<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Player\Hero;

use Lorekeeper\Core\Game\Level\Experience;
use Lorekeeper\Core\Player\Hero\Inventory\HeroWallet;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Player\PlayerSpawnId;
use Lorekeeper\Core\Support\Gauge;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Core\World\Currency\AdventureCoins;
use Lorekeeper\Core\World\Currency\Gold;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\NamedAfterClass;
use Lorekeeper\Server\Event\Player\Helper\PlayerEntries;
use Lorekeeper\Server\Event\Quest\Helper\QuestGroups;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use Lorekeeper\Server\Message\Message;
use Override;
use Psl\Option;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

/**
 * The server sent our player's level, wallet, membership, bag slots and quests.
 */
final readonly class HeroDataLoadedEvent implements Event
{
    use NamedAfterClass;

    public function __construct(
        public HeroData $data,
    ) {}

    /** @return Option\Option<static> */
    #[Override]
    public static function from(Message $message): Option\Option
    {
        if (!$message instanceof JsonMessage || ($message->data['cmd'] ?? null) !== 'initUserDatas') {
            return Option\none();
        }

        $hero = PlayerEntries::hero($message, static::class);
        if ($hero->isNone()) {
            return Option\none();
        }

        try {
            $entry = Type\shape([
                'uid' => Type\positive_int(),
                'data' => Type\shape([
                    'strUsername' => Type\non_empty_string(),
                    'intLevel' => Type\int_range(min: 1, max: 100),
                    'intExp' => Type\uint(),
                    'intExpToLevel' => Type\uint(),
                    'intGold' => Type\uint(),
                    'intCoins' => Type\uint(),
                    'iUpg' => Type\int(),
                    'iUpgDays' => Type\int(),
                    'iBagSlots' => Type\positive_int(),
                    'iBankSlots' => Type\positive_int(),
                ], allowUnknownFields: true),
            ], allowUnknownFields: true)->coerce($hero->unwrap());
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'hero data is missing or invalid', $exception);
        }

        $data = $entry['data'];

        return Option\some(
            new static(
                new HeroData(
                    new PlayerSpawnId($entry['uid']),
                    new PlayerName($data['strUsername']),
                    new PlayerLevel($data['intLevel']),
                    new Gauge(
                        new Experience($data['intExp']),
                        new Experience(max($data['intExp'], $data['intExpToLevel'])),
                    ),
                    new HeroWallet(new Gold($data['intGold']), new AdventureCoins($data['intCoins'])),
                    $data['iUpg'] > 0 && $data['iUpgDays'] >= 0,
                    new Quantity($data['iBagSlots']),
                    new Quantity($data['iBankSlots']),
                    QuestGroups::held($data),
                ),
            ),
        );
    }

    #[Override]
    public function summary(): string
    {
        return "Data of {$this->data->name->value}";
    }
}
