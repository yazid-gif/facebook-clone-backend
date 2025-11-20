<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Contrôleur d'Authentification
 * Gère l'inscription, la connexion, la déconnexion et le rafraîchissement du token
 */
class AuthController extends Controller
{
    /**
     * Enregistrer un nouvel utilisateur
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        // Validation des données d'entrée
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ], [
            // Messages d'erreur personnalisés en français
            'name.required' => 'Le nom est obligatoire',
            'email.required' => 'L\'email est obligatoire',
            'email.email' => 'L\'email doit être valide',
            'email.unique' => 'Cet email est déjà utilisé',
            'password.required' => 'Le mot de passe est obligatoire',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères',
            'password.confirmed' => 'Les mots de passe ne correspondent pas',
        ]);

        // Si la validation échoue, retourner les erreurs
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => $validator->errors()
            ], 422); // 422 Unprocessable Entity
        }

        // Créer l'utilisateur avec mot de passe hashé
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password), // Hash sécurisé
        ]);

        // Générer un token JWT pour l'utilisateur
        $token = auth('api')->login($user);

        // Retourner la réponse avec le token
        return response()->json([
            'success' => true,
            'message' => 'Utilisateur enregistré avec succès',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'authorization' => [
                    'token' => $token,
                    'type' => 'bearer',
                    'expires_in' => auth('api')->factory()->getTTL() * 60 // En secondes
                ]
            ]
        ], 201); // 201 Created
    }

    /**
     * Connecter un utilisateur existant
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        // Validation des données
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ], [
            'email.required' => 'L\'email est obligatoire',
            'email.email' => 'L\'email doit être valide',
            'password.required' => 'Le mot de passe est obligatoire',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        // Récupérer les credentials (email et password)
        $credentials = $request->only('email', 'password');

        // Tenter l'authentification
        if (!$token = auth('api')->attempt($credentials)) {
            // Si les credentials sont incorrects
            return response()->json([
                'success' => false,
                'message' => 'Email ou mot de passe invalide',
            ], 401); // 401 Unauthorized
        }

        // Récupérer l'utilisateur authentifié
        $user = auth('api')->user();

        // Retourner la réponse avec le token
        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'authorization' => [
                    'token' => $token,
                    'type' => 'bearer',
                    'expires_in' => auth('api')->factory()->getTTL() * 60
                ]
            ]
        ], 200); // 200 OK
    }

    /**
     * Obtenir le profil de l'utilisateur authentifié
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'user' => auth('api')->user() // Utilisateur actuellement connecté
            ]
        ], 200);
    }

    /**
     * Déconnecter l'utilisateur (invalider le token)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        try {
            // Invalider le token JWT actuel en l'ajoutant à la blacklist
            auth('api')->logout(true); // true = invalider le token (blacklist)
            
            return response()->json([
                'success' => true,
                'message' => 'Déconnexion réussie',
            ], 200);
        } catch (\Exception $e) {
            // Si le token est déjà invalidé ou s'il y a une erreur
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la déconnexion: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Rafraîchir le token JWT
     * Obtenir un nouveau token sans se reconnecter
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        // Générer un nouveau token
        $token = auth('api')->refresh();

        return response()->json([
            'success' => true,
            'data' => [
                'authorization' => [
                    'token' => $token,
                    'type' => 'bearer',
                    'expires_in' => auth('api')->factory()->getTTL() * 60
                ]
            ]
        ], 200);
    }
}
