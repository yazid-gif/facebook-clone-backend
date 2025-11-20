<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modèle Post - Représente un article de blog
 */
class Post extends Model
{
    use HasFactory;

    /**
     * Attributs assignables en masse
     * Protection contre mass assignment vulnerability
     */
    protected $fillable = [
        'titre',
        'contenu',
        'statut',
        'publie_le',
        'user_id'
    ];

    /**
     * Conversion automatique des types
     */
    protected $casts = [
        'publie_le' => 'datetime', // Convertir en objet Carbon
    ];

    // ==========================================
    // RELATIONS ELOQUENT
    // ==========================================

    /**
     * Un post appartient à un utilisateur (Many-to-One)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ==========================================
    // SCOPES (Requêtes réutilisables)
    // ==========================================

    /**
     * Scope pour récupérer uniquement les posts publiés
     * Usage : Post::publie()->get()
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublie($query)
    {
        return $query->where('statut', 'publie');
    }

    /**
     * Scope pour récupérer uniquement les brouillons
     * Usage : Post::brouillon()->get()
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBrouillon($query)
    {
        return $query->where('statut', 'brouillon');
    }
}
