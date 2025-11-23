<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;

/**
 * Contrôleur API pour la gestion des catégories
 */
class CategoryController extends Controller
{
    /**
     * Afficher la liste des catégories
     * GET /api/categories
     *
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $query = Category::query();

        // Inclure le compteur d'articles si demandé
        if ($request->has('with_count')) {
            $query->withPostCount();
        }

        // Tri
        $trierPar = $request->get('trier_par', 'nom');
        $ordreTri = $request->get('ordre_tri', 'asc');
        $query->orderBy($trierPar, $ordreTri);

        $categories = $query->get();

        return CategoryResource::collection($categories);
    }

    /**
     * Créer une nouvelle catégorie
     * POST /api/categories
     * Note: Devrait être protégé par un middleware admin
     *
     * @param StoreCategoryRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreCategoryRequest $request)
    {
        $category = Category::create([
            'nom' => $request->nom,
            'slug' => $request->get('slug'),
            'description' => $request->description,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Catégorie créée avec succès',
            'data' => new CategoryResource($category)
        ], 201);
    }

    /**
     * Afficher une catégorie spécifique
     * GET /api/categories/{category}
     *
     * @param Category $category
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Category $category)
    {
        // Charger le compteur d'articles
        $category->loadCount('posts');

        return response()->json([
            'success' => true,
            'data' => new CategoryResource($category)
        ], 200);
    }

    /**
     * Mettre à jour une catégorie
     * PUT/PATCH /api/categories/{category}
     * Editor/Admin uniquement
     *
     * @param Request $request
     * @param Category $category
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Category $category)
    {
        $request->validate([
            'nom' => 'sometimes|string|max:255|unique:categories,nom,' . $category->id,
            'slug' => 'sometimes|string|max:255|unique:categories,slug,' . $category->id,
            'description' => 'nullable|string',
        ]);

        $category->update($request->only(['nom', 'slug', 'description']));

        return response()->json([
            'success' => true,
            'message' => 'Catégorie mise à jour avec succès',
            'data' => new CategoryResource($category)
        ], 200);
    }

    /**
     * Supprimer une catégorie
     * DELETE /api/categories/{category}
     * Editor/Admin uniquement
     *
     * @param Category $category
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Category $category)
    {
        // Vérifier si la catégorie a des posts
        if ($category->posts()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer une catégorie qui contient des articles'
            ], 422);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Catégorie supprimée avec succès'
        ], 200);
    }
}
