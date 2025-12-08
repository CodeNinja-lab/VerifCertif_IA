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
        Schema::create('matchings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offre_id')->constrained('offres')->onDelete('cascade');
            $table->foreignId('etudiant_id')->constrained('users')->onDelete('cascade');
            $table->decimal('score_global', 5, 2);
            $table->decimal('score_competences', 5, 2);
            $table->decimal('score_localisation', 5, 2)->nullable();
            $table->decimal('score_experience', 5, 2)->nullable();
            $table->jsonb('competences_matchees')->nullable();
            $table->jsonb('competences_manquantes')->nullable();
            $table->jsonb('points_forts')->nullable();
            $table->jsonb('points_amelioration')->nullable();
            $table->string('algorithme_version', 20);
            $table->decimal('seuil_notification', 5, 2);
            $table->boolean('notifie')->default(false);
            $table->timestamp('date_notification')->nullable();
            $table->timestamp('date_matching')->useCurrent();
            $table->boolean('vu_par_etudiant')->default(false);
            $table->timestamp('date_vue_etudiant')->nullable();
            $table->boolean('interesse')->nullable();
            $table->boolean('vu_par_recruteur')->default(false);
            $table->timestamps();
            
            $table->unique(['offre_id', 'etudiant_id']);
            $table->index(['offre_id', 'score_global']);
            $table->index(['etudiant_id', 'score_global']);
            $table->index('notifie');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matchings');
    }
};
