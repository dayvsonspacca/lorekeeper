<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Bots;

enum LogLevel: string
{
    case Info = 'info';
    case Success = 'success';
    case Warning = 'warning';
    case Error = 'error';
}
