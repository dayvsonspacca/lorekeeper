<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Bots;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Override;

enum RunStatus: string implements HasLabel, HasColor, HasIcon
{
    case Pending = 'pending';
    case Running = 'running';
    case Stopping = 'stopping';
    case Stopped = 'stopped';
    case Disconnected = 'disconnected';
    case Failed = 'failed';

    public function isActive(): bool
    {
        return match ($this) {
            self::Pending, self::Running, self::Stopping => true,
            self::Stopped, self::Disconnected, self::Failed => false,
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
            self::Stopped => 'gray',
        };
    }

    #[Override]
    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Pending => Heroicon::OutlinedClock,
            self::Running => Heroicon::OutlinedPlayCircle,
            self::Stopping => Heroicon::OutlinedPauseCircle,
            self::Stopped => Heroicon::OutlinedStopCircle,
            self::Disconnected => Heroicon::OutlinedSignalSlash,
            self::Failed => Heroicon::OutlinedExclamationTriangle,
        };
    }
}
