<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Tests\Message;

use Lorekeeper\Protocol\Message\DelimitedMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DelimitedMessageTest extends TestCase
{
    #[Test]
    public function it_returns_none_for_empty_string(): void
    {
        $result = DelimitedMessage::from('');

        static::assertTrue($result->isNone());
    }

    #[Test]
    public function it_returns_none_when_not_starting_with_percent(): void
    {
        $result = DelimitedMessage::from('xt%login%');

        static::assertTrue($result->isNone());
    }

    #[Test]
    public function it_returns_none_when_not_ending_with_percent(): void
    {
        $result = DelimitedMessage::from('%xt%login');

        static::assertTrue($result->isNone());
    }

    #[Test]
    public function it_returns_none_for_only_percent_signs(): void
    {
        $result = DelimitedMessage::from('%%');

        static::assertTrue($result->isNone());
    }

    #[Test]
    public function it_returns_some_for_valid_message(): void
    {
        $result = DelimitedMessage::from('%xt%login%');

        static::assertTrue($result->isSome());
    }

    #[Test]
    public function it_stores_raw_string(): void
    {
        $raw = '%xt%login%';
        $message = DelimitedMessage::from($raw)->unwrap();

        static::assertSame($raw, $message->raw);
    }

    #[Test]
    public function it_filters_empty_segments(): void
    {
        $message = DelimitedMessage::from('%xt%login%')->unwrap();

        static::assertSame(['xt', 'login'], $message->data);
    }

    #[Test]
    public function it_parses_multiple_segments(): void
    {
        $message = DelimitedMessage::from('%xt%server%zone%1%')->unwrap();

        static::assertSame(['xt', 'server', 'zone', '1'], $message->data);
    }
}
