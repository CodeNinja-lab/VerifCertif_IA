<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offres', function (Blueprint $table) {
            $table->string('source_name', 255)->nullable()->index();
            $table->string('source_account', 255)->nullable()->index();
            $table->string('source_external_id', 255)->nullable();
            $table->string('source_url', 2048)->nullable();
            $table->timestamp('source_imported_at')->nullable();
            $table->timestamp('source_last_seen_at')->nullable();

            $table->unique(['recruteur_id', 'source_name', 'source_account', 'source_external_id'], 'offres_source_unique');
        });
    }

    public function down(): void
    {
        Schema::table('offres', function (Blueprint $table) {
            $table->dropUnique('offres_source_unique');
            $table->dropColumn([
                'source_name',
                'source_account',
                'source_external_id',
                'source_url',
                'source_imported_at',
                'source_last_seen_at',
            ]);
        });
    }
};