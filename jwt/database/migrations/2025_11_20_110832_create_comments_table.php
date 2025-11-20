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
        Schema::create('comments', function (Blueprint $table) {
            // Clé primaire auto-incrémentée
            $table->id();

            // Clé étrangère vers la table posts
            // onDelete('cascade') : Si le post est supprimé, ses commentaires aussi
            $table->foreignId('post_id')
                ->constrained()
                ->onDelete('cascade');

            // Clé étrangère vers la table users
            // onDelete('cascade') : Si l'utilisateur est supprimé, ses commentaires aussi
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');

            // Colonne de données
            $table->text('contenu'); // TEXT (long texte)

            // Timestamps automatiques (created_at, updated_at)
            $table->timestamps();

            // Index pour améliorer les performances des requêtes
            $table->index('post_id'); // Index sur post_id
            $table->index('user_id'); // Index sur user_id
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
