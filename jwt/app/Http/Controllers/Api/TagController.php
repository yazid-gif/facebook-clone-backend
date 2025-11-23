<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTagRequest;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use Illuminate\Http\Request;

/**
 * Contrôleur API pour la gestion des tags
 */
class TagController extends Controller
{
    /**
     * Afficher la liste des tags
     * GET /api/tags
     *
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $query = Tag::query();

        // Inclure le compteur d'articles si demandé
        if ($request->has('with_count')) {
            $query->withPostCount();
        }

        // Récupérer les tags les plus utilisés
        if ($request->has('most_used')) {
            $limit = $request->get('limit', 10);
            $query->mostUsed($limit);
        } else {
            // Tri par défaut
            $trierPar = $request->get('trier_par', 'nom');
            $ordreTri = $request->get('ordre_tri', 'asc');
            $query->orderBy($trierPar, $ordreTri);
        }

        $tags = $query->get();

        return TagResource::collection($tags);
    }

    /**
     * Créer un nouveau tag
     * POST /api/tags
     * Note: Devrait être protégé par un middleware admin
     *
     * @param StoreTagRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreTagRequest $request)
    {
        $tag = Tag::create([
            'nom' => $request->nom,
            'slug' => $request->get('slug'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tag créé avec succès',
            'data' => new TagResource($tag)
        ], 201);
    }

    /**
     * Afficher un tag spécifique
     * GET /api/tags/{tag}
     *
     * @param Tag $tag
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Tag $tag)
    {
        // Charger le compteur d'articles
        $tag->loadCount('posts');

        return response()->json([
            'success' => true,
            'data' => new TagResource($tag)
        ], 200);
    }

    /**
     * Mettre à jour un tag
     * PUT/PATCH /api/tags/{tag}
     * Editor/Admin uniquement
     *
     * @param Request $request
     * @param Tag $tag
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Tag $tag)
    {
        $request->validate([
            'nom' => 'sometimes|string|max:255|unique:tags,nom,' . $tag->id,
            'slug' => 'sometimes|string|max:255|unique:tags,slug,' . $tag->id,
        ]);

        $tag->update($request->only(['nom', 'slug']));

        return response()->json([
            'success' => true,
            'message' => 'Tag mis à jour avec succès',
            'data' => new TagResource($tag)
        ], 200);
    }

    /**
     * Supprimer un tag
     * DELETE /api/tags/{tag}
     * Editor/Admin uniquement
     *
     * @param Tag $tag
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Tag $tag)
    {
        // Le tag sera automatiquement détaché des posts grâce à la cascade
        $tag->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tag supprimé avec succès'
        ], 200);
    }
}
