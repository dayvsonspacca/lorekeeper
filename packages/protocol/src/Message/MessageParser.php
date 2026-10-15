<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Message;

use Psl\Option;

final readonly class MessageParser
{
    private const string COMPRESSED_PREFIX = 'Z';

    /** @return Option\Option<Message> */
    public static function parse(string $raw): Option\Option
    {
        return self::decompress($raw)
            ->andThen(static fn(string $message): Option\Option => XmlMessage::from($message)->or(JsonMessage::from(
                $message,
            ))->or(DelimitedMessage::from($message)));
    }

    /** @return Option\Option<string> */
    private static function decompress(string $raw): Option\Option
    {
        if (!str_starts_with($raw, self::COMPRESSED_PREFIX)) {
            return Option\some($raw);
        }

        $compressed = base64_decode(substr($raw, offset: 1), strict: true);
        if ($compressed === false) {
            return Option\none();
        }

        set_error_handler(static fn(): bool => true);
        try {
            $message = gzuncompress($compressed);
        } finally {
            restore_error_handler();
        }

        return $message === false ? Option\none() : Option\some($message);
    }
}
