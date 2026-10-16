<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Bot\Task;

use Lorekeeper\Client\Bot\Position;
use Lorekeeper\Client\Script\Script;
use Lorekeeper\Core\Player\PlayerName;

/**
 * Something a bot is asked to do once it is in the game. The script is built when the task starts, from where the
 * bot is at that moment; it ends with a Done, or with whatever stopped it.
 */
interface Task
{
    public function title(): string;

    /** @return Script<object> */
    public function script(PlayerName $player, Position $here): Script;
}
