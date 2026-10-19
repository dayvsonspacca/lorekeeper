<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Player;

use Lorekeeper\Core\Support\Tag\Tag;

enum PlayerTag implements Tag
{
    case Member;
}
