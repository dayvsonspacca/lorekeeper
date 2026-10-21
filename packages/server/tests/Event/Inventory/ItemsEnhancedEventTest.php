<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Inventory;

use Lorekeeper\Core\Item\ItemLevel;
use Lorekeeper\Core\Item\Misc\Enhancement\Enhancement;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementRarity;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementType;
use Lorekeeper\Server\Event\Inventory\ItemsEnhancedEvent;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
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
    public function it_says_nothing_when_the_enhancement_failed(): void
    {
        static::assertTrue(
            ItemsEnhancedEvent::from(
                JsonMessage::from('{"t":"xt","b":{"r":-1,"o":{"cmd":"enhanceItemShop","bSuccess":0}}}')->unwrap(),
            )->isNone(),
        );
    }

    #[Test]
    #[TestWith([
        '{"t":"xt","b":{"r":-1,"o":{"cmd":"enhanceItemShop","ItemIDs":[3],"EnhID":3628,"EnhPID":9,"EnhLvl":21,"EnhRty":1,"iCost":1010}}}',
        'the enhanced items are invalid',
    ])]
    #[TestWith([
        '{"t":"xt","b":{"r":-1,"o":{"cmd":"enhanceItemShop","EnhID":3628,"EnhPID":9,"EnhLvl":21,"EnhRty":1,"iCost":1010,"bSuccess":1}}}',
        'a successful enhancement names no items, type, rarity or level',
    ])]
    #[TestWith([
        '{"t":"xt","b":{"r":-1,"o":{"cmd":"enhanceItemShop","ItemIDs":[3],"EnhID":3628,"EnhLvl":21,"EnhRty":1,"iCost":1010,"bSuccess":1}}}',
        'a successful enhancement names no items, type, rarity or level',
    ])]
    public function it_rejects_an_enhancement_it_cannot_read(string $json, string $reason): void
    {
        $this->expectException(MalformedEventException::class);
        $this->expectExceptionMessage($reason);

        ItemsEnhancedEvent::from(JsonMessage::from($json)->unwrap());
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
