<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Facts\Reading;

use Lorekeeper\Apps\Wiki\Facts\Fact;
use Lorekeeper\Server\Message\JsonMessage;
use Lorekeeper\Server\Message\Message;

final readonly class MessageFacts
{
    /** @return list<Fact<object, object>> */
    public static function of(Message $message): array
    {
        return $message instanceof JsonMessage ? self::items($message->data) : [];
    }

    /**
     * @param array<array-key, mixed> $data
     *
     * @return list<Fact<object, object>>
     */
    private static function items(array $data): array
    {
        $facts = array_key_exists('ItemID', $data) && array_key_exists('sName', $data)
            ? ItemFacts::of(new Entry($data))
            : [];

        foreach ($data as $value) {
            if (!is_array($value)) {
                continue;
            }

            $facts = [...$facts, ...self::items($value)];
        }

        return $facts;
    }
}
