<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Command\Map;

use Lorekeeper\Client\Command\Map\JoinHouseCommand;
use Lorekeeper\Core\Player\PlayerName;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class JoinHouseCommandTest extends TestCase
{
    #[Test]
    public function it_serializes_to_message(): void
    {
        static::assertSame('%xt%zm%house%1%Hero%', new JoinHouseCommand(new PlayerName('Hero'))->toMessage());
    }
}
