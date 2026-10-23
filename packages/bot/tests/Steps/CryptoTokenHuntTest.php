<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Tests\Steps;

use Lorekeeper\Client\Script\Failed;
use Lorekeeper\Client\Tests\Fixture;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemLevel;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\Misc\Resource;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Core\World\Quest\Quest;
use Lorekeeper\Core\World\Quest\QuestGroup;
use Lorekeeper\Core\World\Quest\QuestGroupId;
use Lorekeeper\Core\World\Quest\QuestGroupNumber;
use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Core\World\Quest\QuestName;
use Lorekeeper\Server\Event\Inventory\HeldItem;
use Lorekeeper\Server\Event\Inventory\ItemDroppedEvent;
use Lorekeeper\Server\Event\Quest\QuestAcceptedEvent;
use Lorekeeper\Server\Event\Quest\QuestLoadedEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Option;

final class CryptoTokenHuntTest extends TestCase
{
    #[Test]
    public function it_stops_hunting_when_the_ore_cannot_be_picked_up(): void
    {
        $ore = new HeldItem(
            new Resource(new ItemId(42_658), new ItemName('Metal Ore'), new ItemLevel(1), new Quantity(99)),
        );

        [$steps, $result] = Play::run(Dailies::cryptoToken(), Fixture::me(level: 4), [
            Fixture::arrival('boxes', 138_097, ['Closet' => [5, 1]]),
            self::loaded(),
            new QuestAcceptedEvent(new QuestId(6187)),
            null,
            null,
            null,
            new ItemDroppedEvent([$ore]),
            null,
        ]);

        static::assertEquals(
            new Failed('step 4 (Hunt in Closet for item 42658 ×1): item 42658 ×1 was not held after 5 minutes'),
            $result,
        );
        static::assertSame('%xt%zm%getDrop%122628%42658%', Play::sent($steps)[array_key_last(Play::sent($steps))]);
    }

    #[Test]
    public function it_stops_when_no_monster_stands_in_the_closet(): void
    {
        [, $result] = Play::run(Dailies::cryptoToken(), Fixture::me(level: 4), [
            Fixture::arrival('boxes', 138_097),
            self::loaded(),
            new QuestAcceptedEvent(new QuestId(6187)),
        ]);

        static::assertEquals(
            new Failed('step 4 (Hunt in Closet for item 42658 ×1): There are no monsters in Closet.'),
            $result,
        );
    }

    private static function loaded(): QuestLoadedEvent
    {
        return new QuestLoadedEvent([new Quest(
            new QuestId(6187),
            new QuestName("Odd's 'n' Ends"),
            [],
            [],
            [],
            Option\none(),
            Option\some(new QuestGroupId(QuestGroup::Daily, new QuestGroupNumber(70))),
        )]);
    }
}
