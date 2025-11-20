<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ressource API pour transformer un Comment en JSON
 * Contrôle exactement quelles données sont exposées
 */
class CommentResource extends JsonResource
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
            // Données de base du commentaire
            'id' => $this->id,
            'contenu' => $this->contenu,

            // Informations de l'auteur (relation)
            'auteur' => $this->when($this->relationLoaded('user') && $this->user, [
                'id' => $this->user->id,
                'nom' => $this->user->name,
                'email' => $this->user->email,
            ]),

            // Informations du post (relation)
            'post_id' => $this->post_id,

            // Timestamps formatés
            'cree_le' => $this->created_at->format('d/m/Y H:i:s'),
            'modifie_le' => $this->updated_at->format('d/m/Y H:i:s'),
        ];
    }
}
