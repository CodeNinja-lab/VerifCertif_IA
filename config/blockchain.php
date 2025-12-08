<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Blockchain Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration pour l'intégration blockchain (ancrage des documents)
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Réseau Blockchain
    |--------------------------------------------------------------------------
    |
    | Réseau blockchain utilisé pour l'ancrage
    | Options: ethereum, polygon, bsc, autres
    |
    */

    'network' => env('BLOCKCHAIN_NETWORK', 'ethereum'),

    /*
    |--------------------------------------------------------------------------
    | Adresse du Contrat
    |--------------------------------------------------------------------------
    |
    | Adresse du contrat intelligent déployé sur la blockchain
    |
    */

    'contract_address' => env('BLOCKCHAIN_CONTRACT_ADDRESS', null),

    /*
    |--------------------------------------------------------------------------
    | Clé API
    |--------------------------------------------------------------------------
    |
    | Clé API pour accéder au service blockchain (Etherscan, PolygonScan, etc.)
    |
    */

    'api_key' => env('BLOCKCHAIN_API_KEY', null),

    /*
    |--------------------------------------------------------------------------
    | URL de l'API
    |--------------------------------------------------------------------------
    |
    | URL de base de l'API blockchain
    | Exemples:
    | - Ethereum: https://api.etherscan.io/api
    | - Polygon: https://api.polygonscan.com/api
    | - BSC: https://api.bscscan.com/api
    |
    */

    'api_url' => env('BLOCKCHAIN_API_URL', null),

    /*
    |--------------------------------------------------------------------------
    | Activer l'Ancrage
    |--------------------------------------------------------------------------
    |
    | Si false, l'ancrage blockchain est désactivé (utile pour le développement)
    |
    */

    'enabled' => env('BLOCKCHAIN_ENABLED', false),

];

