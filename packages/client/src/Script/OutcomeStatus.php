<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Script;

enum OutcomeStatus
{
    case Done;
    case Failed;
    case Cancelled;
    case Disconnected;
}
