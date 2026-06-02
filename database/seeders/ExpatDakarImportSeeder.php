<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ExpatDakarImportSeeder extends Seeder
{
    public function run(): void
    {
        $importConfig = config('app_constants.expat_dakar_import');
        $password = env('EXPAT_DAKAR_IMPORT_PASSWORD', Str::random(24));

        $user = User::updateOrCreate(
            ['email' => $importConfig['email']],
            [
                'prenom' => 'Expat',
                'nom' => 'Dakar Import',
                'nom_entreprise' => $importConfig['name'],
                'mot_de_passe_hash' => Hash::make($password),
                'role' => 'recruteur',
                'telephone' => $importConfig['phone'],
                'langue' => 'fr',
                'is_active' => true,
            ]
        );

        $tokenPath = 'secrets/expat-dakar-import-token.txt';

        if (!Storage::exists($tokenPath)) {
            $token = $user->createToken('expat-dakar-import')->plainTextToken;
            Storage::put($tokenPath, $token . PHP_EOL);
        }
    }
}