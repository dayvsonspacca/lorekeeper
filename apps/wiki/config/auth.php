<?php

declare(strict_types=1);

use Lorekeeper\Apps\Wiki\Models\User;

return [
    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => User::class,
        ],
    ],
];
