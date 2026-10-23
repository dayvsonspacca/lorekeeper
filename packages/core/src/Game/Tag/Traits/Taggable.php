<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Game\Tag\Traits;

use Lorekeeper\Core\Game\Tag\Tag;
use Lorekeeper\Core\Game\Tag\Tags;

/**
 * @template T of Tag
 */
trait Taggable
{
    /** @var Tags<T> */
    public readonly Tags $tags;
}
