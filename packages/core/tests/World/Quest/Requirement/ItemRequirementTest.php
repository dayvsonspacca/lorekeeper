<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\World\Quest\Requirement;

use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\World\Quest\Requirement\ItemRequirement;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Type\Exception\AssertException;

final class ItemRequirementTest extends TestCase
{
    #[Test]
    public function it_holds_item_and_quantity(): void
    {
        $requirement = new ItemRequirement(new ItemId(1234), 1);

        static::assertSame(1234, $requirement->itemId->value);
        static::assertSame(1, $requirement->quantity);
    }

    #[Test]
    public function it_rejects_a_zero_quantity(): void
    {
        $this->expectException(AssertException::class);

        new ItemRequirement(new ItemId(1234), 0);
    }
}
