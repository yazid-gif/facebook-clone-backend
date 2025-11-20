<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Exécuter la migration - Créer la table
     */
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            // Clé primaire auto-incrémentée
            $table->id();

            // Clé étrangère vers la table users
            // onDelete('cascade') : Si l'utilisateur est supprimé, ses posts aussi
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');

            // Colonnes de données
            $table->string('titre'); // VARCHAR(255)
            $table->text('contenu'); // TEXT (long texte)

            // Énumération pour le statut
            $table->enum('statut', ['brouillon', 'publie'])
                ->default('brouillon');

            // Date de publication (nullable = peut être NULL)
            $table->timestamp('publie_le')->nullable();

            // Timestamps automatiques (created_at, updated_at)
            $table->timestamps();

            // Index pour améliorer les performances des requêtes
            $table->index('statut'); // Index sur le statut
            $table->index('user_id'); // Index sur user_id
        });
    }

    /**
     * Annuler la migration - Supprimer la table
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
