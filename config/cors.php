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

    // Autoriser explicitement votre front Next.js en développement
    'allowed_origins' => [
        'http://localhost:3000',
    ],

    // Vous pouvez mettre ['*'] si vous voulez tout autoriser en dev
    // 'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_methods' => ['*'], // GET, POST, PUT, DELETE, OPTIONS, ...

    'allowed_headers' => ['*'], // Authorization, Content-Type, X-Requested-With, etc.

    'exposed_headers' => [],

    'max_age' => 0,

    // Si vous envoyez le token dans l'en-tête Authorization, vous pouvez laisser à false.
    // Mettez à true seulement si vous utilisez des cookies cross-site.
    'supports_credentials' => false,
];


