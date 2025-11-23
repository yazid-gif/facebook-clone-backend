<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Exécuter la migration - Créer la table pivot
     */
    public function up(): void
    {
        Schema::create('post_user_likes', function (Blueprint $table) {
            // Clés étrangères vers users et posts
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');
            
            $table->foreignId('post_id')
                ->constrained()
                ->onDelete('cascade');

            // Contrainte unique : un user ne peut liker qu'une fois le même post
            $table->unique(['user_id', 'post_id']);

            // Timestamps automatiques (created_at, updated_at)
            $table->timestamps();
        });
    }

    /**
     * Annuler la migration - Supprimer la table
     */
    public function down(): void
    {
        Schema::dropIfExists('post_user_likes');
    }
};
