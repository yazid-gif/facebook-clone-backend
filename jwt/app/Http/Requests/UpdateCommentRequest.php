<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Request de validation pour la mise à jour d'un commentaire
 */
class UpdateCommentRequest extends FormRequest
{
    /**
     * Déterminer si l'utilisateur est autorisé
     * L'autorisation est gérée dans le contrôleur
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
            'contenu' => 'required|string|min:3',
        ];
    }

    /**
     * Messages d'erreur personnalisés
     */
    public function messages(): array
    {
        return [
            'contenu.required' => 'Le contenu du commentaire est obligatoire',
            'contenu.min' => 'Le contenu doit contenir au moins 3 caractères',
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
