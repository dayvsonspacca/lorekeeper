<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Abilities\Moves;

use Generator;
use Lorekeeper\Client\Character;
use Lorekeeper\Client\Command\Command;
use Lorekeeper\Client\Command\Map\JoinMapCommand;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Send;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Map\MapJoins;
use Lorekeeper\Server\Event\Map\MapRefusals;
use Psl\DateTime\Duration;
use Psl\Option;
use RuntimeException;

trait Joins
{
    abstract protected function me(): Character;

    abstract protected function send(Command $command): Send;

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

    private static function refusal(mixed $answer): string
    {
        return match (true) {
            $answer instanceof Event => $answer->summary(),
            $answer instanceof TimedOut => 'no answer',
            default => 'the server refused',
        };
    }
}
