<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;

/**
 * Contrôleur API pour la gestion des commentaires
 * Implémente un CRUD complet avec routes nested
 */
class CommentController extends Controller
{
    /**
     * Afficher la liste des commentaires d'un article
     * GET /api/posts/{post}/commentaires
     *
     * @param Post $post
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Post $post)
    {
        // Vérifier que le post est publié (sauf si l'utilisateur est l'auteur)
        if ($post->statut === 'brouillon') {
            if (!auth('api')->check() || auth('api')->id() !== $post->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Article non trouvé'
                ], 404);
            }
        }

        // Charger les commentaires avec leurs relations
        $commentaires = $post->commentaires()->with('user')->latest()->get();

        return CommentResource::collection($commentaires);
    }

    /**
     * Créer un nouveau commentaire sur un article
     * POST /api/posts/{post}/commentaires
     *
     * @param StoreCommentRequest $request
     * @param Post $post
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreCommentRequest $request, Post $post)
    {
        // Vérifier que le post est publié
        if ($post->statut !== 'publie') {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de commenter un article non publié'
            ], 403);
        }

        // Créer le commentaire pour l'utilisateur authentifié
        $commentaire = $post->commentaires()->create([
            'contenu' => $request->contenu,
            'user_id' => auth('api')->id(),
        ]);

        // Charger la relation 'user' pour la ressource
        $commentaire->load('user');

        return response()->json([
            'success' => true,
            'message' => 'Commentaire créé avec succès',
            'data' => new CommentResource($commentaire)
        ], 201); // 201 Created
    }

    /**
     * Afficher un commentaire spécifique
     * GET /api/posts/{post}/commentaires/{comment}
     *
     * @param Post $post
     * @param Comment $comment
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Post $post, Comment $comment)
    {
        // Vérifier que le commentaire appartient au post
        if ($comment->post_id !== $post->id) {
            return response()->json([
                'success' => false,
                'message' => 'Commentaire non trouvé'
            ], 404);
        }

        // Vérifier que le post est publié (sauf si l'utilisateur est l'auteur du post)
        if ($post->statut === 'brouillon') {
            if (!auth('api')->check() || auth('api')->id() !== $post->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Article non trouvé'
                ], 404);
            }
        }

        // Charger la relation 'user'
        $comment->load('user');

        return response()->json([
            'success' => true,
            'data' => new CommentResource($comment)
        ], 200);
    }

    /**
     * Mettre à jour un commentaire
     * PUT/PATCH /api/posts/{post}/commentaires/{comment}
     *
     * @param UpdateCommentRequest $request
     * @param Post $post
     * @param Comment $comment
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateCommentRequest $request, Post $post, Comment $comment)
    {
        // Vérifier que le commentaire appartient au post
        if ($comment->post_id !== $post->id) {
            return response()->json([
                'success' => false,
                'message' => 'Commentaire non trouvé'
            ], 404);
        }

        // Vérifier l'autorisation : seul l'auteur peut modifier son commentaire
        if (auth('api')->id() !== $comment->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à modifier ce commentaire'
            ], 403);
        }

        // Mettre à jour le commentaire
        $comment->update([
            'contenu' => $request->contenu,
        ]);

        // Recharger la relation 'user'
        $comment->load('user');

        return response()->json([
            'success' => true,
            'message' => 'Commentaire mis à jour avec succès',
            'data' => new CommentResource($comment)
        ], 200);
    }

    /**
     * Supprimer un commentaire
     * DELETE /api/posts/{post}/commentaires/{comment}
     *
     * @param Post $post
     * @param Comment $comment
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Post $post, Comment $comment)
    {
        // Vérifier que le commentaire appartient au post
        if ($comment->post_id !== $post->id) {
            return response()->json([
                'success' => false,
                'message' => 'Commentaire non trouvé'
            ], 404);
        }

        // Vérifier l'autorisation : seul l'auteur peut supprimer son commentaire
        if (auth('api')->id() !== $comment->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à supprimer ce commentaire'
            ], 403);
        }

        // Supprimer le commentaire
        $comment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Commentaire supprimé avec succès'
        ], 200);
    }
}

