<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Command;

use Lorekeeper\Client\Command\PolicyFileRequestCommand;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PolicyFileRequestCommandTest extends TestCase
{
    #[Test]
    public function it_serializes_to_message(): void
    {
        static::assertSame('<policy-file-request/>', new PolicyFileRequestCommand()->toMessage());
    }
}
