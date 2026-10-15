<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Tests\Exception;

use Lorekeeper\Protocol\Event\Event;
use Lorekeeper\Protocol\Exception\MalformedEventException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class MalformedEventExceptionTest extends TestCase
{
    #[Test]
    public function it_describes_the_event_and_reason(): void
    {
        $previous = new RuntimeException('boom');
        $exception = new MalformedEventException(Event::class, 'missing field', $previous);

        static::assertSame(Event::class, $exception->event);
        static::assertSame('Malformed ' . Event::class . ': missing field', $exception->getMessage());
        static::assertSame($previous, $exception->getPrevious());
    }
}
