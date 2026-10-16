<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Bots;

enum RunStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Stopping = 'stopping';
    case Stopped = 'stopped';
    case Finished = 'finished';
    case Disconnected = 'disconnected';
    case Failed = 'failed';

    public function isActive(): bool
    {
        return match ($this) {
            self::Pending, self::Running, self::Stopping => true,
            self::Stopped, self::Finished, self::Disconnected, self::Failed => false,
        };
    }
}
