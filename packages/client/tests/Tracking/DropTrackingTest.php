<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Tracking;

use Lorekeeper\Client\Drops;
use Lorekeeper\Client\Tracking\DropTracking;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemLevel;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\Misc\Resource;
use Lorekeeper\Core\Player\PlayerItem;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Server\Event\Combat\PlayerDiedEvent;
use Lorekeeper\Server\Event\Inventory\DropPickedUpEvent;
use Lorekeeper\Server\Event\Inventory\ItemDroppedEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DropTrackingTest extends TestCase
{
    #[Test]
    public function drops_wait_until_they_are_picked_up(): void
    {
        $drops = DropTracking::follow(new Drops(), new ItemDroppedEvent([self::cubes(1)]));
        $drops = DropTracking::follow($drops, new ItemDroppedEvent([self::cubes(2), self::token()]));

        static::assertSame(3, $drops->find(new ItemId(4708))?->quantity->value);
        static::assertSame('Crypto Token', $drops->find(new ItemId(42_657))?->item->name->value);

        $drops = DropTracking::follow(
            $drops,
            new DropPickedUpEvent(new ItemId(42_657), new Quantity(1), new Quantity(1)),
        );

        static::assertNull($drops->find(new ItemId(42_657)));
        static::assertNotNull($drops->find(new ItemId(4708)));
    }

    #[Test]
    public function other_events_leave_the_drops_as_they_are(): void
    {
        $drops = new Drops([4708 => self::cubes(1)]);

        static::assertSame($drops, DropTracking::follow($drops, new PlayerDiedEvent()));
        static::assertSame($drops, DropTracking::follow(
            $drops,
            new DropPickedUpEvent(new ItemId(42_657), new Quantity(1), new Quantity(1)),
        ));
    }

    private static function cubes(int $quantity): PlayerItem
    {
        return new PlayerItem(
            new Resource(new ItemId(4708), new ItemName('Cubes'), new ItemLevel(1)),
            new Quantity($quantity),
        );
    }

    private static function token(): PlayerItem
    {
        return new PlayerItem(new Resource(new ItemId(42_657), new ItemName('Crypto Token'), new ItemLevel(1)));
    }
}
