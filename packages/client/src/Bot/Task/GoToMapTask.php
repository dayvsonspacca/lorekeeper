<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Bot\Task;

use Lorekeeper\Client\Bot\Position;
use Lorekeeper\Client\Bot\Script\GoToMapScript;
use Lorekeeper\Client\Script\Script;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\World\Map\MapName;
use Override;

final readonly class GoToMapTask implements Task
{
    public function __construct(
        public MapName $map,
    ) {}

    #[Override]
    public function title(): string
    {
        return "Go to {$this->map->value}";
    }

    #[Override]
    public function script(PlayerName $player, Position $here): Script
    {
        return new GoToMapScript($player, $here, $this->map);
    }
}
