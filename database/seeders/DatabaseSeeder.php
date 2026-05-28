<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Administration;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // Compte administrateur principal UCAD (toujours mis à jour avec le bon mot de passe)
        User::updateOrCreate(
            ['email' => 'admin@ucad.edu.sn'],
            [
                'prenom' => 'Admin',
                'nom' => 'UCAD',
                'mot_de_passe_hash' => Hash::make('@dministrateur2025'),
                'role' => 'admin',
                'telephone' => '+221000000000',
                'langue' => 'fr',
                'is_active' => true,
            ]
        );

        // Créer l'administration UCAD associée au compte admin
        Administration::firstOrCreate(
            [
                'email_contact' => 'admin@ucad.edu.sn',
            ],
            [
                'nom' => 'Université Cheikh Anta Diop (UCAD)',
                'type_administration' => 'universite',
                'pays' => 'Sénégal',
                'ville' => 'Dakar',
                'adresse' => 'Avenue Cheikh Anta Diop, BP 5005, Dakar-Fann',
                'statut' => 'verifie',
                'cle_publique_ed25519' => 'temp_will_be_replaced_by_observer', // Sera remplacé par l'observer
            ]
        );

        $this->call([
            TestAdministrationSeeder::class,
            ExpatDakarImportSeeder::class,
        ]);
    }
}

