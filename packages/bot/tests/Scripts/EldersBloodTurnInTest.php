<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Tests\Scripts;

use Lorekeeper\Bot\Scripts\EldersBloodScript;
use Lorekeeper\Client\Script\Failed;
use Lorekeeper\Client\Tests\Fixture;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemLevel;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\Misc\Resource;
use Lorekeeper\Core\Player\PlayerItem;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Core\World\Quest\Quest;
use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Core\World\Quest\QuestMark;
use Lorekeeper\Core\World\Quest\QuestName;
use Lorekeeper\Core\World\Quest\QuestPeriod;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Inventory\QuestItemsReceivedEvent;
use Lorekeeper\Server\Event\Quest\QuestAcceptedEvent;
use Lorekeeper\Server\Event\Quest\QuestCompletedEvent;
use Lorekeeper\Server\Event\Quest\QuestLoadedEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Option;

final class EldersBloodTurnInTest extends TestCase
{
    #[Test]
    public function it_stops_when_the_game_does_not_answer_the_turn_in(): void
    {
        [, $result] = Play::run(new EldersBloodScript(), Fixture::me(level: 30), [...self::hunted(), null]);

        static::assertEquals(
            new Failed('the game would not complete Experiment 107 (the game did not answer)'),
            $result,
        );
    }

    #[Test]
    public function it_stops_when_the_blood_never_drops(): void
    {
        [, $result] = Play::run(new EldersBloodScript(), Fixture::me(level: 30), [
            ...self::hunted(),
            new QuestCompletedEvent(new QuestId(802), new QuestName("Experiment 107: Elder's Blood Potion")),
            null,
        ]);

        static::assertEquals(
            new Failed("Experiment 107 was completed, but the Elders' Blood could not be picked up"),
            $result,
        );
    }

    /** @return list<null|Event> */
    private static function hunted(): array
    {
        return [
            Fixture::arrival('arcangrove', 7_101, ['LeftBack' => [2, 30]]),
            self::loaded(),
            new QuestAcceptedEvent(new QuestId(802)),
            null,
            null,
            null,
            new QuestItemsReceivedEvent([
                new PlayerItem(
                    new Resource(new ItemId(5574), new ItemName('Slain Gorillaphant'), new ItemLevel(1)),
                    new Quantity(50),
                ),
            ]),
        ];
    }

    private static function loaded(): QuestLoadedEvent
    {
        return new QuestLoadedEvent([new Quest(
            new QuestId(802),
            new QuestName("Experiment 107: Elder's Blood Potion"),
            Option\none(),
            [],
            [],
            [],
            Option\some(new QuestMark(QuestPeriod::Day, 0, 25)),
        )]);
    }
}
