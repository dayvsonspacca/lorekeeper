<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\Player;

use Lorekeeper\Core\Attribute\Health;
use Lorekeeper\Core\Attribute\Mana;
use Lorekeeper\Core\Attribute\Vitals;
use Lorekeeper\Core\Player\Player;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Player\PlayerTag;
use Lorekeeper\Core\Support\Gauge;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PlayerTest extends TestCase
{
    #[Test]
    public function a_player_is_a_member_once_tagged_so(): void
    {
        $player = new Player(
            new PlayerName('Hero'),
            new PlayerLevel(81),
            new Vitals(new Gauge(new Health(2585), new Health(2585)), new Gauge(new Mana(100), new Mana(100))),
        );

        static::assertFalse($player->tags->has(PlayerTag::Member));

        $player->tags->add(PlayerTag::Member);

        static::assertTrue($player->tags->has(PlayerTag::Member));
    }
}
