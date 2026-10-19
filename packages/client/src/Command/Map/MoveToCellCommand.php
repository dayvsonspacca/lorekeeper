<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Command\Map;

use Lorekeeper\Client\Command\Command;
use Lorekeeper\Core\World\Map\MapPad;
use Lorekeeper\Core\World\Map\MapSpot;
use Lorekeeper\Server\AreaId;
use Override;

final readonly class MoveToCellCommand implements Command
{
    public function __construct(
        private AreaId $area,
        private MapSpot $spot,
    ) {}

    #[Override]
    public function toMessage(): string
    {
        $pad = match ($this->spot->pad) {
            MapPad::Left => 'Left',
            MapPad::Right => 'Right',
            MapPad::Top => 'Top',
            MapPad::Bottom => 'Bottom',
            MapPad::Up => 'Up',
            MapPad::Down => 'Down',
            MapPad::Center => 'Center',
            MapPad::Spawn => 'Spawn',
        };

        return "%xt%zm%moveToCell%{$this->area}%{$this->spot->cell}%{$pad}%";
    }
}
