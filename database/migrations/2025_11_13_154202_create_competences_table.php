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
        Schema::create('competences', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 255);
            $table->string('nom_normalise', 255);
            $table->enum('categorie', ['technique', 'transversale', 'langue', 'logiciel', 'framework', 'domaine', 'autre']);
            $table->text('description')->nullable();
            $table->string('referentiel_externe_id', 100)->nullable();
            $table->jsonb('synonymes')->nullable();
            $table->integer('popularite')->default(0);
            $table->timestamp('date_creation')->useCurrent();
            $table->timestamps();
            
            $table->index('nom_normalise');
            $table->index('categorie');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('competences');
    }
};
