<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Command\Combat;

use Lorekeeper\Client\Command\Combat\ResurrectCommand;
use Lorekeeper\Server\AreaId;
use Lorekeeper\Server\SocketId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ResurrectCommandTest extends TestCase
{
    #[Test]
    public function it_serializes_the_timed_resurrection(): void
    {
        static::assertSame(
            '%xt%zm%resPlayerTimed%733017%122628%',
            new ResurrectCommand(new AreaId(733_017), new SocketId(122_628))->toMessage(),
        );
    }
}
