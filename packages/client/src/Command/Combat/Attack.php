<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Command\Combat;

use InvalidArgumentException;
use Lorekeeper\Client\Command\Command;
use Lorekeeper\Core\CharacterClass\Skill\SkillSlot;
use Lorekeeper\Core\Combat\Targetable;

interface Attack extends Command
{
    public function target(): Targetable;

    /** @throws InvalidArgumentException */
    public function with(SkillSlot $skill): static;

    /** @throws InvalidArgumentException */
    public function at(Targetable $target): static;
}
