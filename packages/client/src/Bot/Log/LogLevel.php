<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Bot\Log;

enum LogLevel: string
{
    case Info = 'info';
    case Success = 'success';
    case Warning = 'warning';
    case Error = 'error';
}
