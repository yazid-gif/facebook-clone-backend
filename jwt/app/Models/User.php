<?php

namespace App\Models;

// Imports standards de Laravel
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// ⚠️ IMPORTANT : Importer l'interface JWTSubject
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

/**
 * Modèle User - Représente un utilisateur de l'application
 * Implémente JWTSubject pour l'authentification JWT
 */
class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    /**
     * Attributs qui peuvent être assignés en masse
     * Protection contre les vulnérabilités de mass assignment
     */
    protected $fillable = [
        'name', // Nom de l'utilisateur
        'email', // Email (unique)
        'password', // Mot de passe (sera hashé)
        'role', // Rôle de l'utilisateur (user, editor, admin)
    ];

    /**
     * Attributs cachés dans les réponses JSON
     * Pour des raisons de sécurité
     */
    protected $hidden = [
        'password', // Ne jamais exposer le mot de passe
        'remember_token', // Token de session
    ];

    /**
     * Conversion automatique des types
     * Laravel 11+ utilise une méthode casts()
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime', // Convertir en objet Carbon
            'password' => 'hashed', // Hash automatique
        ];
    }

    // ==========================================
    // Méthodes JWT OBLIGATOIRES
    // ==========================================

    /**
     * Obtenir l'identifiant qui sera stocké dans le JWT
     * Généralement l'ID de l'utilisateur
     */
    public function getJWTIdentifier()
    {
        return $this->getKey(); // Retourne l'ID (clé primaire)
    }

    /**
     * Retourner des données personnalisées à inclure dans le JWT
     * Par exemple : rôle, permissions, etc.
     */
    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->role, // Inclure le rôle dans le JWT
        ];
    }

    // ==========================================
    // MÉTHODES DE VÉRIFICATION DE RÔLE
    // ==========================================

    /**
     * Vérifier si l'utilisateur est admin
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Vérifier si l'utilisateur est éditeur
     *
     * @return bool
     */
    public function isEditor(): bool
    {
        return $this->role === 'editor';
    }

    /**
     * Vérifier si l'utilisateur est admin ou éditeur
     *
     * @return bool
     */
    public function isEditorOrAdmin(): bool
    {
        return in_array($this->role, ['editor', 'admin']);
    }

    // ==========================================
    // RELATIONS ELOQUENT
    // ==========================================

    /**
     * Un utilisateur peut avoir plusieurs posts (One-to-Many)
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Un utilisateur peut avoir plusieurs commentaires (One-to-Many)
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function commentaires()
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Un utilisateur peut liker plusieurs posts (Many-to-Many)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function likedPosts()
    {
        return $this->belongsToMany(Post::class, 'post_user_likes')
            ->withTimestamps();
    }
}
