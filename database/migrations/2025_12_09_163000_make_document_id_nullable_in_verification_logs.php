<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('verification_logs', function (Blueprint $table) {
            // Supprimer d'abord la contrainte de clé étrangère
            $table->dropForeign(['document_id']);
            
            // Modifier la colonne pour la rendre nullable
            $table->unsignedBigInteger('document_id')->nullable()->change();
            
            // Recréer la contrainte de clé étrangère avec nullable
            $table->foreign('document_id')
                  ->references('id')
                  ->on('documents')
                  ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('verification_logs', function (Blueprint $table) {
            // Supprimer la contrainte
            $table->dropForeign(['document_id']);
            
            // Remettre la colonne en non-nullable
            $table->unsignedBigInteger('document_id')->nullable(false)->change();
            
            // Recréer la contrainte
            $table->foreign('document_id')
                  ->references('id')
                  ->on('documents')
                  ->onDelete('restrict');
        });
    }
};
