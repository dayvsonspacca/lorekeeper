<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Player;

use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\DelimitedMessage;
use Lorekeeper\Server\Message\JsonMessage;
use Lorekeeper\Server\Message\Message;
use Override;
use Psl\Option;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

/**
 * A player in the room went away or came back.
 */
final readonly class PlayerAwayUpdatedEvent extends PlayerUpdatedEvent
{
    public function __construct(
        PlayerName $name,
        public bool $away,
    ) {
        parent::__construct($name);
    }

    /** @return Option\Option<static> */
    #[Override]
    public static function from(Message $message): Option\Option
    {
        return match (true) {
            $message instanceof DelimitedMessage && ($message->data[1] ?? null) === 'uotls' => self::delimited(
                $message,
            ),
            $message instanceof JsonMessage && ($message->data['cmd'] ?? null) === 'uotls' => self::json($message),
            default => Option\none(),
        };
    }

    #[Override]
    public function summary(): string
    {
        return $this->away ? "{$this->name->value} is away" : "{$this->name->value} is back";
    }

    /**
     * @throws MalformedEventException
     *
     * @return Option\Option<static>
     */
    private static function delimited(DelimitedMessage $message): Option\Option
    {
        $matches = [];
        if (preg_match('/(?:^|,)afk:([^,]*)/', $message->data[4] ?? '', $matches) !== 1) {
            return Option\none();
        }

        $away = $matches[1];
        if ($away !== 'true' && $away !== 'false') {
            throw new MalformedEventException(static::class, "away is '{$away}', not true or false");
        }

        return Option\some(new static(self::player($message->data[3] ?? null), $away === 'true'));
    }

    /**
     * @throws MalformedEventException
     *
     * @return Option\Option<static>
     */
    private static function json(JsonMessage $message): Option\Option
    {
        try {
            $update = Type\shape([
                'unm' => Type\optional(Type\mixed()),
                'o' => Type\optional(Type\shape(['afk' => Type\optional(Type\bool())], allowUnknownFields: true)),
            ], allowUnknownFields: true)->coerce($message->data);
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'away is not true or false', $exception);
        }

        $away = $update['o']['afk'] ?? null;
        if ($away === null) {
            return Option\none();
        }

        return Option\some(new static(self::player($update['unm'] ?? null), $away));
    }

    /** @throws MalformedEventException */
    private static function player(mixed $name): PlayerName
    {
        try {
            return new PlayerName(Type\non_empty_string()->coerce($name));
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'player name is missing or invalid', $exception);
        }
    }
}
