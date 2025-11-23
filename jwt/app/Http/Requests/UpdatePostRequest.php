<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Request de validation pour la mise à jour d'un post
 */
class UpdatePostRequest extends FormRequest
{
    /**
     * Vérifier les permissions :
     * - User : peut modifier uniquement ses propres articles
     * - Editor/Admin : peuvent modifier tous les articles
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $post = $this->route('post');
        
        // Editor et Admin peuvent modifier tous les articles
        if (in_array($user->role, ['editor', 'admin'])) {
            return true;
        }
        
        // User peut modifier uniquement ses propres articles
        return $user->id === $post->user_id;
    }

    /**
     * Règles de validation
     * 'sometimes' = le champ est optionnel mais s'il existe, il doit être valide
     */
    public function rules(): array
    {
        return [
            'titre' => 'sometimes|string|max:255',
            'contenu' => 'sometimes|string|min:10',
            'statut' => 'sometimes|in:brouillon,publie',
            'category_id' => 'sometimes|nullable|exists:categories,id',
            'tags' => 'sometimes|array',
            'tags.*' => 'exists:tags,id',
        ];
    }

    /**
     * Messages d'erreur personnalisés
     */
    public function messages(): array
    {
        return [
            'titre.max' => 'Le titre ne peut pas dépasser 255 caractères',
            'contenu.min' => 'Le contenu doit contenir au moins 10 caractères',
            'statut.in' => 'Le statut doit être "brouillon" ou "publie"',
        ];
    }

    /**
     * Réponse JSON en cas d'échec de validation
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Erreurs de validation',
            'errors' => $validator->errors()
        ], 422));
    }

    /**
     * Réponse JSON en cas d'échec d'autorisation
     */
    protected function failedAuthorization()
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Vous n\'êtes pas autorisé à modifier cet article'
        ], 403));
    }
}
