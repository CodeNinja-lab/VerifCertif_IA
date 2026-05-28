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
        $password = env('EXPAT_DAKAR_IMPORT_PASSWORD', Str::random(24));

        $user = User::updateOrCreate(
            ['email' => env('EXPAT_DAKAR_IMPORT_EMAIL', 'expat-dakar-import@vericertis.sn')],
            [
                'prenom' => 'Expat',
                'nom' => 'Dakar Import',
                'nom_entreprise' => 'Expat Dakar Import',
                'mot_de_passe_hash' => Hash::make($password),
                'role' => 'recruteur',
                'telephone' => env('EXPAT_DAKAR_IMPORT_PHONE', '+221000000001'),
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