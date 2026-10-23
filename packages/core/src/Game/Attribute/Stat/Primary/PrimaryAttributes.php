<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Game\Attribute\Stat\Primary;

final class PrimaryAttributes
{
    /** @mago-expect lint:excessive-parameter-list */
    public function __construct(
        public private(set) Strength $strength = new Strength(0),
        public private(set) Intellect $intellect = new Intellect(0),
        public private(set) Dexterity $dexterity = new Dexterity(0),
        public private(set) Endurance $endurance = new Endurance(0),
        public private(set) Wisdom $wisdom = new Wisdom(0),
        public private(set) Luck $luck = new Luck(0),
    ) {}

    /** @mago-expect analysis:match-not-exhaustive */
    public function update(PrimaryAttribute $stat): void
    {
        match (true) {
            $stat instanceof Strength => $this->strength = $stat,
            $stat instanceof Intellect => $this->intellect = $stat,
            $stat instanceof Dexterity => $this->dexterity = $stat,
            $stat instanceof Endurance => $this->endurance = $stat,
            $stat instanceof Wisdom => $this->wisdom = $stat,
            $stat instanceof Luck => $this->luck = $stat,
        };
    }
}
