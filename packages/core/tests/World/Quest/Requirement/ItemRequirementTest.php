<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\World\Quest\Requirement;

use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Core\World\Quest\Requirement\ItemRequirement;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ItemRequirementTest extends TestCase
{
    #[Test]
    public function it_holds_item_and_quantity(): void
    {
        $requirement = new ItemRequirement(new ItemId(1234), new Quantity(1));

        static::assertSame(1234, $requirement->itemId->value);
        static::assertSame(1, $requirement->quantity->value);
    }
}
