<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Map;

use DOMElement;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\Message;
use Lorekeeper\Server\Message\XmlMessage;
use Override;
use Psl\Option;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

final readonly class RoomJoinedEvent implements Event
{
    /** @param list<PlayerName> $players */
    public function __construct(
        public array $players,
    ) {}

    /** @return Option\Option<static> */
    #[Override]
    public static function from(Message $message): Option\Option
    {
        if (!$message instanceof XmlMessage) {
            return Option\none();
        }

        $body = $message->dom->getElementsByTagName('body')->item(0);
        if (!$body instanceof DOMElement || $body->getAttribute('action') !== 'joinOK') {
            return Option\none();
        }

        $players = [];
        foreach ($body->getElementsByTagName('u') as $user) {
            try {
                $name = Type\non_empty_string()->coerce($user->getElementsByTagName('n')->item(0)?->textContent);
            } catch (CoercionException $exception) {
                throw new MalformedEventException(static::class, 'a player name is missing or invalid', $exception);
            }

            $players[] = new PlayerName($name);
        }

        return Option\some(new static($players));
    }
}
