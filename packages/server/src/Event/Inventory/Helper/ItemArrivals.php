<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Inventory\Helper;

use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use Lorekeeper\Server\Message\Message;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

/**
 * @psalm-type Entry = array{ItemID: positive-int, sName?: non-empty-string, bTemp?: bool, iQty: positive-int, iQtyNow?: positive-int, ...}
 */
final readonly class ItemArrivals
{
    /**
     * @param class-string<Event> $event
     *
     * @throws MalformedEventException
     *
     * @return null|list<array{ItemArrival, Entry}>
     */
    public static function of(Message $message, string $event): ?array
    {
        if (!$message instanceof JsonMessage || ($message->data['cmd'] ?? null) !== 'addItems') {
            return null;
        }

        try {
            $entries = Type\dict(Type\positive_int(), Type\shape([
                'ItemID' => Type\optional(Type\positive_int()),
                'sName' => Type\optional(Type\non_empty_string()),
                'bTemp' => Type\optional(Type\bool()),
                'iQty' => Type\positive_int(),
                'iQtyNow' => Type\optional(Type\positive_int()),
            ], allowUnknownFields: true))->coerce($message->data['items'] ?? null);
        } catch (CoercionException $exception) {
            throw new MalformedEventException($event, 'items are missing or invalid', $exception);
        }

        $sorted = [];
        foreach ($entries as $id => $entry) {
            $sorted[] = [self::kind($entry, $event), ['ItemID' => $id, ...$entry]];
        }

        return $sorted;
    }

    /**
     * @param array{sName?: non-empty-string, bTemp?: bool, iQtyNow?: positive-int, ...} $entry
     * @param class-string<Event> $event
     *
     * @throws MalformedEventException
     */
    private static function kind(array $entry, string $event): ItemArrival
    {
        return match (true) {
            array_key_exists('sName', $entry) && ($entry['bTemp'] ?? false) => ItemArrival::QuestItemReceived,
            array_key_exists('sName', $entry) => throw new MalformedEventException(
                $event,
                'a new item came straight into the inventory, which is not read yet',
            ),
            array_key_exists('iQtyNow', $entry) => ItemArrival::ItemIncreased,
            array_key_exists('ItemID', $entry) => ItemArrival::QuestItemIncreased,
            default => throw new MalformedEventException($event, 'an added item matches no known shape'),
        };
    }
}
