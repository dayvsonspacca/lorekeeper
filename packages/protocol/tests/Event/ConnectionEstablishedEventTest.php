<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Tests\Event;

use Lorekeeper\Protocol\Event\ConnectionEstablishedEvent;
use Lorekeeper\Protocol\Message\DelimitedMessage;
use Lorekeeper\Protocol\Message\JsonMessage;
use Lorekeeper\Protocol\Message\XmlMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ConnectionEstablishedEventTest extends TestCase
{
    #[Test]
    public function it_matches_cross_domain_policy(): void
    {
        $message = XmlMessage::from(
            '<cross-domain-policy><allow-access-from domain="*" to-ports="*" /></cross-domain-policy>',
        )->unwrap();

        static::assertTrue(ConnectionEstablishedEvent::from($message)->isSome());
    }

    #[Test]
    public function it_ignores_other_xml_root(): void
    {
        $message = XmlMessage::from('<msg t="sys"><body action="login" /></msg>')->unwrap();

        static::assertTrue(ConnectionEstablishedEvent::from($message)->isNone());
    }

    #[Test]
    public function it_ignores_json_message(): void
    {
        $message = JsonMessage::from('{"b": {"o": {"cmd": "login"}}}')->unwrap();

        static::assertTrue(ConnectionEstablishedEvent::from($message)->isNone());
    }

    #[Test]
    public function it_ignores_delimited_message(): void
    {
        $message = DelimitedMessage::from('%xt%login%')->unwrap();

        static::assertTrue(ConnectionEstablishedEvent::from($message)->isNone());
    }
}
