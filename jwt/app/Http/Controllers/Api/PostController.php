<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\Request;

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
        // Commencer la requête avec la relation 'user'
        $query = Post::with('user');

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
        ]);

        // Charger la relation 'user' pour la ressource
        $post->load('user');

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

        // Charger la relation 'user'
        $post->load('user');

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
        // Récupérer uniquement les champs fournis
        $data = $request->only(['titre', 'contenu', 'statut']);

        // Si on passe de brouillon à publié, définir la date de publication
        if (isset($data['statut']) &&
            $data['statut'] === 'publie' &&
            $post->statut !== 'publie') {
            $data['publie_le'] = now();
        }

        // Mettre à jour le post
        $post->update($data);

        // Recharger la relation 'user'
        $post->load('user');

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
        // Vérifier l'autorisation
        if (auth('api')->id() !== $post->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à supprimer cet article'
            ], 403);
        }

        // Supprimer le post
        $post->delete();

        return response()->json([
            'success' => true,
            'message' => 'Article supprimé avec succès'
        ], 200);
    }
}

