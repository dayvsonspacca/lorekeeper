<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Script\Game;

use Lorekeeper\Client\Script\Game\JoinMapScript;
use Lorekeeper\Client\Script\Send;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Server\Event\Map\MapLockedEvent;
use Lorekeeper\Server\Event\Map\MovedToMapEvent;
use Lorekeeper\Server\RoomId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Option;

final class JoinMapScriptTest extends TestCase
{
    #[Test]
    public function it_asks_for_the_map_and_returns_how_the_server_answered(): void
    {
        $locked = new MapLockedEvent();

        [$instructions, $result] = Replay::run(
            new JoinMapScript(new PlayerName('Hero'), new MapName('forest'), Option\some(new RoomId(3))),
            $locked,
        );

        static::assertSame($locked, $result);
        static::assertInstanceOf(Send::class, $instructions[0]);
        static::assertSame('%xt%zm%cmd%1%tfer%Hero%forest-3%', $instructions[0]->command->toMessage());
        static::assertContains(MovedToMapEvent::class, $instructions[0]->await->events ?? []);
        static::assertContains(MapLockedEvent::class, $instructions[0]->await->events ?? []);
    }
}
