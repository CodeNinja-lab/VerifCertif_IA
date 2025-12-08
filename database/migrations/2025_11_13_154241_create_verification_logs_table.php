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
        Schema::create('verification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->onDelete('restrict');
            $table->enum('verificateur_type', ['public', 'recruteur', 'administration', 'systeme']);
            $table->foreignId('verificateur_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('ip_hash', 64);
            $table->text('user_agent')->nullable();
            $table->string('pays', 100)->nullable();
            $table->string('ville', 100)->nullable();
            $table->enum('methode_verification', ['qr_scan', 'upload_fichier', 'url_directe', 'api']);
            $table->enum('resultat', ['VALIDE', 'INVALIDE', 'REVOQUE', 'EXPIRE', 'ERREUR']);
            $table->text('details_erreur')->nullable();
            $table->integer('duree_ms')->nullable();
            $table->timestamp('date_verification')->useCurrent();
            $table->timestamps();
            
            $table->index('document_id');
            $table->index(['date_verification']);
            $table->index('resultat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verification_logs');
    }
};
