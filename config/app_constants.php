<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Constantes de l'application
    |--------------------------------------------------------------------------
    */

    'roles' => [
        'etudiant' => 'etudiant',
        'recruteur' => 'recruteur',
        'admin' => 'admin',
    ],

    'langues' => [
        'fr' => 'Français',
        'en' => 'English',
        'es' => 'Español',
        'ar' => 'العربية',
    ],

    'statuts_document' => [
        'emis' => 'emis',
        'revoque' => 'revoque',
        'expire' => 'expire',
    ],

    'statuts_matching' => [
        'en_attente' => 'en_attente',
        'valide' => 'valide',
        'rejete' => 'rejete',
    ],

    'token' => [
        'name' => 'auth_token',
        'expiration' => 60 * 24 * 30, // 30 jours en minutes
    ],

    'rate_limiting' => [
        'auth' => 5, // 5 tentatives
        'password_reset' => 3, // 3 tentatives
    ],

    'expat_dakar_import' => [
        'email' => env('EXPAT_DAKAR_IMPORT_EMAIL', 'expat-dakar-import@vericertis.sn'),
        'name' => env('EXPAT_DAKAR_IMPORT_NAME', 'Expat Dakar Import'),
        'phone' => env('EXPAT_DAKAR_IMPORT_PHONE', '+221000000001'),
    ],
];
