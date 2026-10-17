<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Bot\Task;

use Lorekeeper\Client\Bot\Character;
use Lorekeeper\Client\Script\Script;

interface Task
{
    public function title(): string;

    /** @return Script<object> */
    public function script(Character $me): Script;
}
