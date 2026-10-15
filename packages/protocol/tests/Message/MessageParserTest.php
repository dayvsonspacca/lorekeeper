<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Tests\Message;

use Lorekeeper\Protocol\Message\DelimitedMessage;
use Lorekeeper\Protocol\Message\JsonMessage;
use Lorekeeper\Protocol\Message\MessageParser;
use Lorekeeper\Protocol\Message\XmlMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MessageParserTest extends TestCase
{
    private const string MOVE_TO_AREA = '{"t":"xt","b":{"r":-1,"o":{"cmd":"moveToArea","areaId":172760,"areaName":"battleon-2"}}}';

    #[Test]
    public function it_parses_xml(): void
    {
        $message = MessageParser::parse("<msg t='sys'><body action='joinOK' r='172760'/></msg>")->unwrap();

        static::assertInstanceOf(XmlMessage::class, $message);
    }

    #[Test]
    public function it_parses_json(): void
    {
        $message = MessageParser::parse(self::MOVE_TO_AREA)->unwrap();

        static::assertInstanceOf(JsonMessage::class, $message);
        static::assertSame('moveToArea', $message->data['cmd']);
    }

    #[Test]
    public function it_parses_delimited(): void
    {
        $message = MessageParser::parse('%xt%server%-1%Profanity filter On.%')->unwrap();

        static::assertInstanceOf(DelimitedMessage::class, $message);
    }

    #[Test]
    public function it_decompresses_messages_prefixed_with_z(): void
    {
        $message = MessageParser::parse(self::compress(self::MOVE_TO_AREA))->unwrap();

        static::assertInstanceOf(JsonMessage::class, $message);
        static::assertSame(172_760, $message->data['areaId']);
        static::assertSame(self::MOVE_TO_AREA, $message->raw);
    }

    #[Test]
    public function it_decompresses_any_kind_of_message(): void
    {
        $message = MessageParser::parse(self::compress('%xt%server%-1%You joined "battleon-2"%'))->unwrap();

        static::assertInstanceOf(DelimitedMessage::class, $message);
    }

    #[Test]
    public function it_returns_none_for_invalid_base64(): void
    {
        static::assertTrue(MessageParser::parse('Z!!!not base64!!!')->isNone());
    }

    #[Test]
    public function it_returns_none_for_invalid_compressed_data(): void
    {
        static::assertTrue(MessageParser::parse('Z' . base64_encode('not zlib'))->isNone());
    }

    #[Test]
    public function it_returns_none_for_unknown_format(): void
    {
        static::assertTrue(MessageParser::parse('hello')->isNone());
    }

    private static function compress(string $message): string
    {
        return 'Z' . base64_encode((string) gzcompress($message));
    }
}
