<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Bot\Session;

enum OutcomeStatus
{
    case Done;
    case Failed;
    case Cancelled;
    case Disconnected;
}
