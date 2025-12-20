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
        Schema::create('experiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('utilisateur_id')->constrained('users')->onDelete('cascade');
            $table->string('titre', 255);
            $table->string('entreprise', 255);
            $table->string('localisation', 255)->nullable();
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->boolean('poste_actuel')->default(false);
            $table->text('description')->nullable();
            $table->json('realisations')->nullable(); // Array of achievements
            $table->integer('ordre')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('experiences');
    }
};
