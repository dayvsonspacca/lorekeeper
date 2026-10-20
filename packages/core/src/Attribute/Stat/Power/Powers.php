<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Attribute\Stat\Power;

final readonly class Powers
{
    public function __construct(
        public AttackPower $attack = new AttackPower(0),
        public SpellPower $spell = new SpellPower(0),
    ) {}
}
