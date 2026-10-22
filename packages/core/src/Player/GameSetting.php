<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Player;

enum GameSetting
{
    case Goto;
    case Whispers;
    case FriendRequests;
    case PartyInvites;
    case GuildInvites;
    case Duels;
}
