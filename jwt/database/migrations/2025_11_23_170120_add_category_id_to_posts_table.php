<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Exécuter la migration - Ajouter category_id à la table posts
     */
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            // Clé étrangère vers la table categories
            // nullable() : Un post peut ne pas avoir de catégorie
            // onDelete('set null') : Si la catégorie est supprimée, category_id devient null
            $table->foreignId('category_id')
                ->nullable()
                ->after('user_id')
                ->constrained()
                ->onDelete('set null');

            // Index pour améliorer les performances
            $table->index('category_id');
        });
    }

    /**
     * Annuler la migration - Supprimer category_id de la table posts
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropIndex(['category_id']);
            $table->dropColumn('category_id');
        });
    }
};
