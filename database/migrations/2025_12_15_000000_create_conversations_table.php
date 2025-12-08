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
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recruteur_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('etudiant_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('offre_id')->nullable()->constrained('offres')->onDelete('set null');
            $table->foreignId('matching_id')->nullable()->constrained('matchings')->onDelete('set null');
            $table->timestamp('last_message_at')->nullable();
            $table->boolean('recruteur_has_unread')->default(false);
            $table->boolean('etudiant_has_unread')->default(false);
            $table->timestamps();

            // Index pour les recherches
            $table->index(['recruteur_id', 'last_message_at']);
            $table->index(['etudiant_id', 'last_message_at']);
            // Index unique partiel (PostgreSQL supporte les index partiels)
            // On ne peut pas avoir deux conversations identiques entre le même recruteur et étudiant pour la même offre
            // Mais on peut avoir plusieurs conversations sans offre_id
            $table->index(['recruteur_id', 'etudiant_id', 'offre_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};

