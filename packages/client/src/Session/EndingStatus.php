<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Session;

enum EndingStatus: string
{
    case Stopped = 'stopped';
    case Disconnected = 'disconnected';
    case Failed = 'failed';
}
