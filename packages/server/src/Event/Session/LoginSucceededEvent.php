<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Session;

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
 * The server logged our player in.
 */
final readonly class LoginSucceededEvent implements Event
{
    use NamedAfterClass;

    public function __construct(
        public PlayerSpawnId $spawnId,
    ) {}

    /** @return Option\Option<static> */
    #[Override]
    public static function from(Message $message): Option\Option
    {
        if (!$message instanceof DelimitedMessage || ($message->data[1] ?? null) !== 'loginResponse') {
            return Option\none();
        }

        if (($message->data[3] ?? null) !== 'true') {
            return Option\none();
        }

        try {
            $spawnId = Type\positive_int()->coerce($message->data[4] ?? null);
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'spawn id is missing or invalid', $exception);
        }

        return Option\some(new static(new PlayerSpawnId($spawnId)));
    }

    #[Override]
    public function summary(): string
    {
        return "Logged in as spawn {$this->spawnId->value}";
    }
}
