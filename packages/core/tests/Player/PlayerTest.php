<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\Player;

use Lorekeeper\Core\Game\Attribute\Health;
use Lorekeeper\Core\Game\Attribute\Mana;
use Lorekeeper\Core\Game\Attribute\Vitals;
use Lorekeeper\Core\Game\Combat\Traits\Targetable;
use Lorekeeper\Core\Player\Player;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Player\PlayerSpawnId;
use Lorekeeper\Core\Player\PlayerTag;
use Lorekeeper\Core\Support\Gauge;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PlayerTest extends TestCase
{
    #[Test]
    public function a_player_is_known_by_its_spawn_id_and_can_be_targeted(): void
    {
        $player = self::player();

        static::assertSame(45_466, $player->spawnId->value);
        static::assertInstanceOf(Targetable::class, $player->spawnId);
    }

    #[Test]
    public function a_player_is_a_member_once_tagged_so(): void
    {
        $player = self::player();

        static::assertFalse($player->tags->has(PlayerTag::Member));

        $player->tags->add(PlayerTag::Member);

        static::assertTrue($player->tags->has(PlayerTag::Member));
    }

    private static function player(): Player
    {
        return new Player(
            new PlayerSpawnId(45_466),
            new PlayerName('Hero'),
            new PlayerLevel(81),
            new Vitals(new Gauge(new Health(2585), new Health(2585)), new Gauge(new Mana(100), new Mana(100))),
        );
    }
}
