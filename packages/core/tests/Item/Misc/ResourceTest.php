<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\Item\Misc;

use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemLevel;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\Misc\Resource;
use Lorekeeper\Core\Item\Traits\Stackable;
use Lorekeeper\Core\Support\Quantity;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ResourceTest extends TestCase
{
    #[Test]
    public function a_resource_stacks_up_to_its_limit(): void
    {
        $scales = new Resource(new ItemId(11_475), new ItemName('Dragon Scale'), new ItemLevel(1), new Quantity(1000));

        static::assertInstanceOf(Stackable::class, $scales);
        static::assertSame(1000, $scales->stack->value);
    }
}
