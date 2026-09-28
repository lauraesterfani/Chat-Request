<?php

return [
    // Keep disabled until the IFPE authorizes a provider, protocol and scopes.
    'enabled' => (bool) env('INSTITUTIONAL_IDENTITY_ENABLED', false),
    'issuer' => env('INSTITUTIONAL_IDENTITY_ISSUER'),
    'base_url' => env('INSTITUTIONAL_IDENTITY_BASE_URL'),
];
