<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Bot\Script;

use Closure;
use Generator;
use Lorekeeper\Client\Bot\Character;
use Lorekeeper\Client\Bot\Log\EventSummary;
use Lorekeeper\Client\Command\Attack;
use Lorekeeper\Client\Command\MoveToCellCommand;
use Lorekeeper\Client\Script\Game\JoinMapScript;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Pause;
use Lorekeeper\Client\Script\Script;
use Lorekeeper\Client\Script\Send;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Core\World\Map\Cell;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Map\Pad;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Map\AlreadyInMapEvent;
use Lorekeeper\Server\Event\Map\MovedToMapEvent;
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

        $joining = new JoinMapScript($this->me->name(), $map, Option\none())->run();
        yield from $joining;
        $joined = $joining->getReturn();

        if (!$joined instanceof MovedToMapEvent && !$joined instanceof AlreadyInMapEvent) {
            throw new RuntimeException("Could not join {$map->value}: " . self::why($joined));
        }
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
            $answer instanceof Event => EventSummary::of($answer),
            $answer instanceof TimedOut => 'no answer',
            default => 'the server refused',
        };
    }
}
