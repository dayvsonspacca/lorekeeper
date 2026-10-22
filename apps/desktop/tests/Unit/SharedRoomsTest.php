<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests\Unit;

use Lorekeeper\Apps\Desktop\Bots\PrivateRooms;
use Lorekeeper\Apps\Desktop\Bots\SharedRooms;
use Lorekeeper\Core\World\Map\MapName;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SharedRoomsTest extends TestCase
{
    private const string ONE_INSTALL = 'one install';

    #[Test]
    public function bots_joining_the_same_map_meet_in_the_same_room(): void
    {
        $boxes = SharedRooms::for(new MapName('boxes'), self::ONE_INSTALL);

        static::assertSame($boxes->value, SharedRooms::for(new MapName('boxes'), self::ONE_INSTALL)->value);
        static::assertSame($boxes->value, SharedRooms::for(new MapName('Boxes'), self::ONE_INSTALL)->value);
        static::assertNotSame($boxes->value, SharedRooms::for(new MapName('arcangrove'), self::ONE_INSTALL)->value);
    }

    #[Test]
    public function another_install_picks_other_rooms(): void
    {
        static::assertNotSame(
            SharedRooms::for(new MapName('boxes'), self::ONE_INSTALL)->value,
            SharedRooms::for(new MapName('boxes'), 'another install')->value,
        );
    }

    #[Test]
    public function the_room_stays_among_the_private_ones(): void
    {
        foreach (['battleon', 'boxes', 'arcangrove', 'terrarium', 'icestormarena', 'oaklore'] as $map) {
            $room = SharedRooms::for(new MapName($map), self::ONE_INSTALL)->value;

            static::assertGreaterThanOrEqual(PrivateRooms::LOWEST, $room);
            static::assertLessThanOrEqual(PrivateRooms::HIGHEST, $room);
        }
    }
}
