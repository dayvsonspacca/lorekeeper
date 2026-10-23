<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Faction;

use Lorekeeper\Core\Game\Rank\Traits\GrantsRank;
use Lorekeeper\Core\Support\NonNegativeInteger;

final readonly class Reputation extends NonNegativeInteger implements GrantsRank {}
