<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Request de validation pour la création d'une catégorie
 */
class StoreCategoryRequest extends FormRequest
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
            'nom' => 'required|string|max:255|unique:categories,nom',
            'slug' => 'sometimes|string|max:255|unique:categories,slug',
            'description' => 'nullable|string',
        ];
    }

    /**
     * Messages d'erreur personnalisés
     */
    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom de la catégorie est obligatoire',
            'nom.unique' => 'Cette catégorie existe déjà',
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
