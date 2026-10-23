<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Bots;

use Lorekeeper\Apps\Desktop\Models\Account;
use Lorekeeper\Apps\Desktop\Models\Preferences;
use Lorekeeper\Client\Client;
use Lorekeeper\Client\Command\Settings\ChangeGameSettingCommand;
use Lorekeeper\Client\PeerClosedException;
use Lorekeeper\Client\Script\Disconnected;
use Lorekeeper\Client\Script\Stopped;
use Lorekeeper\Core\Game\Setting\GameSetting;
use Psl\Async\CancellationTokenInterface;
use Psl\DateTime\Duration;

final readonly class HideFromPlayers
{
    public static function apply(
        Preferences $preferences,
        Account $account,
        Client $client,
        CancellationTokenInterface $stop,
    ): null|Disconnected|Stopped {
        $known = $client->character()->hero()->settings;
        $changes = array_values(array_filter(
            GameSetting::cases(),
            static fn(GameSetting $setting): bool => $known?->allows($setting) !== !$preferences->blocks($setting),
        ));
        $blocked = array_map(self::named(...), array_values(array_filter($changes, $preferences->blocks(...))));
        if ($blocked !== []) {
            $account->log('Hiding from other players: ' . implode(', ', $blocked));
        }

        foreach ($changes as $setting) {
            try {
                $client->send(new ChangeGameSettingCommand($setting, allowed: !$preferences->blocks($setting)));
            } catch (PeerClosedException $closed) {
                return new Disconnected($closed->getMessage());
            }

            $waited = $client->wait(Duration::seconds(1), $stop);
            if ($waited !== null) {
                return $waited;
            }
        }

        return null;
    }

    public static function named(GameSetting $setting): string
    {
        return match ($setting) {
            GameSetting::Goto => 'goto',
            GameSetting::Whispers => 'whispers',
            GameSetting::FriendRequests => 'friend requests',
            GameSetting::PartyInvites => 'party invites',
            GameSetting::GuildInvites => 'guild invites',
            GameSetting::Duels => 'duels',
        };
    }
}
