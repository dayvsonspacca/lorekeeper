<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Script\Game;

use Lorekeeper\Client\Command\Map\JoinHouseCommand;
use Lorekeeper\Client\Script\Await;
use Lorekeeper\Client\Script\Game\JoinHouseScript;
use Lorekeeper\Client\Script\Send;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Server\Event\Map\MovedToMapEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\DateTime\Duration;

final class JoinHouseScriptTest extends TestCase
{
    #[Test]
    public function it_asks_for_the_house_and_waits_to_move_in(): void
    {
        $timedOut = new TimedOut(new Await([MovedToMapEvent::class], Duration::seconds(15)));

        [$instructions, $result] = Replay::run(new JoinHouseScript(new PlayerName('Hero')), $timedOut);

        static::assertSame($timedOut, $result);
        static::assertEquals(
            [new Send(
                new JoinHouseCommand(new PlayerName('Hero')),
                new Await([MovedToMapEvent::class], Duration::seconds(15)),
            )],
            $instructions,
        );
    }
}
