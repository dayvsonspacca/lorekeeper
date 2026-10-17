<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Command;

use InvalidArgumentException;
use Lorekeeper\Core\CharacterClass\Skill\SkillSlot;
use Lorekeeper\Core\World\Monster\MonsterSpawnId;
use Lorekeeper\Server\AreaId;
use Override;

final readonly class AttackMonsterCommand implements Command
{
    /** @throws InvalidArgumentException */
    public function __construct(
        private AreaId $area,
        private SkillSlot $skill,
        private MonsterSpawnId $monster,
    ) {
        if ($skill === SkillSlot::Potion) {
            throw new InvalidArgumentException('A potion is not an attack.');
        }
    }

    #[Override]
    public function toMessage(): string
    {
        $skill = match ($this->skill) {
            SkillSlot::AutoAttack => 'aa',
            SkillSlot::First => 'a1',
            SkillSlot::Second => 'a2',
            SkillSlot::Third => 'a3',
            SkillSlot::Fourth => 'a4',
            SkillSlot::Potion => 'i1',
        };

        return "%xt%zm%gar%{$this->area}%0%{$skill}>m:{$this->monster}%wvz%";
    }
}
