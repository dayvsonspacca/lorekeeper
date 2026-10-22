<?php

declare(strict_types=1);

namespace Lorekeeper\Bot;

use Closure;
use Generator;
use Lorekeeper\Client\Character;
use Lorekeeper\Client\Command\Command;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Script;
use Lorekeeper\Client\Script\Send;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Server\Event\Event;
use Override;
use Psl\DateTime\Timestamp;

/**
 * @implements Script<object>
 */
abstract class BotScript implements Script
{
    private Character $me;

    /** @var Closure(): Timestamp */
    private readonly Closure $clock;

    /** @param null|Closure(): Timestamp $clock */
    public function __construct(?Closure $clock = null)
    {
        $this->clock = $clock ?? Timestamp::monotonic(...);
    }

    /** @return Generator<int, Instruction, null|Event|TimedOut, object> */
    abstract protected function rules(): Generator;

    /** @return Generator<int, Instruction, null|Event|TimedOut, object> */
    #[Override]
    final public function run(Character $me): Generator
    {
        $this->me = $me;

        return $this->rules();
    }

    public function me(): Character
    {
        return $this->me;
    }

    public function send(Command $command): Send
    {
        return new Send($command);
    }

    public function now(): Timestamp
    {
        return ($this->clock)();
    }
}
