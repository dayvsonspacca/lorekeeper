<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Player;

use Lorekeeper\Core\Attribute\Health;
use Lorekeeper\Core\Attribute\Mana;
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

/**
 * A player in the room died, went idle or started fighting.
 */
final readonly class PlayerStateUpdatedEvent extends PlayerUpdatedEvent
{
    public function __construct(
        PlayerName $name,
        public CombatState $state,
        public ?Health $health = null,
        public ?Mana $mana = null,
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
                    'intHP' => Type\optional(Type\uint()),
                    'intMP' => Type\optional(Type\uint()),
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

        $health = $update['o']['intHP'] ?? null;
        $mana = $update['o']['intMP'] ?? null;

        return Option\some(
            new static(
                new PlayerName($name),
                CombatStates::read($state),
                $health === null ? null : new Health($health),
                $mana === null ? null : new Mana($mana),
            ),
        );
    }

    #[Override]
    public function summary(): string
    {
        return "{$this->name->value} is " . strtolower($this->state->name);
    }
}
