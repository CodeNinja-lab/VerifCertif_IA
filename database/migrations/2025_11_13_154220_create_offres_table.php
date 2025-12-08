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
        Schema::create('offres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recruteur_id')->constrained('users')->onDelete('cascade');
            $table->string('titre', 255);
            $table->text('description');
            $table->string('entreprise', 255);
            $table->string('secteur_activite', 100)->nullable();
            $table->string('lieu', 255);
            $table->enum('type_contrat', ['CDI', 'CDD', 'stage', 'alternance', 'freelance', 'interim']);
            $table->integer('duree_contrat_mois')->nullable();
            $table->enum('teletravail', ['non', 'partiel', 'total'])->nullable();
            $table->integer('salaire_min')->nullable();
            $table->integer('salaire_max')->nullable();
            $table->string('devise', 3)->default('XOF');
            $table->enum('niveau_etudes_requis', ['bac', 'bac+2', 'bac+3', 'bac+5', 'bac+8', 'sans_diplome'])->nullable();
            $table->integer('annees_experience_min')->nullable();
            $table->timestamp('date_publication')->useCurrent();
            $table->date('date_expiration')->nullable();
            $table->enum('statut', ['BROUILLON', 'PUBLIEE', 'EXPIREE', 'POURVUE', 'ARCHIVEE'])->default('BROUILLON');
            $table->integer('nombre_vues')->default(0);
            $table->integer('nombre_candidatures')->default(0);
            $table->timestamps();
            
            $table->index('recruteur_id');
            $table->index('statut');
            $table->index(['date_publication']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offres');
    }
};
