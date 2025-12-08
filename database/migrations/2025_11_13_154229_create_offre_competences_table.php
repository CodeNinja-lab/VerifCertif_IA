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
        Schema::create('offre_competences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offre_id')->constrained('offres')->onDelete('cascade');
            $table->foreignId('competence_id')->constrained('competences')->onDelete('cascade');
            $table->enum('niveau_requis', ['debutant', 'intermediaire', 'avance', 'expert'])->nullable();
            $table->enum('importance', ['indispensable', 'importante', 'souhaitee', 'bonus']);
            $table->integer('poids')->default(5);
            $table->timestamps();
            
            $table->unique(['offre_id', 'competence_id']);
            $table->index('competence_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offre_competences');
    }
};
