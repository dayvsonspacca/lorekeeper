<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Tracking;

use Lorekeeper\Client\Tests\Fixture;
use Lorekeeper\Core\Currency\AdventureCoins;
use Lorekeeper\Core\Currency\Gold;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\Player\PlayerLevelProgress;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Player\PlayerQuestMarks;
use Lorekeeper\Core\Player\PlayerTag;
use Lorekeeper\Core\Player\Wallet;
use Lorekeeper\Core\Progression\ClassPoints;
use Lorekeeper\Core\Progression\Experience;
use Lorekeeper\Core\Support\Gauge;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Core\World\Monster\MonsterSpawnId;
use Lorekeeper\Core\World\Quest\QuestMark;
use Lorekeeper\Core\World\Quest\QuestPeriod;
use Lorekeeper\Server\Event\Combat\ResurrectedEvent;
use Lorekeeper\Server\Event\Player\PlayerData;
use Lorekeeper\Server\Event\Player\PlayerDataLoadedEvent;
use Lorekeeper\Server\Event\Progression\LeveledUpEvent;
use Lorekeeper\Server\Event\Quest\QuestMarkedEvent;
use Lorekeeper\Server\Event\Reward\MonsterRewardedEvent;
use Lorekeeper\Server\SocketId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PlayerProgressTrackingTest extends TestCase
{
    #[Test]
    public function rewards_add_experience_up_to_the_level_and_gold_to_the_wallet(): void
    {
        $me = Fixture::me(level: 4);

        $me->hear(self::reward(experience: 400, gold: 15));
        $me->hear(self::reward(experience: 900, gold: 5));

        static::assertSame([1000, 1000], [
            $me->player()->progress->experience->current->value,
            $me->player()->progress->experience->maximum->value,
        ]);
        static::assertSame(20, $me->player()->wallet->gold->value);
    }

    #[Test]
    public function a_new_level_starts_its_experience_over(): void
    {
        $me = Fixture::me(level: 4);
        $me->hear(self::reward(experience: 400, gold: 0));

        $me->hear(new LeveledUpEvent(new PlayerLevel(5), new Experience(1500)));

        static::assertSame([5, 0, 1500], [
            $me->player()->progress->level->value,
            $me->player()->progress->experience->current->value,
            $me->player()->progress->experience->maximum->value,
        ]);
    }

    #[Test]
    public function the_player_data_sets_everything_straight_and_a_daily_marks_itself(): void
    {
        $me = Fixture::me(level: 4);

        $me->hear(self::memberData());
        $me->hear(new QuestMarkedEvent(new QuestMark(QuestPeriod::Day, 0, 3)));
        $me->hear(new ResurrectedEvent());

        $player = $me->player();
        static::assertSame([81, 4_514_754, 6_800_132, 131], [
            $player->progress->level->value,
            $player->progress->experience->current->value,
            $player->wallet->gold->value,
            $player->inventory->slots->value,
        ]);
        static::assertTrue($player->marks->has(new QuestMark(QuestPeriod::Day, 2, 6)));
        static::assertTrue($player->marks->has(new QuestMark(QuestPeriod::Day, 0, 3)));
        static::assertTrue($player->tags->has(PlayerTag::Member));
    }

    private static function reward(int $experience, int $gold): MonsterRewardedEvent
    {
        return new MonsterRewardedEvent(
            new Gold($gold),
            new Experience($experience),
            new ClassPoints(0),
            new MonsterSpawnId(4),
        );
    }

    private static function memberData(): PlayerDataLoadedEvent
    {
        return new PlayerDataLoadedEvent([new PlayerData(
            new SocketId(122_628),
            new PlayerName('lorekeeper03'),
            new PlayerLevelProgress(
                new PlayerLevel(81),
                new Gauge(new Experience(4_514_754), new Experience(5_280_000)),
            ),
            new Wallet(new Gold(6_800_132), new AdventureCoins(2899)),
            true,
            new Quantity(131),
            new PlayerQuestMarks([new QuestMark(QuestPeriod::Day, 2, 6)]),
        )]);
    }
}
