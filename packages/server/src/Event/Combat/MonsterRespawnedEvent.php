<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Combat;

use Lorekeeper\Core\World\Monster\MonsterSpawnId;
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
 * A monster in the room came back to life.
 */
final readonly class MonsterRespawnedEvent implements Event
{
    use NamedAfterClass;

    public function __construct(
        public MonsterSpawnId $id,
    ) {}

    /** @return Option\Option<static> */
    #[Override]
    public static function from(Message $message): Option\Option
    {
        if (!$message instanceof DelimitedMessage || ($message->data[1] ?? null) !== 'respawnMon') {
            return Option\none();
        }

        try {
            $id = Type\positive_int()->coerce($message->data[3] ?? null);
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'monster id is missing or invalid', $exception);
        }

        return Option\some(new static(new MonsterSpawnId($id)));
    }

    #[Override]
    public function summary(): string
    {
        return "Monster {$this->id->value} came back";
    }
}
