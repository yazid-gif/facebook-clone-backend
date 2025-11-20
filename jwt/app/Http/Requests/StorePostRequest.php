<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Request de validation pour la création d'un post
 */
class StorePostRequest extends FormRequest
{
    /**
     * Déterminer si l'utilisateur est autorisé
     * Nous gérons l'autorisation dans le contrôleur/middleware
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Règles de validation
     */
    public function rules(): array
    {
        return [
            'titre' => 'required|string|max:255',
            'contenu' => 'required|string|min:10',
            'statut' => 'sometimes|in:brouillon,publie',
        ];
    }

    /**
     * Messages d'erreur personnalisés
     */
    public function messages(): array
    {
        return [
            'titre.required' => 'Le titre de l\'article est obligatoire',
            'titre.max' => 'Le titre ne peut pas dépasser 255 caractères',
            'contenu.required' => 'Le contenu de l\'article est obligatoire',
            'contenu.min' => 'Le contenu doit contenir au moins 10 caractères',
            'statut.in' => 'Le statut doit être "brouillon" ou "publie"',
        ];
    }

    /**
     * Personnaliser la réponse en cas d'échec de validation
     * Retourner un JSON au lieu de rediriger
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Erreurs de validation',
            'errors' => $validator->errors()
        ], 422));
    }
}
