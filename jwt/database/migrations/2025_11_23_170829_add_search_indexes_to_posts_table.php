<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Exécuter la migration - Ajouter des index pour optimiser les recherches
     */
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            // Index sur created_at pour optimiser les tris par date
            $table->index('created_at');
            
            // Index composite pour optimiser les recherches filtrées par statut et triées par date
            // (souvent utilisé ensemble : WHERE statut = 'publie' ORDER BY created_at DESC)
            $table->index(['statut', 'created_at']);
        });
    }

    /**
     * Annuler la migration - Supprimer les index
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['statut', 'created_at']);
        });
    }
};
