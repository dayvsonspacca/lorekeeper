<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Abilities\Moves;

use Generator;
use Lorekeeper\Client\Command\Map\JoinMapCommand;
use Lorekeeper\Client\Script\Await;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Core\Combat\CombatState;
use Lorekeeper\Core\World\Map\MapCell;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Map\MapPad;
use Lorekeeper\Core\World\Map\MapSpot;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Map\MapJoins;
use Lorekeeper\Server\Event\Map\MapRefusals;
use Lorekeeper\Server\Event\Player\PlayerStateUpdatedEvent;
use Psl\DateTime\Duration;
use Psl\Option;
use RuntimeException;

trait Joins
{
    use Jumps;

    private const int SECONDS_TO_LEAVE_COMBAT = 10;

    /**
     * @throws RuntimeException
     *
     * @return Generator<int, Instruction, null|Event|TimedOut, void>
     */
    protected function join(MapName $map): Generator
    {
        if ($this->me()->location()->map->name->value === $map->value) {
            return;
        }

        yield from $this->leaveCombat();

        /** @var Event|TimedOut $joined */
        $joined = yield $this->send(new JoinMapCommand($this->me()->player()->name, $map, Option\none()))->awaits(
            Duration::seconds(15),
            MapJoins::class,
            MapRefusals::class,
        );

        if (!MapJoins::contains($joined)) {
            throw new RuntimeException("Could not join {$map->value}: " . self::refusal($joined));
        }
    }

    /** @return Generator<int, Instruction, null|Event|TimedOut, void> */
    private function leaveCombat(): Generator
    {
        if ($this->me()->state() !== CombatState::Fighting) {
            return;
        }

        yield from $this->jump(new MapSpot(new MapCell('Enter'), MapPad::Spawn));
        for ($second = 0; $second < self::SECONDS_TO_LEAVE_COMBAT; $second++) {
            if ($this->me()->state() !== CombatState::Fighting) {
                return;
            }

            yield new Await([PlayerStateUpdatedEvent::class], Duration::seconds(1));
        }
    }

    private static function refusal(mixed $answer): string
    {
        return match (true) {
            $answer instanceof Event => $answer->summary(),
            $answer instanceof TimedOut => 'no answer',
            default => 'the server refused',
        };
    }
}
