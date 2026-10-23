<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Tracking;

use Lorekeeper\Client\Tests\Fixture;
use Lorekeeper\Core\Game\Level\Experience;
use Lorekeeper\Core\Item\Equipment\CharacterClass\ClassPoints;
use Lorekeeper\Core\Player\Hero\Inventory\HeroWallet;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Player\PlayerSpawnId;
use Lorekeeper\Core\Player\PlayerTag;
use Lorekeeper\Core\Support\Gauge;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Core\World\Currency\AdventureCoins;
use Lorekeeper\Core\World\Currency\Gold;
use Lorekeeper\Core\World\Monster\MonsterSpawnId;
use Lorekeeper\Core\World\Quest\QuestGroup;
use Lorekeeper\Core\World\Quest\QuestGroupId;
use Lorekeeper\Core\World\Quest\QuestGroupNumber;
use Lorekeeper\Server\Event\Combat\ResurrectedEvent;
use Lorekeeper\Server\Event\Player\PlayerData;
use Lorekeeper\Server\Event\Player\PlayerDataLoadedEvent;
use Lorekeeper\Server\Event\Progression\LeveledUpEvent;
use Lorekeeper\Server\Event\Quest\QuestMarkedEvent;
use Lorekeeper\Server\Event\Reward\MonsterRewardedEvent;
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

        $hero = $me->hero();
        static::assertSame([1000, 1000], [
            $hero->progression->level->experience->current->value,
            $hero->progression->level->experience->maximum->value,
        ]);
        static::assertSame(20, $hero->inventory->wallet->gold->value);
    }

    #[Test]
    public function a_new_level_starts_its_experience_over(): void
    {
        $me = Fixture::me(level: 4);
        $me->hear(self::reward(experience: 400, gold: 0));

        $me->hear(new LeveledUpEvent(new PlayerLevel(5), new Experience(1500)));

        $hero = $me->hero();
        static::assertSame([5, 0, 1500], [
            $hero->level->value,
            $hero->progression->level->experience->current->value,
            $hero->progression->level->experience->maximum->value,
        ]);
    }

    #[Test]
    public function the_player_data_sets_everything_straight_and_a_daily_marks_itself(): void
    {
        $me = Fixture::me(level: 4);

        $me->hear(self::memberData());
        $me->hear(new QuestMarkedEvent(self::daily(3)));
        $me->hear(new ResurrectedEvent());

        $hero = $me->hero();
        static::assertSame([81, 4_514_754, 5_280_000, 6_800_132, 131, 40], [
            $hero->level->value,
            $hero->progression->level->experience->current->value,
            $hero->progression->level->experience->maximum->value,
            $hero->inventory->wallet->gold->value,
            $hero->inventory->slots->value,
            $hero->inventory->bank->slots->value,
        ]);
        static::assertTrue($hero->progression->quests->has(self::daily(70)));
        static::assertTrue($hero->progression->quests->has(self::daily(3)));
        static::assertTrue($hero->tags->has(PlayerTag::Member));
    }

    #[Test]
    public function player_data_about_someone_else_changes_nothing(): void
    {
        $me = Fixture::me(level: 4);

        $me->hear(new PlayerDataLoadedEvent([self::data(new PlayerSpawnId(1), level: 90)]));

        static::assertSame(4, $me->hero()->level->value);
        static::assertFalse($me->hero()->tags->has(PlayerTag::Member));
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
        return new PlayerDataLoadedEvent([self::data(new PlayerSpawnId(122_628), level: 81)]);
    }

    private static function data(PlayerSpawnId $spawnId, int $level): PlayerData
    {
        return new PlayerData(
            $spawnId,
            new PlayerName('Hero'),
            new PlayerLevel($level),
            new Gauge(new Experience(4_514_754), new Experience(5_280_000)),
            new HeroWallet(new Gold(6_800_132), new AdventureCoins(2899)),
            true,
            new Quantity(131),
            new Quantity(40),
            [self::daily(70)],
        );
    }

    private static function daily(int $number): QuestGroupId
    {
        return new QuestGroupId(QuestGroup::Daily, new QuestGroupNumber($number));
    }
}
