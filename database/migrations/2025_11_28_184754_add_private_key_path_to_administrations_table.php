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
        Schema::table('administrations', function (Blueprint $table) {
            $table->string('private_key_path', 500)->nullable()->after('cle_publique_ed25519');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('administrations', function (Blueprint $table) {
            $table->dropColumn('private_key_path');
        });
    }
};
