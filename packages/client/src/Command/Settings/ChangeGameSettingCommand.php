<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Command\Settings;

use Lorekeeper\Client\Command\Command;
use Lorekeeper\Core\Game\Setting\GameSetting;
use Override;

final readonly class ChangeGameSettingCommand implements Command
{
    public function __construct(
        private GameSetting $setting,
        private bool $allowed,
    ) {}

    #[Override]
    public function toMessage(): string
    {
        $setting = match ($this->setting) {
            GameSetting::Goto => 'bGoto',
            GameSetting::Whispers => 'bWhisper',
            GameSetting::FriendRequests => 'bFriend',
            GameSetting::PartyInvites => 'bParty',
            GameSetting::GuildInvites => 'bGuild',
            GameSetting::Duels => 'bDuel',
        };
        $allowed = $this->allowed ? 'true' : 'false';

        return "%xt%zm%cmd%1%uopref%{$setting}%{$allowed}%";
    }
}
