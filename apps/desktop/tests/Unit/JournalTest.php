<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests\Unit;

use Lorekeeper\Apps\Desktop\Bots\Journal;
use Lorekeeper\Apps\Desktop\Bots\LogLevel;
use Lorekeeper\Apps\Desktop\Tests\Characters;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemLevel;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\Misc\Resource;
use Lorekeeper\Core\Player\PlayerItem;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Core\World\Quest\Quest;
use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Core\World\Quest\QuestName;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Inventory\DropPickedUpEvent;
use Lorekeeper\Server\Event\Inventory\ItemDroppedEvent;
use Lorekeeper\Server\Event\Inventory\ItemIncrease;
use Lorekeeper\Server\Event\Inventory\ItemsIncreasedEvent;
use Lorekeeper\Server\Event\Inventory\QuestItemsIncreasedEvent;
use Lorekeeper\Server\Event\Inventory\QuestItemsReceivedEvent;
use Lorekeeper\Server\Event\Quest\QuestAcceptedEvent;
use Lorekeeper\Server\Event\Quest\QuestCompletedEvent;
use Lorekeeper\Server\Event\Quest\QuestLoadedEvent;
use Lorekeeper\Server\Event\Quest\QuestTurnInRefusedEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Option;

final class JournalTest extends TestCase
{
    #[Test]
    public function it_tells_how_a_daily_goes_from_accepting_to_picking_up_the_reward(): void
    {
        $journal = new Journal();
        $me = Characters::me('Hilise', 30, 'arcangrove');
        $slain = self::resource(5574, 'Slain Gorillaphant');
        $lines = [];
        $hear = static function (Event $event) use ($journal, $me, &$lines): void {
            $me->hear($event);
            foreach ($journal->heard($event, $me) as [$message, $level]) {
                $lines[] = [$message, $level->value];
            }
        };

        $hear(new QuestLoadedEvent([new Quest(
            new QuestId(802),
            new QuestName("Experiment 107: Elder's Blood Potion"),
            Option\none(),
            [],
            [],
            [],
            Option\none(),
        )]));
        $hear(new QuestAcceptedEvent(new QuestId(802)));
        $hear(new QuestItemsReceivedEvent([new PlayerItem($slain)]));
        foreach (range(2, end: 10) as $kill) {
            $hear(new QuestItemsIncreasedEvent([new ItemIncrease(
                new ItemId(5574),
                new Quantity(1),
                new Quantity($kill),
            )]));
        }

        $hear(new QuestCompletedEvent(new QuestId(802), new QuestName("Experiment 107: Elder's Blood Potion")));
        $hear(new ItemDroppedEvent([new PlayerItem(self::resource(5586, "Elders' Blood"))]));
        $hear(new DropPickedUpEvent(new ItemId(5586), new Quantity(1), new Quantity(11)));

        static::assertSame(
            [
                ["Accepted Experiment 107: Elder's Blood Potion",  'info'],
                ['Collected Slain Gorillaphant ×1',                'info'],
                ['Collected Slain Gorillaphant ×10',               'info'],
                ["Completed Experiment 107: Elder's Blood Potion", 'success'],
                ["Picked up Elders' Blood ×1 (11 held)",           'success'],
            ],
            $lines,
        );
    }

    #[Test]
    public function what_it_does_not_know_by_name_goes_by_number(): void
    {
        $journal = new Journal();

        static::assertEquals(
            [['Accepted quest 7', LogLevel::Info]],
            $journal->heard(new QuestAcceptedEvent(new QuestId(7)), null),
        );
        static::assertEquals(
            [['Got item 9 ×2 (2 held)', LogLevel::Success]],
            $journal->heard(new ItemsIncreasedEvent([new ItemIncrease(new ItemId(9), new Quantity(2))]), null),
        );
        static::assertEquals(
            [['Could not complete Seven: Missing Badge', LogLevel::Warning]],
            $journal->heard(new QuestTurnInRefusedEvent(new QuestId(7), new QuestName('Seven'), 'Missing Badge'), null),
        );
        static::assertEquals(
            [['Could not complete Seven', LogLevel::Warning]],
            $journal->heard(new QuestTurnInRefusedEvent(new QuestId(7), new QuestName('Seven'), ''), null),
        );
    }

    private static function resource(int $id, string $name): Resource
    {
        return new Resource(new ItemId($id), new ItemName($name), new ItemLevel(1));
    }
}
