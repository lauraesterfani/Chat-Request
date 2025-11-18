<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:3000', 
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    // ESTE ERA O SEU PROBLEMA: Agora expomos cabeçalhos importantes.
    'exposed_headers' => [
        'Authorization', 
        'Content-Type', 
        'X-Requested-With',
    ],

    'max_age' => 0,

    'supports_credentials' => false,

]; 