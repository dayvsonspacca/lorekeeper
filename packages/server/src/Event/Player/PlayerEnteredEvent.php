<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Player;

use DOMElement;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Player\PlayerSpawnId;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\NamedAfterClass;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\Message;
use Lorekeeper\Server\Message\XmlMessage;
use Override;
use Psl\Option;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

/**
 * A player entered the room.
 */
final readonly class PlayerEnteredEvent implements Event
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
        if (!$message instanceof XmlMessage) {
            return Option\none();
        }

        $body = $message->dom->getElementsByTagName('body')->item(0);
        if (!$body instanceof DOMElement || $body->getAttribute('action') !== 'uER') {
            return Option\none();
        }

        $user = $body->getElementsByTagName('u')->item(0);
        if (!$user instanceof DOMElement) {
            throw new MalformedEventException(static::class, 'the player is missing');
        }

        try {
            $spawnId = Type\positive_int()->coerce($user->getAttribute('i'));
            $name = Type\non_empty_string()->coerce($user->getElementsByTagName('n')->item(0)?->textContent);
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
        return "{$this->name->value} (spawn {$this->spawnId->value}) entered";
    }
}
