<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Player\Hero;

use Lorekeeper\Core\Game\Setting\GameSetting;
use Lorekeeper\Core\Player\Hero\HeroSettings;
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
 * The server sent our player's game settings.
 */
final readonly class HeroSettingsLoadedEvent implements Event
{
    use NamedAfterClass;

    private const array BITS = [
        4 => GameSetting::Goto,
        7 => GameSetting::FriendRequests,
        8 => GameSetting::PartyInvites,
        9 => GameSetting::GuildInvites,
        10 => GameSetting::Whispers,
        13 => GameSetting::Duels,
    ];

    public function __construct(
        public HeroSettingsData $data,
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
                    'ia1' => Type\optional(Type\int()),
                ], allowUnknownFields: true),
            ], allowUnknownFields: true)->coerce($hero->unwrap());
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'hero settings are invalid', $exception);
        }

        if (!array_key_exists('ia1', $entry['data'])) {
            return Option\none();
        }

        return Option\some(
            new static(
                new HeroSettingsData(
                    new PlayerSpawnId($entry['uid']),
                    new PlayerName($entry['data']['strUsername']),
                    self::settings($entry['data']['ia1']),
                ),
            ),
        );
    }

    #[Override]
    public function summary(): string
    {
        return "Settings of {$this->data->name->value}";
    }

    private static function settings(int $bits): HeroSettings
    {
        $allowed = [];
        foreach (self::BITS as $bit => $setting) {
            if (($bits & (1 << $bit)) !== 0) {
                continue;
            }

            $allowed[] = $setting;
        }

        return new HeroSettings($allowed);
    }
}
