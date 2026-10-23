<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Game\Setting;

enum GameSetting
{
    case Goto;
    case Whispers;
    case FriendRequests;
    case PartyInvites;
    case GuildInvites;
    case Duels;
}
