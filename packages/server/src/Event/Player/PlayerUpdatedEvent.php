<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Player;

use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\NamedAfterClass;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\DelimitedMessage;
use Lorekeeper\Server\Message\JsonMessage;
use Lorekeeper\Server\Message\Message;
use Override;
use Psl\Option;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

/**
 * Something changed about a player in the room.
 */
readonly class PlayerUpdatedEvent implements Event
{
    use NamedAfterClass;

    public function __construct(
        public PlayerName $name,
    ) {}

    /**
     * @return Option\Option<static>
     *
     * @mago-expect analysis:unsafe-instantiation
     */
    #[Override]
    public static function from(Message $message): Option\Option
    {
        $name = match (true) {
            $message instanceof JsonMessage && ($message->data['cmd'] ?? null) === 'uotls' => Option\some(
                $message->data['unm'] ?? null,
            ),
            $message instanceof DelimitedMessage && ($message->data[1] ?? null) === 'uotls' => Option\some(
                $message->data[3] ?? null,
            ),
            default => Option\none(),
        };

        if ($name->isNone()) {
            return Option\none();
        }

        try {
            $name = Type\non_empty_string()->coerce($name->unwrap());
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'player name is missing or invalid', $exception);
        }

        return Option\some(new static(new PlayerName($name)));
    }

    #[Override]
    public function summary(): string
    {
        return "{$this->name->value} updated";
    }
}
