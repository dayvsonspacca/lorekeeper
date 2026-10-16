<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Bot\Session;

enum JobStatus: string
{
    case Done = 'done';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
}
