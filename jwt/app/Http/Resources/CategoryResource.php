<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ressource API pour transformer une Category en JSON
 */
class CategoryResource extends JsonResource
{
    /**
     * Transformer la ressource en tableau
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'slug' => $this->slug,
            'description' => $this->description,
            
            // Compteur d'articles (si chargé)
            'nombre_articles' => $this->when(isset($this->posts_count), $this->posts_count),
            
            // Timestamps formatés
            'cree_le' => $this->created_at->format('d/m/Y H:i:s'),
            'modifie_le' => $this->updated_at->format('d/m/Y H:i:s'),
        ];
    }
}
