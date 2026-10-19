<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Bots\Scene;

enum FeedKind: string
{
    case Defeated = 'defeated';
    case Died = 'died';
    case Revived = 'revived';
    case LeveledUp = 'leveled-up';
    case Travelled = 'travelled';
    case RankedUp = 'ranked-up';
}
