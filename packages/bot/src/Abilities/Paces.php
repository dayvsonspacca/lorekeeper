<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Abilities;

use Generator;
use Lorekeeper\Client\Script\Await;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Pause;
use Lorekeeper\Client\Script\Send;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Server\Event\ActionTooSoonEvent;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\TooSoonAfterCombatEvent;
use Psl\DateTime\Duration;

trait Paces
{
    private const int TRIES_WHEN_TOO_SOON = 4;

    /** @return Generator<int, Instruction, null|Event|TimedOut, null|Event|TimedOut> */
    protected function paced(Send $send): Generator
    {
        $await = $send->await;
        if ($await === null) {
            return yield $send;
        }

        $watching = new Send($send->command, new Await([
            ...$await->events,
            ActionTooSoonEvent::class,
            TooSoonAfterCombatEvent::class,
        ], $await->timeout));
        for ($try = 1;; $try++) {
            $answer = yield $watching;
            $tooSoon = $answer instanceof ActionTooSoonEvent || $answer instanceof TooSoonAfterCombatEvent;
            if (!$tooSoon || $try >= self::TRIES_WHEN_TOO_SOON) {
                return $answer;
            }

            yield new Pause(Duration::seconds($answer instanceof TooSoonAfterCombatEvent ? 2 : 1));
        }
    }
}
