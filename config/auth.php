<?php

return [
    // ...

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        'api' => [ // Este é o guarda que você está usando
            'driver' => 'jwt',
            'provider' => 'users', // Este nome deve corresponder ao provider abaixo
        ],

        'api-admin' => [
            'driver' => 'jwt',
            'provider' => 'admins',
        ],
    ],

    // ...

    'providers' => [
        'users' => [ // Este provider deve apontar para o modelo Aluno
            'driver' => 'eloquent',
            'model' => App\Models\Aluno::class,
        ],

        'admins' => [
            'driver' => 'eloquent',
            'model' => App\Models\Admin::class,
        ],
    ],

    // ...
];