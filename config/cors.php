<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Only /api/* est exposé en cross-origin. L'auth se fait par Bearer token
    | (Sanctum), donc on n'a pas besoin de cookies cross-origin : supports_credentials
    | reste à false pour réduire la surface.
    |
    | Origines autorisées :
    |   - L'app elle-même (APP_URL)
    |   - L'extension Chrome (chrome-extension://*) via pattern regex
    |   - Origines additionnelles via env CORS_EXTRA_ORIGINS (CSV)
    |
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => array_filter(array_merge(
        [env('APP_URL', 'http://localhost')],
        array_map('trim', explode(',', (string) env('CORS_EXTRA_ORIGINS', '')))
    )),

    'allowed_origins_patterns' => [
        // Extension Chrome/Edge/Brave (l'ID n'est pas connu avant publication store)
        '#^chrome-extension://[a-z]{32}$#',
    ],

    'allowed_headers' => ['Accept', 'Content-Type', 'Authorization', 'X-Requested-With'],

    'exposed_headers' => [],

    'max_age' => 3600,

    'supports_credentials' => false,

];
