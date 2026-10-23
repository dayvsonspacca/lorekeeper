<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Item\Equipment\CharacterClass;

use Lorekeeper\Core\Game\Rank\Traits\GrantsRank;
use Lorekeeper\Core\Support\NonNegativeInteger;

final readonly class ClassPoints extends NonNegativeInteger implements GrantsRank {}
