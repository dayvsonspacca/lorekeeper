<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Bots;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Override;

enum BotStatus: string implements HasLabel, HasColor, HasIcon
{
    case Offline = 'offline';
    case Starting = 'starting';
    case Online = 'online';
    case Stopping = 'stopping';
    case Disconnected = 'disconnected';
    case Failed = 'failed';

    public function isActive(): bool
    {
        return match ($this) {
            self::Starting, self::Online, self::Stopping => true,
            self::Offline, self::Disconnected, self::Failed => false,
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
            self::Online => 'success',
            self::Starting, self::Stopping => 'info',
            self::Disconnected => 'warning',
            self::Failed => 'danger',
            self::Offline => 'gray',
        };
    }

    #[Override]
    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Starting => Heroicon::OutlinedClock,
            self::Online => Heroicon::OutlinedPlayCircle,
            self::Stopping => Heroicon::OutlinedPauseCircle,
            self::Offline => Heroicon::OutlinedStopCircle,
            self::Disconnected => Heroicon::OutlinedSignalSlash,
            self::Failed => Heroicon::OutlinedExclamationTriangle,
        };
    }
}
