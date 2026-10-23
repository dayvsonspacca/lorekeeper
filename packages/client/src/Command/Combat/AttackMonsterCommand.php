<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Command\Combat;

use InvalidArgumentException;
use Lorekeeper\Core\Game\Combat\Traits\Targetable;
use Lorekeeper\Core\Item\Equipment\CharacterClass\Skill\SkillSlot;
use Lorekeeper\Core\World\Monster\MonsterSpawnId;
use Lorekeeper\Server\AreaId;
use Override;

final readonly class AttackMonsterCommand implements Attack
{
    private string $code;

    /** @throws InvalidArgumentException */
    public function __construct(
        private AreaId $area,
        private SkillSlot $skill,
        private MonsterSpawnId $monster,
    ) {
        $this->code = match ($skill) {
            SkillSlot::AutoAttack => 'aa',
            SkillSlot::First => 'a1',
            SkillSlot::Second => 'a2',
            SkillSlot::Third => 'a3',
            SkillSlot::Fourth => 'a4',
            SkillSlot::Potion => throw new InvalidArgumentException('A potion is not an attack.'),
        };
    }

    #[Override]
    public function target(): MonsterSpawnId
    {
        return $this->monster;
    }

    #[Override]
    public function with(SkillSlot $skill): static
    {
        return new static($this->area, $skill, $this->monster);
    }

    #[Override]
    public function at(Targetable $target): static
    {
        if (!$target instanceof MonsterSpawnId) {
            throw new InvalidArgumentException('A monster attack can only aim at a monster.');
        }

        return new static($this->area, $this->skill, $target);
    }

    #[Override]
    public function toMessage(): string
    {
        return "%xt%zm%gar%{$this->area}%0%{$this->code}>m:{$this->monster}%wvz%";
    }
}
