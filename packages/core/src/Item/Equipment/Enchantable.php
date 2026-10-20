<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Item\Equipment;

use Lorekeeper\Core\Item\Misc\Enhancement\Enhancement;
use Psl\Option;

trait Enchantable
{
    /** @var Option\Option<Enhancement> */
    public readonly Option\Option $enhancement;
}
