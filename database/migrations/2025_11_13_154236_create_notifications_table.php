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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('destinataire_id')->constrained('users')->onDelete('cascade');
            $table->enum('type', ['nouvelle_offre', 'matching', 'verification_document', 'message', 'alerte_systeme', 'rappel']);
            $table->enum('priorite', ['basse', 'normale', 'haute', 'urgente'])->default('normale');
            $table->string('titre', 255);
            $table->text('message');
            $table->string('lien_action', 500)->nullable();
            $table->string('icone', 50)->nullable();
            $table->boolean('lue')->default(false);
            $table->timestamp('date_lecture')->nullable();
            $table->boolean('archivee')->default(false);
            $table->timestamp('date_envoi')->useCurrent();
            $table->timestamp('date_expiration')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['destinataire_id', 'lue']);
            $table->index(['date_envoi']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
