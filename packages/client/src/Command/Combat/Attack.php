<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Command\Combat;

use InvalidArgumentException;
use Lorekeeper\Client\Command\Command;
use Lorekeeper\Core\Game\Combat\Traits\Targetable;
use Lorekeeper\Core\Item\Equipment\CharacterClass\Skill\SkillSlot;

interface Attack extends Command
{
    public function target(): Targetable;

    /** @throws InvalidArgumentException */
    public function with(SkillSlot $skill): static;

    /** @throws InvalidArgumentException */
    public function at(Targetable $target): static;
}
