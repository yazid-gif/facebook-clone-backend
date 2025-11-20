<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modèle Comment - Représente un commentaire sur un article
 */
class Comment extends Model
{
    use HasFactory;

    /**
     * Attributs assignables en masse
     * Protection contre mass assignment vulnerability
     */
    protected $fillable = [
        'contenu',
        'post_id',
        'user_id'
    ];

    // ==========================================
    // RELATIONS ELOQUENT
    // ==========================================

    /**
     * Un commentaire appartient à un post (Many-to-One)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Un commentaire appartient à un utilisateur (Many-to-One)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
