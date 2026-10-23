<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Steps\Checks;

use Generator;
use Lorekeeper\Bot\Steps\Check;
use Lorekeeper\Bot\Steps\StepScript;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Support\Quantity;
use Override;

final readonly class Holding implements Check
{
    public function __construct(
        public ItemId $item,
        public Quantity $quantity,
    ) {}

    #[Override]
    public function describe(): string
    {
        return "Holding item {$this->item->value} ×{$this->quantity->value}";
    }

    #[Override]
    public function holds(StepScript $bot): Generator
    {
        yield from [];

        return $bot->me()->hero()->inventory->quantity($this->item) >= $this->quantity->value;
    }
}
