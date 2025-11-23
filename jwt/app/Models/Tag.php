<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Modèle Tag - Représente un tag pour les articles
 */
class Tag extends Model
{
    use HasFactory;

    /**
     * Attributs assignables en masse
     * Protection contre mass assignment vulnerability
     */
    protected $fillable = [
        'nom',
        'slug'
    ];

    /**
     * Boot method pour générer automatiquement le slug
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tag) {
            if (empty($tag->slug)) {
                $tag->slug = Str::slug($tag->nom);
            }
        });

        static::updating(function ($tag) {
            if ($tag->isDirty('nom') && empty($tag->slug)) {
                $tag->slug = Str::slug($tag->nom);
            }
        });
    }

    // ==========================================
    // RELATIONS ELOQUENT
    // ==========================================

    /**
     * Un tag peut être sur plusieurs posts (Many-to-Many)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function posts()
    {
        return $this->belongsToMany(Post::class)->withTimestamps();
    }

    // ==========================================
    // SCOPES (Requêtes réutilisables)
    // ==========================================

    /**
     * Scope pour compter les posts par tag
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithPostCount($query)
    {
        return $query->withCount('posts');
    }

    /**
     * Scope pour récupérer les tags les plus utilisés
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeMostUsed($query, $limit = 10)
    {
        return $query->withCount('posts')
            ->orderBy('posts_count', 'desc')
            ->limit($limit);
    }
}
