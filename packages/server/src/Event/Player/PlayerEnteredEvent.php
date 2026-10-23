<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Player;

use DOMElement;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\NamedAfterClass;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\Message;
use Lorekeeper\Server\Message\XmlMessage;
use Lorekeeper\Server\SocketId;
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
        public SocketId $socketId,
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
            $socketId = Type\positive_int()->coerce($user->getAttribute('i'));
            $name = Type\non_empty_string()->coerce($user->getElementsByTagName('n')->item(0)?->textContent);
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'player socket or name is missing or invalid', $exception);
        }

        return Option\some(new static(new SocketId($socketId), new PlayerName($name)));
    }

    #[Override]
    public function summary(): string
    {
        return "{$this->name->value} (socket {$this->socketId->value}) entered";
    }
}
