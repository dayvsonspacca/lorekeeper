<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\World\Map;

use Lorekeeper\Core\Game\Asset\Gamefile;
use Lorekeeper\Core\World\Map\Map;
use Lorekeeper\Core\World\Map\MapMonsters;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Map\MapPlayers;
use Lorekeeper\Core\World\Map\MapRoom;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MapTest extends TestCase
{
    #[Test]
    public function it_is_joined_by_its_name_and_room(): void
    {
        $map = new Map(
            new MapName('battleon'),
            new MapRoom(1234),
            new Gamefile('Battleon/town-Battleon-7Nov23.swf'),
            new MapMonsters([]),
            new MapPlayers([]),
        );

        static::assertSame('battleon-1234', $map->join());
    }
}
