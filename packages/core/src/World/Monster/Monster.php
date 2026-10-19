<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Monster;

final readonly class Monster
{
    public function __construct(
        public MonsterId $id,
        public MonsterName $name,
        public MonsterLevel $level,
        public MonsterRace $race,
        public MonsterAppearance $appearance,
    ) {}
}
