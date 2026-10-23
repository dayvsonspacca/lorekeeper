<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Item\Equipment\Traits;

use Lorekeeper\Core\Item\Misc\Enhancement\Enhancement;
use Psl\Option;

interface Enchantable
{
    /** @var Option\Option<Enhancement> */
    public Option\Option $enhancement { get; }
}
