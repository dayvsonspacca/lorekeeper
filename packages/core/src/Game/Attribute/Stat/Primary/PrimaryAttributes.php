<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Game\Attribute\Stat\Primary;

final readonly class PrimaryAttributes
{
    /** @mago-expect lint:excessive-parameter-list */
    public function __construct(
        public Strength $strength = new Strength(0),
        public Intellect $intellect = new Intellect(0),
        public Dexterity $dexterity = new Dexterity(0),
        public Endurance $endurance = new Endurance(0),
        public Wisdom $wisdom = new Wisdom(0),
        public Luck $luck = new Luck(0),
    ) {}
}
