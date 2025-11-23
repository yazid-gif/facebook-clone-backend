<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Request de validation pour la création d'un tag
 */
class StoreTagRequest extends FormRequest
{
    /**
     * Déterminer si l'utilisateur est autorisé
     * L'autorisation est gérée dans le contrôleur/middleware
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
            'nom' => 'required|string|max:255|unique:tags,nom',
            'slug' => 'sometimes|string|max:255|unique:tags,slug',
        ];
    }

    /**
     * Messages d'erreur personnalisés
     */
    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom du tag est obligatoire',
            'nom.unique' => 'Ce tag existe déjà',
            'slug.unique' => 'Ce slug est déjà utilisé',
        ];
    }

    /**
     * Personnaliser la réponse en cas d'échec de validation
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
