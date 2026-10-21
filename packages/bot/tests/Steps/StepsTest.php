<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Tests\Steps;

use Lorekeeper\Bot\Steps\Action;
use Lorekeeper\Bot\Steps\Check;
use Lorekeeper\Bot\Steps\Checks\Holding;
use Lorekeeper\Bot\Steps\Checks\LevelAtLeast;
use Lorekeeper\Bot\Steps\Checks\QuestDone;
use Lorekeeper\Bot\Steps\Fights\FightFor;
use Lorekeeper\Bot\Steps\Fights\FightToLevel;
use Lorekeeper\Bot\Steps\Fights\Hunt;
use Lorekeeper\Bot\Steps\Gear\Enhance;
use Lorekeeper\Bot\Steps\Moves\JoinMap;
use Lorekeeper\Bot\Steps\Moves\MoveTo;
use Lorekeeper\Bot\Steps\Pauses\Rest;
use Lorekeeper\Bot\Steps\Pauses\Wait;
use Lorekeeper\Bot\Steps\Quests\AcceptQuest;
use Lorekeeper\Bot\Steps\Quests\TurnInQuest;
use Lorekeeper\Client\Script\Done;
use Lorekeeper\Client\Script\Pause;
use Lorekeeper\Client\Tests\Fixture;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementType;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Core\World\Map\MapCell;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Map\MapPad;
use Lorekeeper\Core\World\Map\MapSpot;
use Lorekeeper\Core\World\Monster\MonsterName;
use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Core\World\Quest\QuestName;
use Lorekeeper\Server\Event\Quest\QuestCompletedEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\DateTime\Duration;
use Psl\DateTime\Timestamp;

final class StepsTest extends TestCase
{
    #[Test]
    public function each_step_says_what_it_does(): void
    {
        $closet = new MapSpot(new MapCell('Closet'), MapPad::Center);
        $cricket = new MonsterName('Carnivorous Cricket');
        $steps = [
            new JoinMap(new MapName('boxes')),
            new MoveTo($closet),
            new Hunt($closet, null, new ItemId(42_658), new Quantity(1), Duration::minutes(5)),
            new Hunt($closet, $cricket, new ItemId(9392), new Quantity(2), Duration::minutes(1)),
            new FightToLevel($closet, null, new PlayerLevel(10)),
            new FightToLevel($closet, $cricket, new PlayerLevel(10)),
            new FightFor($closet, null, Duration::minutes(15)),
            new FightFor($closet, $cricket, Duration::minutes(15)),
            new AcceptQuest(new QuestId(6187)),
            new TurnInQuest(new QuestId(6187), null),
            new TurnInQuest(new QuestId(6187), new ItemId(42_657)),
            new Enhance(EnhancementType::Lucky),
            new Rest(),
            new Wait(Duration::seconds(30)),
            new QuestDone(new QuestId(6187)),
            new Holding(new ItemId(5586), new Quantity(20)),
            new LevelAtLeast(new PlayerLevel(30)),
        ];

        static::assertSame(
            [
                'Join boxes',
                'Move to Closet',
                'Hunt in Closet for item 42658 ×1',
                'Hunt Carnivorous Cricket in Closet for item 9392 ×2',
                'Fight in Closet until level 10',
                'Fight Carnivorous Cricket in Closet until level 10',
                'Fight in Closet for 15 min',
                'Fight Carnivorous Cricket in Closet for 15 min',
                'Accept quest 6187',
                'Turn in quest 6187',
                'Turn in quest 6187 for item 42657',
                'Enhance the equipped gear with Lucky',
                'Rest',
                'Wait 30s',
                'Quest 6187 was already done',
                'Holding item 5586 ×20',
                'Level 30 or higher',
            ],
            array_map(static fn(Action|Check $step): string => $step->describe(), $steps),
        );
    }

    #[Test]
    public function it_moves_to_a_cell_of_the_map_it_is_in(): void
    {
        $spot = new MapSpot(new MapCell('Closet'), MapPad::Center);

        [$steps] = Play::run(new InOrder([new MoveTo($spot)]), Fixture::me(map: 'boxes'), [null]);

        static::assertSame(['%xt%zm%moveToCell%1%Closet%Center%'], Play::sent($steps));
    }

    #[Test]
    public function it_waits_as_long_as_asked(): void
    {
        [$steps] = Play::run(new InOrder([new Wait(Duration::seconds(30))]), Fixture::me(), [null]);

        static::assertEquals([new Pause(Duration::seconds(30))], $steps);
    }

    #[Test]
    public function holding_fewer_than_asked_does_not_hold(): void
    {
        [, $result] = Play::run(
            new InOrder([new Holding(new ItemId(42_657), new Quantity(1)), new Rest()]),
            Fixture::me(),
            [],
        );

        static::assertEquals(new Done('all steps are done'), $result);
    }

    #[Test]
    public function a_level_holds_from_that_level_up(): void
    {
        $check = new InOrder([new LevelAtLeast(new PlayerLevel(30)), new Rest()]);

        static::assertEquals(new Done('all steps are done'), Play::run($check, Fixture::me(level: 29), [])[1]);
        static::assertEquals(new Done('stopped: Level 30 or higher'), Play::run($check, Fixture::me(level: 30), [])[1]);
    }

    #[Test]
    public function it_fights_until_the_time_is_up(): void
    {
        $me = Fixture::me(level: 4);
        $me->hear(Fixture::arrival('boxes', 138_097, ['Closet' => [5, 1]]));
        $now = Timestamp::fromParts(2026, 10, 16);
        $script = new InOrder([new FightFor(
            new MapSpot(new MapCell('Closet'), MapPad::Center),
            null,
            Duration::minutes(2),
        )], static function () use (&$now): Timestamp {
            return $now = $now->plus(Duration::minutes(1));
        });

        [$steps, $result] = Play::run($script, $me, [null, null]);

        static::assertSame(
            ['%xt%zm%moveToCell%138097%Closet%Center%', '%xt%zm%gar%138097%0%a1>m:5%wvz%'],
            Play::sent($steps),
        );
        static::assertEquals(new Done('all steps are done'), $result);
    }

    #[Test]
    public function a_quest_turned_in_for_no_reward_picks_nothing_up(): void
    {
        [$steps, $result] = Play::run(
            new InOrder([new TurnInQuest(new QuestId(7), null)]),
            Fixture::me(),
            [new QuestCompletedEvent(new QuestId(7), new QuestName('Seven'))],
        );

        static::assertSame(['%xt%zm%tryQuestComplete%1%7%-1%false%1%wvz%'], Play::sent($steps));
        static::assertEquals(new Done('all steps are done'), $result);
    }
}
