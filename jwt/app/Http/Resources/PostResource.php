<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Ressource API pour transformer un Post en JSON
 * Contrôle exactement quelles données sont exposées
 */
class PostResource extends JsonResource
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
            // Données de base du post
            'id' => $this->id,
            'titre' => $this->titre,
            'contenu' => $this->contenu,
            'statut' => $this->statut,

            // Date de publication formatée (peut être null)
            'publie_le' => $this->publie_le?->format('d/m/Y H:i:s'),

            // Image de couverture
            'image_url' => $this->when(
                $this->image_path,
                function () {
                    return Storage::disk('public')->url($this->image_path);
                }
            ),

            // Informations de l'auteur (relation)
            'auteur' => $this->when($this->relationLoaded('user') && $this->user, [
                'id' => $this->user->id,
                'nom' => $this->user->name,
                'email' => $this->user->email,
            ]),

            // Catégorie (relation)
            'categorie' => $this->when($this->relationLoaded('category') && $this->category, [
                'id' => $this->category->id,
                'nom' => $this->category->nom,
                'slug' => $this->category->slug,
            ]),

            // Tags (relation)
            'tags' => $this->when($this->relationLoaded('tags'), function () {
                return $this->tags->map(function ($tag) {
                    return [
                        'id' => $tag->id,
                        'nom' => $tag->nom,
                        'slug' => $tag->slug,
                    ];
                });
            }),

            // Likes
            'nombre_likes' => $this->when(isset($this->likes_count), $this->likes_count),
            'a_ete_like' => $this->when(
                auth('api')->check(),
                function () {
                    return $this->isLikedBy(auth('api')->user());
                }
            ),

            // Timestamps formatés
            'cree_le' => $this->created_at->format('d/m/Y H:i:s'),
            'modifie_le' => $this->updated_at->format('d/m/Y H:i:s'),
        ];
    }
}
