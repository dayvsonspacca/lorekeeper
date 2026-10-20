<?php

declare(strict_types=1);

namespace Lorekeeper\Core\CharacterClass\Passive;

final readonly class ClassPassive
{
    public function __construct(
        public PassiveName $name,
        public PassiveDescription $description,
    ) {}
}
