<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Tests\Event\Quest;

use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Knowledge\Fact;
use Lorekeeper\Core\World\Faction\FactionName;
use Lorekeeper\Core\World\Quest\QuestDescription;
use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Core\World\Quest\QuestName;
use Lorekeeper\Core\World\Quest\TurnInItem;
use Lorekeeper\Protocol\Event\Quest\QuestFacts;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class QuestFactsTest extends TestCase
{
    #[Test]
    public function it_extracts_name_and_description(): void
    {
        $facts = QuestFacts::of(QuestFacts::type()->coerce(QuestStub::quest()));

        static::assertEquals(
            [
                new Fact(new QuestId(3), new QuestName('Chipped Tooth')),
                new Fact(new QuestId(3), new QuestDescription('Bring back 10 Chipped Teeth.', '')),
            ],
            $facts,
        );
    }

    #[Test]
    public function it_skips_blank_description(): void
    {
        $facts = QuestFacts::of(QuestFacts::type()->coerce(QuestStub::quest(['sDesc' => ' '])));

        static::assertEquals([new Fact(new QuestId(3), new QuestName('Chipped Tooth'))], $facts);
    }

    #[Test]
    public function it_extracts_every_turn_in_item(): void
    {
        $facts = QuestFacts::of(QuestFacts::type()->coerce(QuestStub::quest([
            'QuestID' => 9,
            'sName' => 'Farm Cleanup',
            'sDesc' => '',
            'turnin' => [['ItemID' => 88, 'iQty' => 5], ['ItemID' => 80, 'iQty' => 10]],
        ])));

        static::assertEquals(
            [
                new Fact(new QuestId(9), new QuestName('Farm Cleanup')),
                new Fact(new QuestId(9), new TurnInItem(new ItemId(88), 5)),
                new Fact(new QuestId(9), new TurnInItem(new ItemId(80), 10)),
            ],
            $facts,
        );
    }

    #[Test]
    public function it_ignores_the_none_faction(): void
    {
        $facts = QuestFacts::of(QuestFacts::type()->coerce(QuestStub::quest(['sDesc' => ''])));

        foreach ($facts as $fact) {
            static::assertNotInstanceOf(FactionName::class, $fact->value);
        }
    }

    #[Test]
    public function it_extracts_items_once_from_both_item_lists(): void
    {
        $item = ['ItemID' => 559, 'sName' => 'Small Skull'];
        $facts = QuestFacts::of(QuestFacts::type()->coerce(QuestStub::quest([
            'sDesc' => '',
            'oItems' => ['559' => $item],
            'oRewards' => ['itemsS' => ['0' => $item]],
        ])));

        static::assertEquals(
            [
                new Fact(new QuestId(3), new QuestName('Chipped Tooth')),
                new Fact(new ItemId(559), new ItemName('Small Skull')),
            ],
            $facts,
        );
    }
}
