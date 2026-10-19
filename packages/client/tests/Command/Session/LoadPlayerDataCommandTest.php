<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Command\Session;

use Lorekeeper\Client\Command\Session\LoadPlayerDataCommand;
use Lorekeeper\Server\AreaId;
use Lorekeeper\Server\SocketId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LoadPlayerDataCommandTest extends TestCase
{
    #[Test]
    public function it_asks_for_the_data_of_the_player_in_the_area(): void
    {
        static::assertSame(
            '%xt%zm%retrieveUserDatas%65897%45466%',
            new LoadPlayerDataCommand(new AreaId(65_897), new SocketId(45_466))->toMessage(),
        );
    }
}
