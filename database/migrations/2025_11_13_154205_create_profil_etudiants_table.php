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
        Schema::create('profil_etudiants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('utilisateur_id')->constrained('users')->onDelete('cascade')->unique();
            $table->text('bio')->nullable();
            $table->string('cv_url', 500)->nullable();
            $table->string('linkedin_url', 255)->nullable();
            $table->string('github_url', 255)->nullable();
            $table->string('portfolio_url', 255)->nullable();
            $table->enum('disponibilite', ['immediat', '1_mois', '3_mois', 'non_disponible'])->nullable();
            $table->string('localisation_actuelle', 255)->nullable();
            $table->jsonb('localisation_souhaitee')->nullable();
            $table->enum('mobilite', ['locale', 'nationale', 'internationale', 'teletravail'])->nullable();
            $table->integer('salaire_minimum_souhaite')->nullable();
            $table->jsonb('types_contrat_souhaites')->nullable();
            $table->boolean('profil_public')->default(true);
            $table->timestamp('date_mise_a_jour')->useCurrent()->useCurrentOnUpdate();
            $table->timestamps();
            
            $table->index('profil_public');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profil_etudiants');
    }
};
