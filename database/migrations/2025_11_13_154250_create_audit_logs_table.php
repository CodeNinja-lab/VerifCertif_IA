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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('utilisateur_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('ip_hash', 64);
            $table->string('action', 100);
            $table->string('objet_type', 50)->nullable();
            $table->integer('objet_id')->nullable();
            $table->enum('statut', ['succes', 'echec', 'erreur']);
            $table->jsonb('details')->nullable();
            $table->text('message_erreur')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('date_action')->useCurrent();
            $table->timestamps();
            
            $table->index('utilisateur_id');
            $table->index('action');
            $table->index(['date_action']);
            $table->index('statut');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
