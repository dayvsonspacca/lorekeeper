<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Bots;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Override;

enum RunStatus: string implements HasLabel, HasColor
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

    #[Override]
    public function getLabel(): string
    {
        return ucfirst($this->value);
    }

    #[Override]
    public function getColor(): string
    {
        return match ($this) {
            self::Running => 'success',
            self::Pending, self::Stopping => 'info',
            self::Disconnected => 'warning',
            self::Failed => 'danger',
            self::Stopped, self::Finished => 'gray',
        };
    }
}
