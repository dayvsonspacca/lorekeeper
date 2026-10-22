<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Player;

use Lorekeeper\Core\Player\GameSetting;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Player\PlayerSettings;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\NamedAfterClass;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use Lorekeeper\Server\Message\Message;
use Lorekeeper\Server\SocketId;
use Override;
use Psl\Option;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

final readonly class PlayerSettingsLoadedEvent implements Event
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

    /** @param non-empty-list<PlayerSettingsData> $players */
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
                    'ia1' => Type\optional(Type\int()),
                ], allowUnknownFields: true),
            ], allowUnknownFields: true))
                ->coerce($message->data['a'] ?? null);
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'player settings are invalid', $exception);
        }

        $players = [];
        foreach ($entries as $entry) {
            if (!array_key_exists('ia1', $entry['data'])) {
                continue;
            }

            $players[] = new PlayerSettingsData(
                new SocketId($entry['uid']),
                new PlayerName($entry['data']['strUsername']),
                self::settings($entry['data']['ia1']),
            );
        }

        return $players === [] ? Option\none() : Option\some(new static($players));
    }

    #[Override]
    public function summary(): string
    {
        return 'Settings of '
        . implode(', ', array_map(
            static fn(PlayerSettingsData $player): string => $player->name->value,
            $this->players,
        ));
    }

    private static function settings(int $bits): PlayerSettings
    {
        $allowed = [];
        foreach (self::BITS as $bit => $setting) {
            if (($bits & (1 << $bit)) === 0) {
                continue;
            }

            $allowed[] = $setting;
        }

        return new PlayerSettings($allowed);
    }
}
