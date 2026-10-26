<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Player\Helper;

use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use Psl\Option;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

final readonly class PlayerEntries
{
    /**
     * @param class-string<Event> $event
     *
     * @throws MalformedEventException
     *
     * @return Option\Option<array{data: array<string, mixed>, ...}>
     */
    public static function hero(JsonMessage $message, string $event): Option\Option
    {
        $heroes = array_values(array_filter(self::all($message, $event), self::isHero(...)));
        if (count($heroes) > 1) {
            throw new MalformedEventException($event, 'the data is of more than one hero');
        }

        return $heroes === [] ? Option\none() : Option\some($heroes[0]);
    }

    /**
     * @param class-string<Event> $event
     *
     * @throws MalformedEventException
     *
     * @return list<array{data: array<string, mixed>, ...}>
     */
    public static function others(JsonMessage $message, string $event): array
    {
        return array_values(array_filter(
            self::all($message, $event),
            static fn(array $entry): bool => !self::isHero($entry),
        ));
    }

    /**
     * @param class-string<Event> $event
     *
     * @throws MalformedEventException
     *
     * @return list<array{data: array<string, mixed>, ...}>
     */
    private static function all(JsonMessage $message, string $event): array
    {
        try {
            return Type\vec(Type\shape([
                'data' => Type\dict(Type\string(), Type\mixed()),
            ], allowUnknownFields: true))
                ->coerce($message->data['a'] ?? null);
        } catch (CoercionException $exception) {
            throw new MalformedEventException($event, 'player data is missing or invalid', $exception);
        }
    }

    /** @param array{data: array<string, mixed>, ...} $entry */
    private static function isHero(array $entry): bool
    {
        return array_key_exists('intGold', $entry['data']);
    }
}
