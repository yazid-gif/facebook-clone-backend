<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Contrôleur API pour la gestion des utilisateurs (Admin uniquement)
 */
class UserController extends Controller
{
    /**
     * Afficher la liste des utilisateurs
     * GET /api/users
     * Admin uniquement
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Filtrer par rôle si fourni
        if ($request->has('role') && !empty($request->role)) {
            $query->where('role', $request->role);
        }

        // Recherche par nom ou email
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Tri
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = min($request->get('per_page', 15), 100);
        $users = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'created_at' => $user->created_at->format('d/m/Y H:i:s'),
                ];
            }),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ]
        ], 200);
    }

    /**
     * Afficher un utilisateur spécifique
     * GET /api/users/{user}
     * Admin uniquement
     *
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(User $user)
    {
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'created_at' => $user->created_at->format('d/m/Y H:i:s'),
                'updated_at' => $user->updated_at->format('d/m/Y H:i:s'),
            ]
        ], 200);
    }

    /**
     * Mettre à jour le rôle d'un utilisateur
     * PUT/PATCH /api/users/{user}
     * Admin uniquement
     *
     * @param Request $request
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, User $user)
    {
        // Valider les données
        $request->validate([
            'role' => 'sometimes|in:user,editor,admin',
            'name' => 'sometimes|string|max:255',
        ]);

        // Mettre à jour uniquement les champs fournis
        if ($request->has('role')) {
            $user->role = $request->role;
        }
        if ($request->has('name')) {
            $user->name = $request->name;
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur mis à jour avec succès',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ]
        ], 200);
    }
}
