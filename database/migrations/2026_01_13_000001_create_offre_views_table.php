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
        Schema::create('offre_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offre_id')->constrained('offres')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('viewed_at');
            
            // Un utilisateur ne peut voir qu'une seule fois une offre (pour le comptage)
            $table->unique(['offre_id', 'user_id']);
            
            $table->index('offre_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offre_views');
    }
};
