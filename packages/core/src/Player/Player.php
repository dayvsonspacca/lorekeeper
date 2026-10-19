<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Player;

use Lorekeeper\Core\Attribute\Vitals;

final readonly class Player
{
    /**
     * @param list<PlayerSkill> $skills
     *
     * @mago-expect lint:excessive-parameter-list
     */
    public function __construct(
        public PlayerName $name,
        public PlayerLevel $level,
        public Vitals $vitals,
        public PlayerClass $class,
        public array $skills,
        public PlayerInventory $inventory,
    ) {}
}
