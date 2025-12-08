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
        Schema::create('revocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->onDelete('restrict');
            $table->foreignId('administration_id')->constrained('administrations')->onDelete('restrict');
            $table->foreignId('operateur_id')->constrained('users')->onDelete('restrict');
            $table->enum('motif_categorie', ['fraude', 'erreur_administrative', 'annulation_diplome', 'demande_titulaire', 'autre']);
            $table->text('motif_detail');
            $table->string('document_justificatif_url', 500)->nullable();
            $table->boolean('notification_titulaire')->default(false);
            $table->timestamp('date_notification')->nullable();
            $table->timestamp('date_revocation')->useCurrent();
            $table->boolean('irreversible')->default(true);
            $table->timestamps();
            
            $table->index('document_id');
            $table->index('administration_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('revocations');
    }
};
