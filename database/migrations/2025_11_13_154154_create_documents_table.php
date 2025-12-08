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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid_document')->unique();
            $table->foreignId('etudiant_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('administration_id')->constrained('administrations')->onDelete('restrict');
            $table->enum('type_document', ['diplome', 'releve_notes', 'attestation', 'certificat', 'autre']);
            $table->string('titre', 255);
            $table->string('file_url', 500);
            $table->integer('file_size_kb');
            $table->char('hash_sha256', 64)->unique();
            $table->text('signature_ed25519');
            $table->string('qr_code_url', 500)->nullable();
            $table->string('blockchain_tx_hash', 255)->nullable();
            $table->string('blockchain_network', 50)->nullable();
            $table->enum('statut', ['ACTIF', 'REVOQUE', 'EXPIRE'])->default('ACTIF');
            $table->date('date_emission');
            $table->timestamp('date_certification')->useCurrent();
            $table->date('date_expiration')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            
            $table->index('etudiant_id');
            $table->index('administration_id');
            $table->index('statut');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
