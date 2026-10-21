<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Tests\Abilities;

use Generator;
use Lorekeeper\Bot\Abilities\Paces;
use Lorekeeper\Client\Command\Map\MoveToCellCommand;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Send;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Core\World\Map\MapCell;
use Lorekeeper\Core\World\Map\MapPad;
use Lorekeeper\Core\World\Map\MapSpot;
use Lorekeeper\Server\AreaId;
use Lorekeeper\Server\Event\Event;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

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
}
