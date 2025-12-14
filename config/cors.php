<?php

return [

   'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    
    // Mude de ['*'] para incluir explicitamente o seu frontend se estiver com problemas
    'allowed_origins' => ['http://localhost:3000', 'http://127.0.0.1:3000'],
    
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true, // Importante se usar Cookies/Sanctum

]; 