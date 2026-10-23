<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Game\Attribute\Stat\Power;

final class Powers
{
    public function __construct(
        public private(set) AttackPower $attack = new AttackPower(0),
        public private(set) SpellPower $spell = new SpellPower(0),
    ) {}

    /** @mago-expect analysis:match-not-exhaustive */
    public function update(Power $stat): void
    {
        match (true) {
            $stat instanceof AttackPower => $this->attack = $stat,
            $stat instanceof SpellPower => $this->spell = $stat,
        };
    }
}
