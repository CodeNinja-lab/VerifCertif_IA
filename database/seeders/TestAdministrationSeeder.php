<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Administration;

class TestAdministrationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Administration::create([
            'nom' => 'Université de Test',
            'type_administration' => 'universite',
            'pays' => 'France',
            'ville' => 'Paris',
            'email_contact' => 'contact@univ-test.fr',
            'telephone_contact' => '+33123456789',
            'cle_publique_ed25519' => 'temp_will_be_replaced_by_observer',
            'statut' => 'verifie',
        ]);

        $this->command->info('Administration de test créée avec succès !');
    }
}
