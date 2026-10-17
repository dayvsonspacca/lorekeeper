<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Command\Map;

use Lorekeeper\Client\Command\Map\JoinInitialMapCommand;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class JoinInitialMapCommandTest extends TestCase
{
    #[Test]
    public function it_serializes_to_message(): void
    {
        static::assertSame('%xt%zm%firstJoin%1%', new JoinInitialMapCommand()->toMessage());
    }
}
