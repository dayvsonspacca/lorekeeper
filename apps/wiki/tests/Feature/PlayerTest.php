<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Lorekeeper\Apps\Wiki\Models\Player;
use Lorekeeper\Apps\Wiki\Tests\TestCase;
use Lorekeeper\Core\Player\PlayerName;
use PHPUnit\Framework\Attributes\Test;

final class PlayerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_saves_a_player_once_whatever_the_case(): void
    {
        $first = Player::saw(new PlayerName('hero'));
        $again = Player::saw(new PlayerName('HERO'));

        static::assertSame($first->id, $again->id);
        static::assertSame(1, Player::query()->count());
        static::assertNull($first->aqw_id);
    }

    #[Test]
    public function it_keeps_the_name_as_the_game_shows_it(): void
    {
        Player::saw(new PlayerName('hero'));
        Player::saw(new PlayerName('Hero'));
        Player::saw(new PlayerName('hero'));

        static::assertSame('Hero', Player::query()->sole()->name);
    }
}
