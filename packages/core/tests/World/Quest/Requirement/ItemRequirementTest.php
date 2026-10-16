<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\World\Quest\Requirement;

use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\World\Quest\Requirement\ItemRequirement;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ItemRequirementTest extends TestCase
{
    #[Test]
    public function it_holds_the_item(): void
    {
        static::assertSame(1234, new ItemRequirement(new ItemId(1234))->itemId->value);
    }
}
