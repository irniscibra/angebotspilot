<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Quasar Frontend läuft auf localhost:9000 (dev) – muss erlaubt werden.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:9000',    // Quasar Dev Server
        'http://localhost:9200',    // Quasar alternative port
        'http://127.0.0.1:9000',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,  // Wichtig für Sanctum Cookies

];