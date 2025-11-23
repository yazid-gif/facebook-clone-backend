<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Contrôleur API pour la gestion des posts (articles)
 * Implémente un CRUD complet
 */
class PostController extends Controller
{
    // Les middlewares sont appliqués dans les routes (routes/api.php)

    /**
     * Afficher la liste des articles (avec filtres et pagination)
     * GET /api/posts
     *
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        // Commencer la requête avec les relations
        $query = Post::with(['user', 'category', 'tags'])->withCount('likes');

        // Filtrer par statut
        if (!auth('api')->check()) {
            // Si non authentifié, montrer uniquement les posts publiés
            $query->publie();
        } elseif ($request->has('statut')) {
            // Si authentifié et statut fourni, filtrer par statut
            $query->where('statut', $request->statut);
        } else {
            // Par défaut, montrer les posts publiés
            $query->publie();
        }

        // Filtrer par catégorie
        if ($request->has('category')) {
            $query->where('category_id', $request->category);
        }

        // Filtrer par tags (peut être plusieurs, séparés par des virgules)
        if ($request->has('tags')) {
            $tagIds = is_array($request->tags) 
                ? $request->tags 
                : explode(',', $request->tags);
            
            // Filtrer les posts qui ont au moins un des tags spécifiés
            $query->whereHas('tags', function ($q) use ($tagIds) {
                $q->whereIn('tags.id', $tagIds);
            });
        }

        // Recherche textuelle
        if ($request->has('recherche')) {
            $recherche = $request->recherche;
            $query->where(function ($q) use ($recherche) {
                $q->where('titre', 'like', "%{$recherche}%")
                    ->orWhere('contenu', 'like', "%{$recherche}%");
            });
        }

        // Tri
        $trierPar = $request->get('trier_par', 'created_at');
        $ordreTri = $request->get('ordre_tri', 'desc');
        $query->orderBy($trierPar, $ordreTri);

        // Pagination
        $parPage = $request->get('par_page', 15);
        $posts = $query->paginate($parPage);

        // Retourner une collection de ressources
        return PostResource::collection($posts);
    }

    /**
     * Créer un nouvel article
     * POST /api/posts
     *
     * @param StorePostRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StorePostRequest $request)
    {
        // Créer le post pour l'utilisateur authentifié
        $post = auth('api')->user()->posts()->create([
            'titre' => $request->titre,
            'contenu' => $request->contenu,
            'statut' => $request->get('statut', 'brouillon'),
            'publie_le' => $request->statut === 'publie' ? now() : null,
            'category_id' => $request->category_id,
        ]);

        // Attacher les tags si fournis
        if ($request->has('tags') && is_array($request->tags)) {
            $post->tags()->attach($request->tags);
        }

        // Charger les relations pour la ressource
        $post->load(['user', 'category', 'tags']);
        $post->loadCount('likes');

        return response()->json([
            'success' => true,
            'message' => 'Article créé avec succès',
            'data' => new PostResource($post)
        ], 201); // 201 Created
    }

    /**
     * Afficher un article spécifique
     * GET /api/posts/{post}
     *
     * @param Post $post (Route Model Binding)
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Post $post)
    {
        // Vérifier si le post est un brouillon
        if ($post->statut === 'brouillon') {
            // Seul le propriétaire peut voir ses brouillons
            if (!auth('api')->check() || auth('api')->id() !== $post->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Article non trouvé'
                ], 404);
            }
        }

        // Charger les relations avec le compteur de likes
        $post->load(['user', 'category', 'tags']);
        $post->loadCount('likes');

        return response()->json([
            'success' => true,
            'data' => new PostResource($post)
        ], 200);
    }

    /**
     * Mettre à jour un article
     * PUT/PATCH /api/posts/{post}
     *
     * @param UpdatePostRequest $request
     * @param Post $post
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdatePostRequest $request, Post $post)
    {
        $user = auth('api')->user();

        // Vérifier les permissions :
        // - User : peut modifier uniquement ses propres articles
        // - Editor/Admin : peuvent modifier tous les articles
        if ($user->role === 'user' && $user->id !== $post->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à modifier cet article'
            ], 403);
        }

        // Récupérer uniquement les champs fournis
        $data = $request->only(['titre', 'contenu', 'statut', 'category_id']);

        // Si on passe de brouillon à publié, définir la date de publication
        if (isset($data['statut']) &&
            $data['statut'] === 'publie' &&
            $post->statut !== 'publie') {
            $data['publie_le'] = now();
        }

        // Mettre à jour le post
        $post->update($data);

        // Mettre à jour les tags si fournis
        if ($request->has('tags') && is_array($request->tags)) {
            $post->tags()->sync($request->tags);
        }

        // Recharger les relations avec le compteur de likes
        $post->load(['user', 'category', 'tags']);
        $post->loadCount('likes');

        return response()->json([
            'success' => true,
            'message' => 'Article mis à jour avec succès',
            'data' => new PostResource($post)
        ], 200);
    }

    /**
     * Supprimer un article
     * DELETE /api/posts/{post}
     *
     * @param Post $post
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Post $post)
    {
        $user = auth('api')->user();

        // Vérifier les permissions :
        // - User : peut supprimer uniquement ses propres articles
        // - Editor : ne peut pas supprimer (seul Admin peut)
        // - Admin : peut supprimer tous les articles
        if ($user->role === 'user' && $user->id !== $post->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à supprimer cet article'
            ], 403);
        }

        if ($user->role === 'editor') {
            return response()->json([
                'success' => false,
                'message' => 'Seuls les administrateurs peuvent supprimer des articles'
            ], 403);
        }

        // Supprimer le post
        $post->delete();

        return response()->json([
            'success' => true,
            'message' => 'Article supprimé avec succès'
        ], 200);
    }

    /**
     * Attacher des tags à un post
     * POST /api/posts/{post}/tags
     *
     * @param Request $request
     * @param Post $post
     * @return \Illuminate\Http\JsonResponse
     */
    public function attachTags(Request $request, Post $post)
    {
        // Vérifier l'autorisation
        if (auth('api')->id() !== $post->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à modifier cet article'
            ], 403);
        }

        // Valider les tags
        $request->validate([
            'tags' => 'required|array',
            'tags.*' => 'exists:tags,id',
        ]);

        // Attacher les tags (sans dupliquer)
        $post->tags()->syncWithoutDetaching($request->tags);

        // Recharger les relations
        $post->load(['user', 'category', 'tags']);

        return response()->json([
            'success' => true,
            'message' => 'Tags attachés avec succès',
            'data' => new PostResource($post)
        ], 200);
    }

    /**
     * Détacher un tag d'un post
     * DELETE /api/posts/{post}/tags/{tag}
     *
     * @param Post $post
     * @param \App\Models\Tag $tag
     * @return \Illuminate\Http\JsonResponse
     */
    public function detachTag(Post $post, \App\Models\Tag $tag)
    {
        // Vérifier l'autorisation
        if (auth('api')->id() !== $post->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à modifier cet article'
            ], 403);
        }

        // Détacher le tag
        $post->tags()->detach($tag->id);

        // Recharger les relations
        $post->load(['user', 'category', 'tags']);

        return response()->json([
            'success' => true,
            'message' => 'Tag détaché avec succès',
            'data' => new PostResource($post)
        ], 200);
    }

    /**
     * Liker un article
     * POST /api/posts/{post}/like
     *
     * @param Post $post
     * @return \Illuminate\Http\JsonResponse
     */
    public function like(Post $post)
    {
        $user = auth('api')->user();

        // Vérifier si l'utilisateur a déjà liké ce post
        if ($post->isLikedBy($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous avez déjà liké cet article'
            ], 409); // 409 Conflict
        }

        // Ajouter le like
        $post->likes()->attach($user->id);

        // Recharger les relations avec le compteur de likes
        $post->load(['user', 'category', 'tags']);
        $post->loadCount('likes');

        return response()->json([
            'success' => true,
            'message' => 'Article liké avec succès',
            'data' => new PostResource($post)
        ], 200);
    }

    /**
     * Unliker un article
     * DELETE /api/posts/{post}/like
     *
     * @param Post $post
     * @return \Illuminate\Http\JsonResponse
     */
    public function unlike(Post $post)
    {
        $user = auth('api')->user();

        // Vérifier si l'utilisateur a liké ce post
        if (!$post->isLikedBy($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas liké cet article'
            ], 404);
        }

        // Retirer le like
        $post->likes()->detach($user->id);

        // Recharger les relations avec le compteur de likes
        $post->load(['user', 'category', 'tags']);
        $post->loadCount('likes');

        return response()->json([
            'success' => true,
            'message' => 'Like retiré avec succès',
            'data' => new PostResource($post)
        ], 200);
    }

    /**
     * Voir qui a liké un article
     * GET /api/posts/{post}/likes
     *
     * @param Post $post
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function showLikes(Post $post, Request $request)
    {
        // Pagination
        $parPage = $request->get('per_page', 15);
        
        // Récupérer les utilisateurs qui ont liké avec pagination
        // On utilise withPivot pour accéder aux colonnes de la table pivot
        $likes = $post->likes()
            ->orderBy('post_user_likes.created_at', 'desc')
            ->paginate($parPage);

        return response()->json([
            'success' => true,
            'data' => [
                'post_id' => $post->id,
                'total_likes' => $post->likes()->count(),
                'likes' => $likes->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'nom' => $user->name,
                        'email' => $user->email,
                        'liked_at' => $user->pivot->created_at->format('d/m/Y H:i:s'),
                    ];
                }),
                'pagination' => [
                    'current_page' => $likes->currentPage(),
                    'last_page' => $likes->lastPage(),
                    'per_page' => $likes->perPage(),
                    'total' => $likes->total(),
                ],
            ]
        ], 200);
    }

    /**
     * Supprimer définitivement un article (Admin uniquement)
     * DELETE /api/posts/{post}/force
     *
     * @param Post $post
     * @return \Illuminate\Http\JsonResponse
     */
    public function forceDelete(Post $post)
    {
        // Cette méthode est protégée par le middleware 'role:admin'
        // Supprimer définitivement le post (force delete)
        $post->forceDelete();

        return response()->json([
            'success' => true,
            'message' => 'Article supprimé définitivement avec succès'
        ], 200);
    }

    /**
     * Uploader une image de couverture pour un article
     * POST /api/posts/{post}/image
     *
     * @param Request $request
     * @param Post $post
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadImage(Request $request, Post $post)
    {
        $user = auth('api')->user();

        // Vérifier les permissions :
        // - User : peut uploader uniquement pour ses propres articles
        // - Editor/Admin : peuvent uploader pour tous les articles
        if ($user->role === 'user' && $user->id !== $post->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à modifier cet article'
            ], 403);
        }

        // Validation de l'image
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048', // 2MB max
        ]);

        // Supprimer l'ancienne image si elle existe
        if ($post->image_path) {
            Storage::disk('public')->delete($post->image_path);
        }

        // Sauvegarder la nouvelle image
        $path = $request->file('image')->store('posts', 'public');

        // Mettre à jour le post avec le chemin de l'image
        $post->update(['image_path' => $path]);

        // Recharger les relations
        $post->load(['user', 'category', 'tags']);
        $post->loadCount('likes');

        // Générer l'URL complète de l'image
        $imageUrl = Storage::disk('public')->url($path);

        return response()->json([
            'success' => true,
            'message' => 'Image uploadée avec succès',
            'image_url' => $imageUrl,
            'data' => new PostResource($post)
        ], 200);
    }

    /**
     * Supprimer l'image de couverture d'un article
     * DELETE /api/posts/{post}/image
     *
     * @param Post $post
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteImage(Post $post)
    {
        $user = auth('api')->user();

        // Vérifier les permissions
        if ($user->role === 'user' && $user->id !== $post->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à modifier cet article'
            ], 403);
        }

        // Supprimer l'image du stockage si elle existe
        if ($post->image_path) {
            Storage::disk('public')->delete($post->image_path);
            $post->update(['image_path' => null]);
        }

        // Recharger les relations
        $post->load(['user', 'category', 'tags']);
        $post->loadCount('likes');

        return response()->json([
            'success' => true,
            'message' => 'Image supprimée avec succès',
            'data' => new PostResource($post)
        ], 200);
    }
}

