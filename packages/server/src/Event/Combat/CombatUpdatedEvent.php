<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Combat;

use Lorekeeper\Core\Attribute\Health;
use Lorekeeper\Core\Attribute\Mana;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use Lorekeeper\Server\Message\Message;
use Lorekeeper\Server\MonsterMapId;
use Override;
use Psl\Option;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

final readonly class CombatUpdatedEvent implements Event
{
    /**
     * @param list<MonsterStatus> $monsters
     * @param list<PlayerStatus> $players
     */
    public function __construct(
        public array $monsters,
        public array $players,
    ) {}

    /** @return Option\Option<static> */
    #[Override]
    public static function from(Message $message): Option\Option
    {
        if (!$message instanceof JsonMessage || ($message->data['cmd'] ?? null) !== 'ct') {
            return Option\none();
        }

        $status = Type\shape([
            'intHP' => Type\optional(Type\uint()),
            'intMP' => Type\optional(Type\uint()),
            'intState' => Type\optional(Type\int_range(min: 0, max: 2)),
        ], allowUnknownFields: true);

        try {
            $data = Type\shape([
                'm' => Type\optional(Type\dict(Type\positive_int(), $status)),
                'p' => Type\optional(Type\dict(Type\non_empty_string(), $status)),
            ], allowUnknownFields: true)->coerce($message->data);
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'combat status is missing or invalid', $exception);
        }

        $monsters = [];
        foreach ($data['m'] ?? [] as $id => $monster) {
            $monsters[] = new MonsterStatus(
                new MonsterMapId($id),
                array_key_exists('intHP', $monster) ? new Health($monster['intHP']) : null,
                array_key_exists('intState', $monster) ? CombatState::from($monster['intState']) : null,
            );
        }

        $players = [];
        foreach ($data['p'] ?? [] as $name => $player) {
            $players[] = new PlayerStatus(
                new PlayerName($name),
                array_key_exists('intHP', $player) ? new Health($player['intHP']) : null,
                array_key_exists('intMP', $player) ? new Mana($player['intMP']) : null,
                array_key_exists('intState', $player) ? CombatState::from($player['intState']) : null,
            );
        }

        return Option\some(new static($monsters, $players));
    }
}
