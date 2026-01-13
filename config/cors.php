<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Ici, vous pouvez configurer les paramètres CORS de votre application.
    | Pour le développement, on autorise l'origine Next.js (http://localhost:3000)
    | ainsi que les en-têtes nécessaires (Authorization, Content-Type, etc.).
    |
    */

    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
    ],

    // Autoriser explicitement votre front Next.js en développement et production
    'allowed_origins' => env('CORS_ALLOWED_ORIGINS') 
        ? array_map('trim', explode(',', env('CORS_ALLOWED_ORIGINS')))
        : ['*'],
    
    'allowed_origins_patterns' => [
        env('APP_ENV') === 'local' ? '*' : null,
    ],

    'allowed_methods' => ['*'],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['*'],

    'max_age' => 86400,

    'supports_credentials' => true,
];