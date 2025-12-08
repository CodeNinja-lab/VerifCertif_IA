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
        Schema::create('profil_competences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profil_etudiant_id')->constrained('profil_etudiants')->onDelete('cascade');
            $table->foreignId('competence_id')->constrained('competences')->onDelete('cascade');
            $table->enum('niveau', ['debutant', 'intermediaire', 'avance', 'expert'])->nullable();
            $table->enum('source', ['ia_extraction', 'manuel', 'import_cv', 'validation_admin']);
            $table->foreignId('source_document_id')->nullable()->constrained('documents')->onDelete('set null');
            $table->decimal('score_confiance', 5, 2)->nullable();
            $table->decimal('annees_experience', 3, 1)->nullable();
            $table->boolean('validee_par_etudiant')->default(false);
            $table->timestamp('date_extraction')->useCurrent();
            $table->timestamp('date_validation')->nullable();
            $table->timestamps();
            
            $table->unique(['profil_etudiant_id', 'competence_id']);
            $table->index('competence_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profil_competences');
    }
};
