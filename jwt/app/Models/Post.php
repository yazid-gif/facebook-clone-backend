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
        'user_id',
        'category_id',
        'image_path'
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

    /**
     * Un post peut avoir plusieurs commentaires (One-to-Many)
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function commentaires()
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Un post appartient à une catégorie (Many-to-One)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Un post peut avoir plusieurs tags (Many-to-Many)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function tags()
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }

    /**
     * Un post peut être liké par plusieurs utilisateurs (Many-to-Many)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function likes()
    {
        return $this->belongsToMany(User::class, 'post_user_likes')
            ->withTimestamps();
    }

    /**
     * Vérifier si un utilisateur a liké ce post
     *
     * @param User $user
     * @return bool
     */
    public function isLikedBy(User $user)
    {
        return $this->likes()->where('user_id', $user->id)->exists();
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
