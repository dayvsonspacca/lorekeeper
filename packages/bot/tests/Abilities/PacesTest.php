<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Tests\Abilities;

use Generator;
use Lorekeeper\Bot\Abilities\Paces;
use Lorekeeper\Client\Command\Map\MoveToCellCommand;
use Lorekeeper\Client\Script\Await;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Pause;
use Lorekeeper\Client\Script\Send;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Core\World\Map\MapCell;
use Lorekeeper\Core\World\Map\MapPad;
use Lorekeeper\Core\World\Map\MapSpot;
use Lorekeeper\Server\AreaId;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Shop\ShopLoadedEvent;
use Lorekeeper\Server\Event\TooSoonAfterCombatEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\DateTime\Duration;

final class PacesTest extends TestCase
{
    #[Test]
    public function a_command_that_awaits_nothing_is_sent_once_as_it_is(): void
    {
        $send = new Send(new MoveToCellCommand(new AreaId(946), new MapSpot(new MapCell('r3'), MapPad::Left)));
        $pacer = new class {
            use Paces;

            /** @return Generator<int, Instruction, null|Event|TimedOut, null|Event|TimedOut> */
            public function pace(Send $send): Generator
            {
                return $this->paced($send);
            }
        };
        $pacing = $pacer->pace($send);

        static::assertSame($send, $pacing->current());

        $pacing->send(null);

        static::assertFalse($pacing->valid());
        static::assertNull($pacing->getReturn());
    }

    #[Test]
    public function a_refusal_right_after_a_fight_waits_two_seconds_and_asks_again(): void
    {
        $send = new Send(
            new MoveToCellCommand(new AreaId(946), new MapSpot(new MapCell('r3'), MapPad::Left)),
            new Await([ShopLoadedEvent::class], Duration::seconds(10)),
        );
        $pacer = new class {
            use Paces;

            /** @return Generator<int, Instruction, null|Event|TimedOut, null|Event|TimedOut> */
            public function pace(Send $send): Generator
            {
                return $this->paced($send);
            }
        };
        $pacing = $pacer->pace($send);

        static::assertContains(TooSoonAfterCombatEvent::class, $pacing->current()->await->events ?? []);

        $pacing->send(new TooSoonAfterCombatEvent());

        static::assertEquals(new Pause(Duration::seconds(2)), $pacing->current());

        $pacing->send(null);
        $pacing->send(null);

        static::assertFalse($pacing->valid());
    }
}
