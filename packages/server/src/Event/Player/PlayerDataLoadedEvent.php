<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Player;

use Lorekeeper\Core\Currency\AdventureCoins;
use Lorekeeper\Core\Currency\Gold;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\Player\PlayerLevelProgress;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Player\PlayerQuestMarks;
use Lorekeeper\Core\Player\Wallet;
use Lorekeeper\Core\Progression\Experience;
use Lorekeeper\Core\Support\Gauge;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\NamedAfterClass;
use Lorekeeper\Server\Event\Quest\Helper\QuestMarks;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use Lorekeeper\Server\Message\Message;
use Lorekeeper\Server\SocketId;
use Override;
use Psl\Option;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

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

        try {
            $entries = Type\non_empty_vec(Type\shape([
                'uid' => Type\positive_int(),
                'data' => Type\shape([
                    'strUsername' => Type\non_empty_string(),
                    'intLevel' => Type\int_range(min: 1, max: 100),
                    'intExp' => Type\uint(),
                    'intExpToLevel' => Type\uint(),
                    'intGold' => Type\uint(),
                    'intCoins' => Type\uint(),
                    'iUpg' => Type\int(),
                    'iBagSlots' => Type\positive_int(),
                ], allowUnknownFields: true),
            ], allowUnknownFields: true))
                ->coerce($message->data['a'] ?? null);

            $players = [];
            foreach ($entries as $entry) {
                $data = $entry['data'];
                $players[] = new PlayerData(
                    new SocketId($entry['uid']),
                    new PlayerName($data['strUsername']),
                    new PlayerLevelProgress(
                        new PlayerLevel($data['intLevel']),
                        new Gauge(
                            new Experience($data['intExp']),
                            new Experience(max($data['intExp'], $data['intExpToLevel'])),
                        ),
                    ),
                    new Wallet(new Gold($data['intGold']), new AdventureCoins($data['intCoins'])),
                    $data['iUpg'] > 0,
                    new Quantity($data['iBagSlots']),
                    new PlayerQuestMarks(QuestMarks::held($data)),
                );
            }
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'player data is missing or invalid', $exception);
        }

        return Option\some(new static($players));
    }

    #[Override]
    public function summary(): string
    {
        return 'Data of '
        . implode(', ', array_map(static fn(PlayerData $player): string => $player->name->value, $this->players));
    }
}
