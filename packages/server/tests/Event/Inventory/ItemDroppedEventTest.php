<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Inventory;

use Lorekeeper\Core\Item\ItemTag;
use Lorekeeper\Core\Item\Misc\Resource;
use Lorekeeper\Server\Event\Inventory\ItemDroppedEvent;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ItemDroppedEventTest extends TestCase
{
    private const string CRYPTO_TOKEN = <<<'JSON'
        {"t":"xt","b":{"r":-1,"o":{"cmd":"dropItem","items":{"42657":{"ItemID":42657,"sName":"Crypto Token","sDesc":"Hold onto these, you never know what they might be used for.","iRng":10,"iDPS":0,"iLvl":1,"iRty":13,"iCost":0,"bTemp":false,"iStk":300,"bPTR":false,"bUpg":false,"bCoins":true,"bStaff":false,"sLink":"","iQSIndex":-1,"iQSValue":0,"sReqQuests":"","bHouse":false,"sType":"Item","sIcon":"iibag","sES":"None","sElmt":"None","metaValues":{},"iQty":1}}}}}
        JSON;

    private const string CUBES = <<<'JSON'
        {"t":"xt","b":{"r":-1,"o":{"cmd":"dropItem","items":{"4708":{"ItemID":4708,"sName":"Cubes","sDesc":"Boxes are always useful for something.\r\nCan stack up to x500.","iRng":10,"iDPS":0,"iLvl":1,"iRty":10,"iCost":0,"bTemp":false,"iStk":500,"bPTR":false,"bUpg":false,"bCoins":true,"bStaff":false,"sLink":"","iQSIndex":-1,"iQSValue":0,"sReqQuests":"","bHouse":false,"sType":"Quest Item","sIcon":"iibag","sES":"None","sElmt":"None","metaValues":{},"bQuest":1,"iQty":1}}}}}
        JSON;

    #[Test]
    public function it_reads_the_reward_a_quest_left_in_the_drops(): void
    {
        $event = ItemDroppedEvent::from(JsonMessage::from(self::CRYPTO_TOKEN)->unwrap())->unwrap();

        static::assertCount(1, $event->items);
        static::assertInstanceOf(Resource::class, $event->items[0]->item);
        static::assertSame([42_657, 'Crypto Token', 1], [
            $event->items[0]->item->id->value,
            $event->items[0]->item->name->value,
            $event->items[0]->quantity->value,
        ]);
        static::assertSame('Dropped 1 Crypto Token', $event->summary());
    }

    #[Test]
    public function it_reads_what_a_monster_dropped(): void
    {
        $event = ItemDroppedEvent::from(JsonMessage::from(self::CUBES)->unwrap())->unwrap();

        static::assertSame('Cubes', $event->items[0]->item->name->value);
    }

    #[Test]
    public function it_tags_a_dropped_item_only_members_can_use(): void
    {
        $member = str_replace(search: '"bUpg":false', replace: '"bUpg":true', subject: self::CRYPTO_TOKEN);
        $event = ItemDroppedEvent::from(JsonMessage::from($member)->unwrap())->unwrap();

        static::assertTrue($event->items[0]->item->tags->has(ItemTag::MemberOnly));
        static::assertFalse(ItemDroppedEvent::from(
            JsonMessage::from(self::CRYPTO_TOKEN)->unwrap(),
        )->unwrap()->items[0]->item->tags->has(ItemTag::MemberOnly));
    }

    #[Test]
    public function it_rejects_a_drop_with_no_items(): void
    {
        $this->expectException(MalformedEventException::class);

        ItemDroppedEvent::from(
            JsonMessage::from('{"t":"xt","b":{"r":-1,"o":{"cmd":"dropItem","items":{}}}}')->unwrap(),
        );
    }
}
