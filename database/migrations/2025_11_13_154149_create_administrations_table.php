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
        Schema::create('administrations', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 255);
            $table->enum('type_administration', ['universite', 'ecole', 'organisme_formation', 'administration_publique', 'autre']);
            $table->string('pays', 100);
            $table->string('ville', 100)->nullable();
            $table->text('adresse')->nullable();
            $table->string('numero_accreditation', 100)->nullable()->unique();
            $table->string('email_contact', 255);
            $table->string('telephone_contact', 20)->nullable();
            $table->text('cle_publique_ed25519');
            $table->string('logo_url', 500)->nullable();
            $table->string('site_web', 255)->nullable();
            $table->timestamp('date_inscription')->useCurrent();
            $table->enum('statut', ['en_attente', 'verifie', 'suspendu'])->default('en_attente');
            $table->timestamps();
            
            $table->index('numero_accreditation');
            $table->index('statut');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('administrations');
    }
};
