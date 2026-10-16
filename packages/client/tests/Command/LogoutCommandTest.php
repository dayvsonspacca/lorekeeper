<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Command;

use Lorekeeper\Client\Command\LogoutCommand;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LogoutCommandTest extends TestCase
{
    #[Test]
    public function it_serializes_to_message(): void
    {
        static::assertSame('%xt%zm%logout%1%', new LogoutCommand()->toMessage());
    }
}
