<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Etsy API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Etsy Open API v3
    | Documentation: https://developer.etsy.com/documentation
    |
    */

    'client_id' => env('ETSY_CLIENT_ID', ''),
    'client_secret' => env('ETSY_CLIENT_SECRET', ''),
    'redirect_uri' => env('ETSY_REDIRECT_URI', env('APP_URL') . '/etsy/callback'),

    /*
    |--------------------------------------------------------------------------
    | API Endpoints
    |--------------------------------------------------------------------------
    */

    'api_url' => 'https://openapi.etsy.com/v3',
    'oauth_url' => 'https://www.etsy.com/oauth',

    /*
    |--------------------------------------------------------------------------
    | OAuth Scopes
    |--------------------------------------------------------------------------
    |
    | Available scopes:
    | - listings_r: Read listings
    | - listings_w: Write listings
    | - shops_r: Read shop info
    | - shops_w: Write shop info
    | - transactions_r: Read transactions
    | - email_r: Read email
    */

    'scopes' => [
        'listings_r',
        'listings_w',
        'listings_d',
        'shops_r',
        'shops_w',
        'transactions_r',
        'transactions_w',
        'email_r',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Etsy allows 10 requests per second per application
    |
    */

    'rate_limit' => [
        'max_requests' => 10,
        'per_seconds' => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Expiration
    |--------------------------------------------------------------------------
    |
    | Etsy access tokens expire after 3600 seconds (1 hour)
    | Refresh tokens are valid indefinitely until revoked
    |
    */

    'token_expiration' => 3600, // 1 hour in seconds

];
