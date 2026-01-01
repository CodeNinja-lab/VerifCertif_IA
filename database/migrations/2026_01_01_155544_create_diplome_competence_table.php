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
        Schema::create('diplome_competence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diplome_id')->constrained('diplomes')->onDelete('cascade');
            $table->foreignId('competence_id')->constrained('competences')->onDelete('cascade');
            $table->integer('ordre')->default(0);
            $table->timestamps();
            
            $table->unique(['diplome_id', 'competence_id']);
            $table->index('diplome_id');
            $table->index('competence_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diplome_competence');
    }
};
