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
        Schema::table('offres', function (Blueprint $table) {
            $table->text('missions_principales')->nullable()->after('description');
            $table->text('profil_recherche')->nullable()->after('missions_principales');
            $table->text('nice_to_have')->nullable()->after('profil_recherche');
            $table->jsonb('avantages')->nullable()->after('nice_to_have');
            $table->jsonb('processus_recrutement')->nullable()->after('avantages');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offres', function (Blueprint $table) {
            $table->dropColumn([
                'missions_principales',
                'profil_recherche',
                'nice_to_have',
                'avantages',
                'processus_recrutement',
            ]);
        });
    }
};

