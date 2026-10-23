<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Tracking;

use Lorekeeper\Client\Tracking\Identity;
use Lorekeeper\Client\Tracking\WalletTracking;
use Lorekeeper\Core\Game\Level\Experience;
use Lorekeeper\Core\Item\Equipment\CharacterClass\ClassPoints;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemLevel;
use Lorekeeper\Core\Item\Misc\Enhancement\Enhancement;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementRarity;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementType;
use Lorekeeper\Core\Player\Hero\Inventory\HeroWallet;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Player\PlayerSpawnId;
use Lorekeeper\Core\Support\Gauge;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Core\World\Currency\AdventureCoins;
use Lorekeeper\Core\World\Currency\Gold;
use Lorekeeper\Core\World\Monster\MonsterSpawnId;
use Lorekeeper\Server\Event\Combat\ResurrectedEvent;
use Lorekeeper\Server\Event\Inventory\ItemsEnhancedEvent;
use Lorekeeper\Server\Event\Player\PlayerData;
use Lorekeeper\Server\Event\Player\PlayerDataLoadedEvent;
use Lorekeeper\Server\Event\Reward\MonsterRewardedEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Option;

final class WalletTrackingTest extends TestCase
{
    #[Test]
    public function it_takes_the_gold_and_coins_my_data_says_i_hold(): void
    {
        $wallet = new HeroWallet(new Gold(10), new AdventureCoins(1));

        static::assertFalse(WalletTracking::follow($wallet, self::data(1, 500, 50), self::me()));
        static::assertSame([10, 1], [$wallet->gold->value, $wallet->coins->value]);

        static::assertTrue(WalletTracking::follow($wallet, self::data(122_628, 500, 50), self::me()));
        static::assertSame([500, 50], [$wallet->gold->value, $wallet->coins->value]);
    }

    #[Test]
    public function rewards_with_gold_fill_the_wallet(): void
    {
        $wallet = new HeroWallet(new Gold(10));

        static::assertTrue(WalletTracking::follow($wallet, self::reward(15), self::me()));
        static::assertFalse(WalletTracking::follow($wallet, self::reward(0), self::me()));
        static::assertSame(25, $wallet->gold->value);
    }

    #[Test]
    public function enhancing_pays_what_the_server_charged(): void
    {
        $wallet = new HeroWallet(new Gold(1500));

        static::assertTrue(WalletTracking::follow($wallet, self::enhanced(1010), self::me()));
        static::assertFalse(WalletTracking::follow($wallet, self::enhanced(0), self::me()));
        static::assertSame(490, $wallet->gold->value);
    }

    #[Test]
    public function other_events_leave_the_wallet_as_it_is(): void
    {
        static::assertFalse(WalletTracking::follow(new HeroWallet(), new ResurrectedEvent(), self::me()));
    }

    private static function me(): Identity
    {
        return new Identity(new PlayerSpawnId(122_628), new PlayerName('Hero'));
    }

    private static function data(int $spawnId, int $gold, int $coins): PlayerDataLoadedEvent
    {
        return new PlayerDataLoadedEvent([new PlayerData(
            new PlayerSpawnId($spawnId),
            new PlayerName('Hero'),
            new PlayerLevel(1),
            new Gauge(new Experience(0), new Experience(1000)),
            new HeroWallet(new Gold($gold), new AdventureCoins($coins)),
            false,
            new Quantity(30),
            new Quantity(30),
            [],
        )]);
    }

    private static function reward(int $gold): MonsterRewardedEvent
    {
        return new MonsterRewardedEvent(new Gold($gold), new Experience(0), new ClassPoints(0), new MonsterSpawnId(4));
    }

    private static function enhanced(int $paid): ItemsEnhancedEvent
    {
        return new ItemsEnhancedEvent(
            [new ItemId(3)],
            new Enhancement(EnhancementType::Lucky, EnhancementRarity::PlusZero, new ItemLevel(21), Option\none()),
            new Gold($paid),
        );
    }
}
