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
        Schema::create('post_tag', function (Blueprint $table) {
            // Clés étrangères vers posts et tags
            $table->foreignId('post_id')
                ->constrained()
                ->onDelete('cascade');
            
            $table->foreignId('tag_id')
                ->constrained()
                ->onDelete('cascade');

            // Clé primaire composite (post_id, tag_id)
            $table->primary(['post_id', 'tag_id']);

            // Timestamps automatiques (created_at, updated_at)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_tag');
    }
};
