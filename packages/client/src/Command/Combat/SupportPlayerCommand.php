<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Command\Combat;

use InvalidArgumentException;
use Lorekeeper\Client\Command\Command;
use Lorekeeper\Core\Item\Equipment\CharacterClass\Skill\SkillSlot;
use Lorekeeper\Core\Player\PlayerSpawnId;
use Lorekeeper\Server\AreaId;
use Override;

final readonly class SupportPlayerCommand implements Command
{
    private string $code;

    /** @throws InvalidArgumentException */
    public function __construct(
        private AreaId $area,
        SkillSlot $skill,
        private PlayerSpawnId $player,
    ) {
        $this->code = match ($skill) {
            SkillSlot::First => 'a1',
            SkillSlot::Second => 'a2',
            SkillSlot::Third => 'a3',
            SkillSlot::Fourth => 'a4',
            SkillSlot::AutoAttack, SkillSlot::Potion => throw new InvalidArgumentException(
                'Only a class skill can support a player.',
            ),
        };
    }

    #[Override]
    public function toMessage(): string
    {
        return "%xt%zm%gar%{$this->area}%0%{$this->code}>p:{$this->player}%wvz%";
    }
}
