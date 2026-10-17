<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Command;

use Lorekeeper\Core\World\Map\Cell;
use Lorekeeper\Core\World\Map\Pad;
use Lorekeeper\Server\AreaId;
use Override;

final readonly class MoveToCellCommand implements Command
{
    public function __construct(
        private AreaId $area,
        private Cell $cell,
        private Pad $pad,
    ) {}

    #[Override]
    public function toMessage(): string
    {
        $pad = match ($this->pad) {
            Pad::Left => 'Left',
            Pad::Right => 'Right',
            Pad::Top => 'Top',
            Pad::Bottom => 'Bottom',
            Pad::Up => 'Up',
            Pad::Down => 'Down',
            Pad::Center => 'Center',
            Pad::Spawn => 'Spawn',
        };

        return "%xt%zm%moveToCell%{$this->area}%{$this->cell}%{$pad}%";
    }
}
