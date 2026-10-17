<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Bot\Task;

use Lorekeeper\Client\Bot\Position;
use Lorekeeper\Client\Script\Script;
use Lorekeeper\Core\Player\PlayerName;

interface Task
{
    public function title(): string;

    /** @return Script<object> */
    public function script(PlayerName $player, Position $here): Script;
}
