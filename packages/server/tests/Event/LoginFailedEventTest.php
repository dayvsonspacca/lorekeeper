<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event;

use Lorekeeper\Server\Event\LoginFailedEvent;
use Lorekeeper\Server\Message\DelimitedMessage;
use Lorekeeper\Server\Message\XmlMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LoginFailedEventTest extends TestCase
{
    #[Test]
    public function it_matches_failed_login(): void
    {
        $message = DelimitedMessage::from(
            '%xt%loginResponse%-1%0%-1%%Your game client is an older version. Server requires AQW game version 5.1 or higher. Please close the AQW game window and try again.%',
        )->unwrap();

        static::assertTrue(LoginFailedEvent::from($message)->isSome());
    }

    #[Test]
    public function it_matches_failed_login_with_false_flag(): void
    {
        $message = DelimitedMessage::from('%xt%loginResponse%-1%false%-1%%')->unwrap();

        static::assertTrue(LoginFailedEvent::from($message)->isSome());
    }

    #[Test]
    public function it_ignores_successful_login(): void
    {
        $message = DelimitedMessage::from('%xt%loginResponse%-1%true%59878%hero%Welcome!%')->unwrap();

        static::assertTrue(LoginFailedEvent::from($message)->isNone());
    }

    #[Test]
    public function it_ignores_other_delimited_command(): void
    {
        $message = DelimitedMessage::from('%xt%server%-1%Profanity filter On.%')->unwrap();

        static::assertTrue(LoginFailedEvent::from($message)->isNone());
    }

    #[Test]
    public function it_ignores_xml_message(): void
    {
        $message = XmlMessage::from('<msg t="sys"><body action="login" /></msg>')->unwrap();

        static::assertTrue(LoginFailedEvent::from($message)->isNone());
    }
}
