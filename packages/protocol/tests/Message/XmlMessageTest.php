<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Tests\Message;

use DOMDocument;
use Lorekeeper\Protocol\Message\XmlMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class XmlMessageTest extends TestCase
{
    #[Test]
    public function it_returns_none_for_empty_string(): void
    {
        $result = XmlMessage::from('');

        static::assertTrue($result->isNone());
    }

    #[Test]
    public function it_returns_none_for_invalid_xml(): void
    {
        $result = XmlMessage::from('not xml at all');

        static::assertTrue($result->isNone());
    }

    #[Test]
    public function it_returns_none_for_malformed_xml(): void
    {
        $result = XmlMessage::from('<unclosed>');

        static::assertTrue($result->isNone());
    }

    #[Test]
    public function it_returns_some_for_valid_xml(): void
    {
        $raw = '<msg t="sys"><body action="login" /></msg>';
        $result = XmlMessage::from($raw);

        static::assertTrue($result->isSome());
    }

    #[Test]
    public function it_stores_raw_string(): void
    {
        $raw = '<msg t="sys"><body action="login" /></msg>';
        $message = XmlMessage::from($raw)->unwrap();

        static::assertSame($raw, $message->raw);
    }

    #[Test]
    public function it_stores_parsed_dom(): void
    {
        $raw = '<msg t="sys"><body action="login" /></msg>';
        $message = XmlMessage::from($raw)->unwrap();

        static::assertInstanceOf(DOMDocument::class, $message->dom);
    }

    #[Test]
    public function it_parses_dom_correctly(): void
    {
        $raw = '<msg t="sys"><body action="login" /></msg>';
        $message = XmlMessage::from($raw)->unwrap();

        $root = $message->dom->documentElement;
        static::assertNotNull($root);
        static::assertSame('msg', $root->nodeName);
        static::assertSame('sys', $root->getAttribute('t'));
    }
}
