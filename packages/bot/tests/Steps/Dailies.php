<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Tests\Steps;

use Closure;
use Lorekeeper\Bot\Steps\Checks\Holding;
use Lorekeeper\Bot\Steps\Checks\QuestDone;
use Lorekeeper\Bot\Steps\Fights\Hunt;
use Lorekeeper\Bot\Steps\Moves\JoinMap;
use Lorekeeper\Bot\Steps\Pauses\Rest;
use Lorekeeper\Bot\Steps\Quests\AcceptQuest;
use Lorekeeper\Bot\Steps\Quests\TurnInQuest;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Core\World\Map\MapCell;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Map\MapPad;
use Lorekeeper\Core\World\Map\MapSpot;
use Lorekeeper\Core\World\Monster\MonsterName;
use Lorekeeper\Core\World\Quest\QuestId;
use Psl\DateTime\Duration;
use Psl\DateTime\Timestamp;

final readonly class Dailies
{
    /** @param null|Closure(): Timestamp $clock */
    public static function cryptoToken(?Closure $clock = null): InOrder
    {
        return new InOrder([
            new JoinMap(new MapName('boxes')),
            new QuestDone(new QuestId(6187)),
            new AcceptQuest(new QuestId(6187)),
            new Hunt(
                new MapSpot(new MapCell('Closet'), MapPad::Center),
                null,
                new ItemId(42_658),
                new Quantity(1),
                Duration::minutes(5),
            ),
            new TurnInQuest(new QuestId(6187), new ItemId(42_657)),
            new Rest(),
        ], $clock);
    }

    /** @param null|Closure(): Timestamp $clock */
    public static function eldersBlood(?Closure $clock = null): InOrder
    {
        return new InOrder([
            new Holding(new ItemId(5586), new Quantity(20)),
            new JoinMap(new MapName('arcangrove')),
            new QuestDone(new QuestId(802)),
            new AcceptQuest(new QuestId(802)),
            new Hunt(
                new MapSpot(new MapCell('LeftBack'), MapPad::Left),
                null,
                new ItemId(5574),
                new Quantity(50),
                Duration::minutes(20),
            ),
            new TurnInQuest(new QuestId(802), new ItemId(5586)),
            new Rest(),
        ], $clock);
    }

    /** @param null|Closure(): Timestamp $clock */
    public static function tokensOfCollection(?Closure $clock = null): InOrder
    {
        return new InOrder([
            new JoinMap(new MapName('terrarium')),
            new QuestDone(new QuestId(1316)),
            new AcceptQuest(new QuestId(1316)),
            new Hunt(
                new MapSpot(new MapCell('r2'), MapPad::Left),
                new MonsterName('Carnivorous Cricket'),
                new ItemId(9392),
                new Quantity(2),
                Duration::minutes(10),
            ),
            new TurnInQuest(new QuestId(1316), new ItemId(9397)),
            new Rest(),
        ], $clock);
    }

    /** @return Closure(): Timestamp */
    public static function hourly(): Closure
    {
        $now = Timestamp::fromParts(2026, 10, 16);

        return static function () use (&$now): Timestamp {
            return $now = $now->plus(Duration::hours(1));
        };
    }
}
