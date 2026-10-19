<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Support\Tag;

/**
 * @template T of Tag
 */
trait Taggable
{
    /** @var list<T> */
    public readonly array $tags;

    /** @param T $tag */
    public function has(Tag $tag): bool
    {
        return in_array($tag, $this->tags, strict: true);
    }
}
