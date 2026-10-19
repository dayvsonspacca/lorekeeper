<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Support\Tag;

/**
 * @template T of Tag
 */
trait Taggable
{
    /** @var Tags<T> */
    public readonly Tags $tags;
}
