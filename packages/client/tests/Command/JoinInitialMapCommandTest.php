<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Command;

use Lorekeeper\Client\Command\JoinInitialMapCommand;
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
