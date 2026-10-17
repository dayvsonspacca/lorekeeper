<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Combat;

use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\NamedAfterClass;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use Lorekeeper\Server\Message\Message;
use Override;
use Psl\Option;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

abstract readonly class CombatRoundEvent implements Event
{
    use NamedAfterClass;

    /**
     * @param array<positive-int, array{intHP?: non-negative-int, intMP?: non-negative-int, intState?: int<0, 2>}> $monsters
     * @param array<non-empty-string, array{intHP?: non-negative-int, intMP?: non-negative-int, intState?: int<0, 2>}> $players
     *
     * @return Option\Option<static>
     */
    abstract protected static function read(array $monsters, array $players): Option\Option;

    /** @return Option\Option<static> */
    #[Override]
    final public static function from(Message $message): Option\Option
    {
        if (!$message instanceof JsonMessage || ($message->data['cmd'] ?? null) !== 'ct') {
            return Option\none();
        }

        $status = Type\shape([
            'intHP' => Type\optional(Type\uint()),
            'intMP' => Type\optional(Type\uint()),
            'intState' => Type\optional(Type\int_range(min: 0, max: 2)),
        ], allowUnknownFields: true);

        try {
            $round = Type\shape([
                'm' => Type\optional(Type\dict(Type\positive_int(), $status)),
                'p' => Type\optional(Type\dict(Type\non_empty_string(), $status)),
            ], allowUnknownFields: true)->coerce($message->data);
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'combat round is invalid', $exception);
        }

        return static::read($round['m'] ?? [], $round['p'] ?? []);
    }
}
