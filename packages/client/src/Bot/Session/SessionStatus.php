<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Bot\Session;

enum SessionStatus: string
{
    case Stopped = 'stopped';
    case Disconnected = 'disconnected';
    case Failed = 'failed';
}
