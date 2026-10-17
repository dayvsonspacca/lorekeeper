<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Bot\Task;

use Lorekeeper\Client\Bot\Character;
use Lorekeeper\Client\Bot\Script\LevelUpScript;
use Lorekeeper\Client\Script\Script;
use Lorekeeper\Core\Player\PlayerLevel;
use Override;

final readonly class LevelUpTask implements Task
{
    public function __construct(
        public PlayerLevel $target,
    ) {}

    #[Override]
    public function title(): string
    {
        return "Level up to {$this->target->value}";
    }

    #[Override]
    public function script(Character $me): Script
    {
        return new LevelUpScript($me, $this->target);
    }
}
