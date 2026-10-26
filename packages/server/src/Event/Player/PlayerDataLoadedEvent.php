<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Player;

use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Player\PlayerSpawnId;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\NamedAfterClass;
use Lorekeeper\Server\Event\Player\Helper\PlayerEntries;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use Lorekeeper\Server\Message\Message;
use Override;
use Psl\Option;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

/**
 * The server sent what it shows about other players in the room.
 */
final readonly class PlayerDataLoadedEvent implements Event
{
    use NamedAfterClass;

    /** @param non-empty-list<PlayerData> $players */
    public function __construct(
        public array $players,
    ) {}

    /** @return Option\Option<static> */
    #[Override]
    public static function from(Message $message): Option\Option
    {
        if (!$message instanceof JsonMessage || ($message->data['cmd'] ?? null) !== 'initUserDatas') {
            return Option\none();
        }

        $players = array_map(self::player(...), PlayerEntries::others($message, static::class));
        if ($players === []) {
            return Option\none();
        }

        return Option\some(new static($players));
    }

    #[Override]
    public function summary(): string
    {
        $names = array_map(static fn(PlayerData $player): string => $player->name->value, $this->players);

        return 'Data of ' . implode(', ', $names);
    }

    /**
     * @param array{data: array<string, mixed>, ...} $entry
     *
     * @throws MalformedEventException
     */
    private static function player(array $entry): PlayerData
    {
        try {
            $entry = Type\shape([
                'uid' => Type\positive_int(),
                'data' => Type\shape([
                    'strUsername' => Type\non_empty_string(),
                    'intLevel' => Type\int_range(min: 1, max: 100),
                ], allowUnknownFields: true),
            ], allowUnknownFields: true)->coerce($entry);
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'player data is missing or invalid', $exception);
        }

        return new PlayerData(
            new PlayerSpawnId($entry['uid']),
            new PlayerName($entry['data']['strUsername']),
            new PlayerLevel($entry['data']['intLevel']),
        );
    }
}
