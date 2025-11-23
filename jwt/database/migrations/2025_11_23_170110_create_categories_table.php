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
        Schema::create('categories', function (Blueprint $table) {
            // Clé primaire auto-incrémentée
            $table->id();

            // Colonnes de données
            $table->string('nom')->unique(); // Nom de la catégorie (unique)
            $table->string('slug')->unique(); // Slug pour les URLs (unique)
            $table->text('description')->nullable(); // Description optionnelle

            // Timestamps automatiques (created_at, updated_at)
            $table->timestamps();

            // Index pour améliorer les performances
            $table->index('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
