<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Player;

use Lorekeeper\Core\Combat\CombatState;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Server\Event\Combat\Helper\CombatStates;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use Lorekeeper\Server\Message\Message;
use Override;
use Psl\Option;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

final readonly class PlayerStateUpdatedEvent extends PlayerUpdatedEvent
{
    public function __construct(
        PlayerName $name,
        public CombatState $state,
    ) {
        parent::__construct($name);
    }

    /** @return Option\Option<static> */
    #[Override]
    public static function from(Message $message): Option\Option
    {
        if (!$message instanceof JsonMessage || ($message->data['cmd'] ?? null) !== 'uotls') {
            return Option\none();
        }

        try {
            $update = Type\shape([
                'unm' => Type\optional(Type\mixed()),
                'o' => Type\optional(Type\shape([
                    'intState' => Type\optional(Type\int_range(min: 0, max: 2)),
                ], allowUnknownFields: true)),
            ], allowUnknownFields: true)->coerce($message->data);
            $state = $update['o']['intState'] ?? null;
            if ($state === null) {
                return Option\none();
            }

            $name = Type\non_empty_string()->coerce($update['unm'] ?? null);
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'player name or state is invalid', $exception);
        }

        return Option\some(new static(new PlayerName($name), CombatStates::read($state)));
    }

    #[Override]
    public function summary(): string
    {
        return "{$this->name->value} is " . strtolower($this->state->name);
    }
}
