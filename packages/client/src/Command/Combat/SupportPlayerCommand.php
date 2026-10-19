<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Command\Combat;

use InvalidArgumentException;
use Lorekeeper\Client\Command\Command;
use Lorekeeper\Core\CharacterClass\Skill\SkillSlot;
use Lorekeeper\Server\AreaId;
use Lorekeeper\Server\SocketId;
use Override;

final readonly class SupportPlayerCommand implements Command
{
    /** @throws InvalidArgumentException */
    public function __construct(
        private AreaId $area,
        private SkillSlot $skill,
        private SocketId $player,
    ) {
        if ($skill === SkillSlot::AutoAttack || $skill === SkillSlot::Potion) {
            throw new InvalidArgumentException('Only a class skill can support a player.');
        }
    }

    #[Override]
    public function toMessage(): string
    {
        $skill = match ($this->skill) {
            SkillSlot::First => 'a1',
            SkillSlot::Second => 'a2',
            SkillSlot::Third => 'a3',
            SkillSlot::Fourth => 'a4',
            SkillSlot::AutoAttack, SkillSlot::Potion => throw new InvalidArgumentException(
                'Only a class skill can support a player.',
            ),
        };

        return "%xt%zm%gar%{$this->area}%0%{$skill}>p:{$this->player}%wvz%";
    }
}
