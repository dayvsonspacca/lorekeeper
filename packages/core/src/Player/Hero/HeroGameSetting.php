<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Player\Hero;

enum HeroGameSetting
{
    case Goto;
    case Whispers;
    case FriendRequests;
    case PartyInvites;
    case GuildInvites;
    case Duels;
}
