<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Bot\Script;

use Closure;
use Generator;
use Lorekeeper\Client\Bot\Character;
use Lorekeeper\Client\Command\Combat\Attack;
use Lorekeeper\Client\Command\Command;
use Lorekeeper\Client\Command\Map\JoinMapCommand;
use Lorekeeper\Client\Command\Map\MoveToCellCommand;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Pause;
use Lorekeeper\Client\Script\Script;
use Lorekeeper\Client\Script\Send;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Core\World\Map\Cell;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Map\Pad;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Map\MapJoins;
use Lorekeeper\Server\Event\Map\MapRefusals;
use Override;
use Psl\DateTime\Duration;
use Psl\DateTime\Timestamp;
use Psl\Option;
use RuntimeException;

/** @implements Script<object> */
abstract readonly class BotScript implements Script
{
    /** @var Closure(): Timestamp */
    private Closure $clock;

    /** @param null|Closure(): Timestamp $clock */
    public function __construct(
        protected Character $me,
        ?Closure $clock = null,
    ) {
        $this->clock = $clock ?? Timestamp::monotonic(...);
    }

    /** @return Generator<int, Instruction, null|Event|TimedOut, object> */
    abstract protected function play(): Generator;

    /** @return Generator<int, Instruction, null|Event|TimedOut, object> */
    #[Override]
    final public function run(): Generator
    {
        return $this->play();
    }

    /**
     * @throws RuntimeException
     *
     * @return Generator<int, Instruction, null|Event|TimedOut, void>
     */
    protected function join(MapName $map): Generator
    {
        if ($this->me->position()->map->value === $map->value) {
            return;
        }

        /** @var Event|TimedOut $joined */
        $joined = yield $this->send(new JoinMapCommand($this->me->name(), $map, Option\none()))->awaits(
            Duration::seconds(15),
            MapJoins::class,
            MapRefusals::class,
        );

        if (!MapJoins::contains($joined)) {
            throw new RuntimeException("Could not join {$map->value}: " . self::why($joined));
        }
    }

    protected function send(Command $command): Send
    {
        return new Send($command);
    }

    /** @return Generator<int, Instruction, null|Event|TimedOut, void> */
    protected function jump(Cell $cell, Pad $pad): Generator
    {
        yield new Send(new MoveToCellCommand($this->me->position()->area, $cell, $pad));
        yield new Pause(Duration::seconds(1));
    }

    /**
     * @param Closure(): bool $until
     *
     * @throws RuntimeException
     *
     * @return Generator<int, Instruction, null|Event|TimedOut, void>
     */
    protected function kill(Cell $cell, Pad $pad, Attack $attack, Closure $until): Generator
    {
        yield from $this->jump($cell, $pad);
        yield from new KillScript($this->me, $cell, $pad, $attack, $until, $this->clock)->run();
    }

    private static function why(mixed $answer): string
    {
        return match (true) {
            $answer instanceof Event => $answer->summary(),
            $answer instanceof TimedOut => 'no answer',
            default => 'the server refused',
        };
    }
}
