<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Combat;

use Lorekeeper\Core\Attribute\Health;
use Lorekeeper\Core\World\Monster\MonsterSpawnId;
use Override;
use Psl\Option;

/**
 * The health of one or more monsters in the room changed.
 */
final readonly class MonstersHealthChangedEvent extends CombatRoundEvent
{
    /** @param non-empty-list<MonsterHealth> $monsters */
    public function __construct(
        public array $monsters,
    ) {}

    #[Override]
    protected static function read(array $monsters, array $players): Option\Option
    {
        $changed = [];
        foreach ($monsters as $id => $monster) {
            if (!array_key_exists('intHP', $monster)) {
                continue;
            }

            $changed[] = new MonsterHealth(new MonsterSpawnId($id), new Health($monster['intHP']));
        }

        return $changed === [] ? Option\none() : Option\some(new static($changed));
    }

    #[Override]
    public function summary(): string
    {
        return implode(', ', array_map(
            static fn(MonsterHealth $monster): string => "Monster {$monster->monster->value} has {$monster->health->value} health",
            $this->monsters,
        ));
    }
}
