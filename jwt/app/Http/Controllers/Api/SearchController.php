<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\Request;

/**
 * Contrôleur API pour la recherche avancée de posts
 */
class SearchController extends Controller
{
    /**
     * Recherche avancée de posts avec filtres multiples
     * GET /api/posts/search
     *
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        // Commencer la requête avec les relations et le compteur de likes
        $query = Post::with(['user', 'category', 'tags'])
            ->withCount('likes');

        // Filtrer par statut : uniquement les posts publiés pour la recherche
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

        // ==========================================
        // RECHERCHE TEXTUELLE (q)
        // ==========================================
        if ($request->has('q') && !empty($request->q)) {
            $searchTerm = $request->q;
            
            // Recherche dans le titre et le contenu
            $query->where(function ($q) use ($searchTerm) {
                $q->where('titre', 'like', "%{$searchTerm}%")
                    ->orWhere('contenu', 'like', "%{$searchTerm}%");
            });
        }

        // ==========================================
        // FILTRE PAR CATÉGORIE
        // ==========================================
        if ($request->has('category') && !empty($request->category)) {
            $query->where('category_id', $request->category);
        }

        // ==========================================
        // FILTRE PAR TAGS
        // ==========================================
        if ($request->has('tags') && !empty($request->tags)) {
            $tagIds = is_array($request->tags) 
                ? $request->tags 
                : explode(',', $request->tags);
            
            // Nettoyer les IDs (enlever les espaces)
            $tagIds = array_map('trim', $tagIds);
            $tagIds = array_filter($tagIds);
            
            if (!empty($tagIds)) {
                // Filtrer les posts qui ont au moins un des tags spécifiés
                $query->whereHas('tags', function ($q) use ($tagIds) {
                    $q->whereIn('tags.id', $tagIds);
                });
            }
        }

        // ==========================================
        // FILTRE PAR AUTEUR
        // ==========================================
        if ($request->has('author') && !empty($request->author)) {
            $query->where('user_id', $request->author);
        }

        // ==========================================
        // FILTRES PAR DATE
        // ==========================================
        if ($request->has('date_from') && !empty($request->date_from)) {
            try {
                $dateFrom = \Carbon\Carbon::parse($request->date_from)->startOfDay();
                $query->where('created_at', '>=', $dateFrom);
            } catch (\Exception $e) {
                // Ignorer si la date est invalide
            }
        }

        if ($request->has('date_to') && !empty($request->date_to)) {
            try {
                $dateTo = \Carbon\Carbon::parse($request->date_to)->endOfDay();
                $query->where('created_at', '<=', $dateTo);
            } catch (\Exception $e) {
                // Ignorer si la date est invalide
            }
        }

        // ==========================================
        // TRI
        // ==========================================
        $sort = $request->get('sort', 'recent');
        
        switch ($sort) {
            case 'popular':
                // Trier par nombre de likes (décroissant)
                $query->orderBy('likes_count', 'desc')
                    ->orderBy('created_at', 'desc');
                break;
                
            case 'oldest':
                // Trier par date de création (croissant)
                $query->orderBy('created_at', 'asc');
                break;
                
            case 'recent':
            default:
                // Trier par date de création (décroissant) - par défaut
                $query->orderBy('created_at', 'desc');
                break;
        }

        // ==========================================
        // PAGINATION
        // ==========================================
        $perPage = min($request->get('per_page', 15), 100); // Maximum 100 par page
        $posts = $query->paginate($perPage);

        // Retourner les résultats avec PostResource
        return PostResource::collection($posts)->additional([
            'meta' => [
                'search_params' => [
                    'q' => $request->get('q'),
                    'category' => $request->get('category'),
                    'tags' => $request->get('tags'),
                    'author' => $request->get('author'),
                    'date_from' => $request->get('date_from'),
                    'date_to' => $request->get('date_to'),
                    'sort' => $sort,
                ],
                'total_results' => $posts->total(),
            ]
        ]);
    }
}
