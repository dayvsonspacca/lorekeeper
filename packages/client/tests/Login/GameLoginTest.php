<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Login;

use Lorekeeper\Client\ClientVersion;
use Lorekeeper\Client\Login\GameLogin;
use Lorekeeper\Core\Player\PlayerName;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class GameLoginTest extends TestCase
{
    #[Test]
    public function it_holds_player_token_and_version(): void
    {
        $login = new GameLogin(new PlayerName('Hero'), 'TOKEN', new ClientVersion('5.1'));

        static::assertSame('Hero', $login->player->value);
        static::assertSame('TOKEN', $login->token);
        static::assertSame('5.1', $login->version->value);
    }
}
