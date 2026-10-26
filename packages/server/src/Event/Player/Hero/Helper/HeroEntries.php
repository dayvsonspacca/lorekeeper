<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Player\Hero\Helper;

use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use Psl\Option;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

final readonly class HeroEntries
{
    /**
     * @param class-string<Event> $event
     *
     * @throws MalformedEventException
     *
     * @return Option\Option<array<array-key, mixed>>
     */
    public static function find(JsonMessage $message, string $event): Option\Option
    {
        try {
            $entries = Type\vec(Type\shape([
                'data' => Type\dict(Type\string(), Type\mixed()),
            ], allowUnknownFields: true))
                ->coerce($message->data['a'] ?? null);
        } catch (CoercionException $exception) {
            throw new MalformedEventException($event, 'player data is missing or invalid', $exception);
        }

        $heroes = array_values(array_filter($entries, static fn(array $entry): bool => array_key_exists(
            'intGold',
            $entry['data'],
        )));
        if (count($heroes) > 1) {
            throw new MalformedEventException($event, 'the data is of more than one hero');
        }

        return $heroes === [] ? Option\none() : Option\some($heroes[0]);
    }
}
