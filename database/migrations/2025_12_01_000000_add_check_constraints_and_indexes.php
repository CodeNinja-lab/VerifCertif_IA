<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Contraintes CHECK en SQL brut (PostgreSQL)
        DB::statement('
            ALTER TABLE matchings 
            ADD CONSTRAINT check_score_global 
            CHECK (score_global >= 0 AND score_global <= 100)
        ');

        DB::statement('
            ALTER TABLE matchings 
            ADD CONSTRAINT check_score_competences 
            CHECK (score_competences >= 0 AND score_competences <= 100)
        ');

        DB::statement('
            ALTER TABLE matchings 
            ADD CONSTRAINT check_score_localisation 
            CHECK (score_localisation IS NULL OR (score_localisation >= 0 AND score_localisation <= 100))
        ');

        DB::statement('
            ALTER TABLE matchings 
            ADD CONSTRAINT check_score_experience 
            CHECK (score_experience IS NULL OR (score_experience >= 0 AND score_experience <= 100))
        ');

        DB::statement('
            ALTER TABLE matchings 
            ADD CONSTRAINT check_seuil_notification 
            CHECK (seuil_notification >= 0 AND seuil_notification <= 100)
        ');

        // Contrainte CHECK pour les offres (salaire_max >= salaire_min si les deux sont renseignés)
        DB::statement('
            ALTER TABLE offres 
            ADD CONSTRAINT check_salaire 
            CHECK (
                salaire_max IS NULL OR 
                salaire_min IS NULL OR 
                salaire_max >= salaire_min
            )
        ');

        // Contrainte CHECK pour les offres (date_expiration > date_publication si date_expiration est renseignée)
        DB::statement('
            ALTER TABLE offres 
            ADD CONSTRAINT check_date_expiration 
            CHECK (
                date_expiration IS NULL OR 
                date_expiration > date_publication::date
            )
        ');

        // Note: Les index sur matchings sont déjà présents dans la migration principale
        // Les index (offre_id, score_global) et (etudiant_id, score_global) sont déjà créés

        // Note: Les index uniques sont déjà créés dans les migrations principales
        // Cette migration ajoute uniquement les contraintes CHECK manquantes
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Supprimer les contraintes CHECK
        DB::statement('ALTER TABLE matchings DROP CONSTRAINT IF EXISTS check_score_global');
        DB::statement('ALTER TABLE matchings DROP CONSTRAINT IF EXISTS check_score_competences');
        DB::statement('ALTER TABLE matchings DROP CONSTRAINT IF EXISTS check_score_localisation');
        DB::statement('ALTER TABLE matchings DROP CONSTRAINT IF EXISTS check_score_experience');
        DB::statement('ALTER TABLE matchings DROP CONSTRAINT IF EXISTS check_seuil_notification');
        DB::statement('ALTER TABLE offres DROP CONSTRAINT IF EXISTS check_salaire');
        DB::statement('ALTER TABLE offres DROP CONSTRAINT IF EXISTS check_date_expiration');

        // Note: Les index sur matchings sont déjà dans la migration principale
        // On ne les supprime pas ici car ils sont nécessaires
    }

};

