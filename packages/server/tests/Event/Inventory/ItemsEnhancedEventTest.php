<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Inventory;

use Lorekeeper\Core\Item\ItemLevel;
use Lorekeeper\Core\Item\Misc\Enhancement\Enhancement;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementRarity;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementType;
use Lorekeeper\Server\Event\Inventory\ItemsEnhancedEvent;
use Lorekeeper\Server\Message\JsonMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Option;

final class ItemsEnhancedEventTest extends TestCase
{
    #[Test]
    public function it_reads_the_items_enhanced_from_a_shop_and_what_it_cost(): void
    {
        $event = ItemsEnhancedEvent::from(
            JsonMessage::from(
                '{"t":"xt","b":{"r":-1,"o":{"cmd":"enhanceItemShop","ItemIDs":[3],"EnhID":3628,"EnhPID":9,"EnhLvl":21,"EnhRty":1,"EnhDPS":100,"EnhRng":10,"EnhName":"Lucky Weapon Enhancement","iCost":1010,"bSuccess":1}}}',
            )->unwrap(),
        )->unwrap();

        static::assertSame(3, $event->items[0]->value);
        static::assertEquals(
            new Enhancement(EnhancementType::Lucky, EnhancementRarity::PlusZero, new ItemLevel(21), Option\none()),
            $event->enhancement,
        );
        static::assertSame(1010, $event->paid->value);
        static::assertSame('1 item(s) enhanced with Lucky level 21', $event->summary());
    }

    #[Test]
    public function it_ignores_other_commands(): void
    {
        static::assertTrue(
            ItemsEnhancedEvent::from(
                JsonMessage::from(
                    '{"t":"xt","b":{"r":-1,"o":{"cmd":"acceptQuest","bSuccess":1,"QuestID":1316,"msg":"success"}}}',
                )->unwrap(),
            )->isNone(),
        );
    }
}
