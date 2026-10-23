<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\World\Quest\Requirement;

use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemLevel;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\Misc\Resource;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Core\World\Quest\Requirement\ItemRequirement;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ItemRequirementTest extends TestCase
{
    #[Test]
    public function it_holds_item_and_quantity(): void
    {
        $gem = new Resource(new ItemId(1234), new ItemName('Realm Gem'), new ItemLevel(1), new Quantity(100));
        $requirement = new ItemRequirement($gem, new Quantity(1));

        static::assertSame($gem, $requirement->item);
        static::assertSame(1, $requirement->quantity->value);
    }
}
