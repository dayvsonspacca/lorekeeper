<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\Item\Misc;

use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemLevel;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\Misc\Boost;
use Lorekeeper\Core\Item\Traits\Stackable;
use Lorekeeper\Core\Support\Quantity;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class BoostTest extends TestCase
{
    #[Test]
    public function a_boost_stacks_up_to_its_limit(): void
    {
        $boost = new Boost(
            new ItemId(19_189),
            new ItemName('Experience Boost! (60 min)'),
            new ItemLevel(1),
            new Quantity(99),
        );

        static::assertInstanceOf(Stackable::class, $boost);
        static::assertSame(99, $boost->stack->value);
    }
}
