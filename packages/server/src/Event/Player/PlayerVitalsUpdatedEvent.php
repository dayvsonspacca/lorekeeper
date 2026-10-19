<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Player;

use InvalidArgumentException;
use Lorekeeper\Core\Attribute\Health;
use Lorekeeper\Core\Attribute\Mana;
use Lorekeeper\Core\Attribute\Vitals;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Support\Gauge;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use Lorekeeper\Server\Message\Message;
use Override;
use Psl\Option;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

final readonly class PlayerVitalsUpdatedEvent extends PlayerUpdatedEvent
{
    public function __construct(
        PlayerName $name,
        public Vitals $vitals,
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
                'unm' => Type\optional(Type\non_empty_string()),
                'o' => Type\optional(Type\shape([
                    'intHP' => Type\optional(Type\uint()),
                    'intHPMax' => Type\optional(Type\uint()),
                    'intMP' => Type\optional(Type\uint()),
                    'intMPMax' => Type\optional(Type\uint()),
                ], allowUnknownFields: true)),
            ], allowUnknownFields: true)->coerce($message->data);
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'player name or vitals are invalid', $exception);
        }

        $name = $update['unm'] ?? null;
        $health = $update['o']['intHP'] ?? null;
        $maxHealth = $update['o']['intHPMax'] ?? null;
        $mana = $update['o']['intMP'] ?? null;
        $maxMana = $update['o']['intMPMax'] ?? null;
        if ($name === null || $health === null || $maxHealth === null || $mana === null || $maxMana === null) {
            return Option\none();
        }

        try {
            return Option\some(
                new static(
                    new PlayerName($name),
                    new Vitals(
                        new Gauge(new Health($health), new Health($maxHealth)),
                        new Gauge(new Mana($mana), new Mana($maxMana)),
                    ),
                ),
            );
        } catch (InvalidArgumentException $exception) {
            throw new MalformedEventException(static::class, 'health or mana is above its maximum', $exception);
        }
    }

    #[Override]
    public function summary(): string
    {
        $health = $this->vitals->health;
        $mana = $this->vitals->mana;

        return (
            "{$this->name->value} has {$health->current}/{$health->maximum} health"
            . " and {$mana->current}/{$mana->maximum} mana"
        );
    }
}
