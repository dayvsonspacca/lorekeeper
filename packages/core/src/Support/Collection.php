<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Support;

use Closure;
use Countable;
use InvalidArgumentException;
use Iterator;
use IteratorAggregate;
use Psl\Collection\MutableMap;
use Psl\Option;

/**
 * @template K of PositiveInteger|NonEmptyString
 * @template T
 *
 * @implements IteratorAggregate<array-key, T>
 */
abstract class Collection implements IteratorAggregate, Countable
{
    /** @var MutableMap<array-key, T> */
    private MutableMap $items;

    /**
     * @param list<T> $items
     *
     * @throws InvalidArgumentException
     */
    final public function __construct(array $items)
    {
        $this->items = new MutableMap([]);
        foreach ($items as $item) {
            $this->add($item);
        }
    }

    /**
     * @param T $item
     *
     * @return K
     */
    abstract protected static function key(mixed $item): PositiveInteger|NonEmptyString;

    /**
     * @param T $item
     *
     * @return $this
     *
     * @throws InvalidArgumentException
     */
    public function add(mixed $item): static
    {
        $key = static::key($item)->value;
        if ($this->items->contains($key)) {
            throw new InvalidArgumentException("{$key} is in the collection more than once.");
        }

        $this->items->add($key, $item);

        return $this;
    }

    /**
     * @param K $key
     *
     * @return $this
     *
     * @throws InvalidArgumentException
     */
    public function remove(PositiveInteger|NonEmptyString $key): static
    {
        if (!$this->items->contains($key->value)) {
            throw new InvalidArgumentException("{$key} is not in the collection.");
        }

        $this->items->remove($key->value);

        return $this;
    }

    /** @param T $item */
    public function has(mixed $item): bool
    {
        return $this->items->contains(static::key($item)->value);
    }

    /**
     * @param K $key
     *
     * @return Option\Option<T>
     */
    public function find(PositiveInteger|NonEmptyString $key): Option\Option
    {
        return Option\from_nullable($this->items->get($key->value));
    }

    /**
     * @param Closure(T): bool $fn
     *
     * @throws InvalidArgumentException
     */
    public function filter(Closure $fn): static
    {
        /** @var static<K, T> */
        return new static(
            $this->items
                ->filter($fn)
                ->values()
                ->toArray(),
        );
    }

    /** @return Iterator<array-key, T> */
    public function getIterator(): Iterator
    {
        return $this->items->getIterator();
    }

    public function count(): int
    {
        return $this->items->count();
    }
}
