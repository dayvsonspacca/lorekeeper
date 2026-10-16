<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event;

use Lorekeeper\Server\Event\LoginSucceededEvent;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\DelimitedMessage;
use Lorekeeper\Server\Message\JsonMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LoginSucceededEventTest extends TestCase
{
    private const string SUCCESS = '%xt%loginResponse%-1%true%59878%hero%Welcome!%2026-10-15T08:51:16%sNews=1189,sMap=news/Map-UI_r38.swf%';

    private const string FAILURE = '%xt%loginResponse%-1%0%-1%%Your game client is an older version. Server requires AQW game version 5.1 or higher. Please close the AQW game window and try again.%';

    #[Test]
    public function it_matches_successful_login_with_socket_id(): void
    {
        $message = DelimitedMessage::from(self::SUCCESS)->unwrap();

        $event = LoginSucceededEvent::from($message)->unwrap();

        static::assertSame(59_878, $event->socketId->value);
    }

    #[Test]
    public function it_ignores_failed_login(): void
    {
        $message = DelimitedMessage::from(self::FAILURE)->unwrap();

        static::assertTrue(LoginSucceededEvent::from($message)->isNone());
    }

    #[Test]
    public function it_ignores_other_delimited_command(): void
    {
        $message = DelimitedMessage::from('%xt%server%-1%Profanity filter On.%')->unwrap();

        static::assertTrue(LoginSucceededEvent::from($message)->isNone());
    }

    #[Test]
    public function it_ignores_json_message(): void
    {
        $message = JsonMessage::from('{"b": {"o": {"cmd": "loginResponse"}}}')->unwrap();

        static::assertTrue(LoginSucceededEvent::from($message)->isNone());
    }

    #[Test]
    public function it_rejects_successful_login_without_socket_id(): void
    {
        $message = DelimitedMessage::from('%xt%loginResponse%-1%true%')->unwrap();

        $this->expectException(MalformedEventException::class);

        LoginSucceededEvent::from($message);
    }

    #[Test]
    public function it_rejects_successful_login_with_invalid_socket_id(): void
    {
        $message = DelimitedMessage::from('%xt%loginResponse%-1%true%abc%')->unwrap();

        $this->expectException(MalformedEventException::class);

        LoginSucceededEvent::from($message);
    }
}
