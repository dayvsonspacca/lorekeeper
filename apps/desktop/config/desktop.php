<?php

declare(strict_types=1);

return [
    'shell' => env('LOREKEEPER_SHELL'),
    'shell_secret' => env('LOREKEEPER_SHELL_SECRET'),
    'shell_pid' => env('LOREKEEPER_SHELL_PID'),
    'downloads' => env('LOREKEEPER_DOWNLOADS'),
    'version' => env('LOREKEEPER_VERSION'),
    'releases' => 'https://api.github.com/repos/dayvsonspacca/lorekeeper/releases',
];
