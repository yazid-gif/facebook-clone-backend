<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Modèle Category - Représente une catégorie d'articles
 */
class Category extends Model
{
    use HasFactory;

    /**
     * Attributs assignables en masse
     * Protection contre mass assignment vulnerability
     */
    protected $fillable = [
        'nom',
        'slug',
        'description'
    ];

    /**
     * Boot method pour générer automatiquement le slug
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->nom);
            }
        });

        static::updating(function ($category) {
            if ($category->isDirty('nom') && empty($category->slug)) {
                $category->slug = Str::slug($category->nom);
            }
        });
    }

    // ==========================================
    // RELATIONS ELOQUENT
    // ==========================================

    /**
     * Une catégorie peut avoir plusieurs posts (One-to-Many)
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    // ==========================================
    // SCOPES (Requêtes réutilisables)
    // ==========================================

    /**
     * Scope pour compter les posts par catégorie
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithPostCount($query)
    {
        return $query->withCount('posts');
    }
}
