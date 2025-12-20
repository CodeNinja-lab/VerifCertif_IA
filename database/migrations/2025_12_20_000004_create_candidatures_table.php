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
        Schema::create('candidatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etudiant_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('offre_id')->constrained('offres')->onDelete('cascade');
            $table->enum('statut', ['envoyee', 'vue', 'en_cours', 'entretien', 'acceptee', 'refusee', 'annulee'])->default('envoyee');
            $table->text('lettre_motivation')->nullable();
            $table->string('cv_url', 500)->nullable();
            $table->timestamp('date_candidature')->useCurrent();
            $table->timestamp('date_entretien')->nullable();
            $table->text('notes_recruteur')->nullable();
            $table->text('feedback')->nullable();
            $table->timestamps();
            
            // Un étudiant ne peut postuler qu'une fois à une offre
            $table->unique(['etudiant_id', 'offre_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidatures');
    }
};
