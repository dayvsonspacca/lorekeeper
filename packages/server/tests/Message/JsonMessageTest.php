<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Message;

use Lorekeeper\Server\Message\JsonMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class JsonMessageTest extends TestCase
{
    #[Test]
    public function it_returns_none_for_empty_string(): void
    {
        $result = JsonMessage::from('');

        static::assertTrue($result->isNone());
    }

    #[Test]
    public function it_returns_none_for_invalid_json(): void
    {
        $result = JsonMessage::from('not json');

        static::assertTrue($result->isNone());
    }

    #[Test]
    public function it_returns_none_when_missing_b_key(): void
    {
        $result = JsonMessage::from('{"x": {"o": {"cmd": "login"}}}');

        static::assertTrue($result->isNone());
    }

    #[Test]
    public function it_returns_none_when_missing_o_key(): void
    {
        $result = JsonMessage::from('{"b": {"x": {"cmd": "login"}}}');

        static::assertTrue($result->isNone());
    }

    #[Test]
    public function it_returns_some_for_valid_message(): void
    {
        $result = JsonMessage::from('{"b": {"o": {"cmd": "login"}}}');

        static::assertTrue($result->isSome());
    }

    #[Test]
    public function it_stores_raw_string(): void
    {
        $raw = '{"b": {"o": {"cmd": "login"}}}';
        $message = JsonMessage::from($raw)->unwrap();

        static::assertSame($raw, $message->raw);
    }

    #[Test]
    public function it_stores_data_from_b_o(): void
    {
        $raw = '{"b": {"o": {"cmd": "login", "area": "battleon"}}}';
        $message = JsonMessage::from($raw)->unwrap();

        static::assertSame(['cmd' => 'login', 'area' => 'battleon'], $message->data);
    }

    #[Test]
    public function it_stores_empty_data_when_o_is_empty(): void
    {
        $raw = '{"b": {"o": {}}}';
        $message = JsonMessage::from($raw)->unwrap();

        static::assertSame([], $message->data);
    }
}
