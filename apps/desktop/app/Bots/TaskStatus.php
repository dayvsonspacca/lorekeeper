<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Bots;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Override;

enum TaskStatus: string implements HasLabel, HasColor, HasIcon
{
    case Pending = 'pending';
    case Running = 'running';
    case Cancelling = 'cancelling';
    case Done = 'done';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function isActive(): bool
    {
        return match ($this) {
            self::Pending, self::Running, self::Cancelling => true,
            self::Done, self::Failed, self::Cancelled => false,
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
            self::Pending, self::Cancelling => 'info',
            self::Done => 'gray',
            self::Failed => 'danger',
            self::Cancelled => 'warning',
        };
    }

    #[Override]
    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Pending => Heroicon::OutlinedClock,
            self::Running => Heroicon::OutlinedPlayCircle,
            self::Cancelling => Heroicon::OutlinedPauseCircle,
            self::Done => Heroicon::OutlinedCheckCircle,
            self::Failed => Heroicon::OutlinedExclamationTriangle,
            self::Cancelled => Heroicon::OutlinedXCircle,
        };
    }
}
