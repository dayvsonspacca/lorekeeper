<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Game\Tag;

/**
 * @template T of Tag
 */
final class Tags
{
    /** @var list<T> */
    private array $tags = [];

    /**
     * @param T $tag
     *
     * @return $this
     */
    public function add(Tag $tag): self
    {
        if (!$this->has($tag)) {
            $this->tags[] = $tag;
        }

        return $this;
    }

    /** @param T $tag */
    public function has(Tag $tag): bool
    {
        return in_array($tag, $this->tags, strict: true);
    }
}
