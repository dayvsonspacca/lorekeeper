<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Combat;

use Lorekeeper\Core\World\Monster\MonsterSpawnId;
use Override;
use Psl\Option;

/**
 * One or more monsters in the room died.
 */
final readonly class MonstersDiedEvent extends CombatRoundEvent
{
    /** @param non-empty-list<MonsterSpawnId> $monsters */
    public function __construct(
        public array $monsters,
    ) {}

    #[Override]
    protected static function read(array $monsters, array $players): Option\Option
    {
        $died = [];
        foreach ($monsters as $id => $monster) {
            if (($monster['intState'] ?? null) !== 0) {
                continue;
            }

            $died[] = new MonsterSpawnId($id);
        }

        return $died === [] ? Option\none() : Option\some(new static($died));
    }

    #[Override]
    public function summary(): string
    {
        return (
            'Monster '
            . implode(', ', array_map(
                static fn(MonsterSpawnId $monster): string => (string) $monster->value,
                $this->monsters,
            ))
            . ' died'
        );
    }
}
