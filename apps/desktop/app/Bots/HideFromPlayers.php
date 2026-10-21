<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Bots;

use Lorekeeper\Apps\Desktop\Models\Account;
use Lorekeeper\Apps\Desktop\Models\Preferences;
use Lorekeeper\Client\Client;
use Lorekeeper\Client\Command\Settings\ChangeGameSettingCommand;
use Lorekeeper\Client\Command\Settings\GameSetting;
use Lorekeeper\Client\PeerClosedException;
use Lorekeeper\Client\Script\Disconnected;
use Lorekeeper\Client\Script\Stopped;
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
        $blocked = [];
        foreach (GameSetting::cases() as $setting) {
            $blocks = $preferences->blocks($setting);
            try {
                $client->send(new ChangeGameSettingCommand($setting, allowed: !$blocks));
            } catch (PeerClosedException $closed) {
                return new Disconnected($closed->getMessage());
            }

            if ($blocks) {
                $blocked[] = self::named($setting);
            }

            $waited = $client->wait(Duration::seconds(1), $stop);
            if ($waited !== null) {
                return $waited;
            }
        }

        if ($blocked !== []) {
            $account->log('Hidden from other players: ' . implode(', ', $blocked));
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
