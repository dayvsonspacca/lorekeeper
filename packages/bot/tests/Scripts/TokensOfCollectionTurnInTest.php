<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Tests\Scripts;

use Lorekeeper\Bot\Scripts\TokensOfCollectionScript;
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
use Lorekeeper\Server\Event\Inventory\DropPickedUpEvent;
use Lorekeeper\Server\Event\Inventory\ItemDroppedEvent;
use Lorekeeper\Server\Event\Inventory\ItemIncrease;
use Lorekeeper\Server\Event\Inventory\ItemsIncreasedEvent;
use Lorekeeper\Server\Event\Map\MovedToMapEvent;
use Lorekeeper\Server\Event\Quest\QuestAcceptedEvent;
use Lorekeeper\Server\Event\Quest\QuestCompletedEvent;
use Lorekeeper\Server\Event\Quest\QuestLoadedEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Option;

final class TokensOfCollectionTurnInTest extends TestCase
{
    #[Test]
    public function it_stops_when_the_game_does_not_answer_the_turn_in(): void
    {
        [, $result] = Play::run(new TokensOfCollectionScript(), Fixture::me(level: 82), [...self::hunted(), null]);

        static::assertEquals(
            new Failed('the game would not complete Tokens of Collection (the game did not answer)'),
            $result,
        );
    }

    #[Test]
    public function it_stops_when_the_token_never_drops(): void
    {
        [, $result] = Play::run(new TokensOfCollectionScript(), Fixture::me(level: 82), [
            ...self::hunted(),
            new QuestCompletedEvent(new QuestId(1316), new QuestName('Tokens of Collection')),
            null,
        ]);

        static::assertEquals(
            new Failed('Tokens of Collection was completed, but the Token of Collection could not be picked up'),
            $result,
        );
    }

    /** @return list<null|Event> */
    private static function hunted(): array
    {
        return [
            self::terrarium(),
            self::loaded(),
            new QuestAcceptedEvent(new QuestId(1316)),
            null,
            null,
            null,
            new ItemDroppedEvent([new PlayerItem(
                new Resource(new ItemId(9392), new ItemName('This Might Be A Token'), new ItemLevel(1)),
            )]),
            new DropPickedUpEvent(new ItemId(9392), new Quantity(1), new Quantity(1)),
            null,
            null,
            null,
            new ItemsIncreasedEvent([new ItemIncrease(new ItemId(9392), new Quantity(1), new Quantity(2))]),
        ];
    }

    private static function terrarium(): MovedToMapEvent
    {
        return Fixture::arrivalOf('terrarium', 189_254, [
            Fixture::spawn(3, 'r2', Fixture::kind(13, 'Death on Wings')),
            Fixture::spawn(4, 'r2', Fixture::kind(17, 'Carnivorous Cricket')),
            Fixture::spawn(5, 'r2', Fixture::kind(28, 'Killer Cricket')),
        ]);
    }

    private static function loaded(): QuestLoadedEvent
    {
        return new QuestLoadedEvent([new Quest(
            new QuestId(1316),
            new QuestName('Tokens of Collection'),
            Option\none(),
            [],
            [],
            [],
            Option\some(new QuestMark(QuestPeriod::Day, 1, 14)),
        )]);
    }
}
