<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Shop;

use InvalidArgumentException;
use Lorekeeper\Core\Item\Item;

final readonly class MergeShopItem
{
    /** @var non-empty-list<MergeCost> */
    public array $costs;

    /**
     * @param list<MergeCost> $costs
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        public Item $item,
        array $costs,
    ) {
        if ($costs === []) {
            throw new InvalidArgumentException("Merging {$item->name} costs nothing.");
        }

        $this->costs = $costs;
    }
}
