<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Player;

use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Player\PlayerSpawnId;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\NamedAfterClass;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\DelimitedMessage;
use Lorekeeper\Server\Message\Message;
use Override;
use Psl\Option;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

/**
 * A player left the room.
 */
final readonly class PlayerLeftEvent implements Event
{
    use NamedAfterClass;

    public function __construct(
        public PlayerSpawnId $spawnId,
        public PlayerName $name,
    ) {}

    /** @return Option\Option<static> */
    #[Override]
    public static function from(Message $message): Option\Option
    {
        if (!$message instanceof DelimitedMessage || ($message->data[1] ?? null) !== 'exitArea') {
            return Option\none();
        }

        try {
            $spawnId = Type\positive_int()->coerce($message->data[3] ?? null);
            $name = Type\non_empty_string()->coerce($message->data[4] ?? null);
        } catch (CoercionException $exception) {
            throw new MalformedEventException(
                static::class,
                'player spawn id or name is missing or invalid',
                $exception,
            );
        }

        return Option\some(new static(new PlayerSpawnId($spawnId), new PlayerName($name)));
    }

    #[Override]
    public function summary(): string
    {
        return "{$this->name->value} (spawn {$this->spawnId->value}) left";
    }
}
