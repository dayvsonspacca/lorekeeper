<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\Knowledge;

use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Knowledge\Fact;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FactTest extends TestCase
{
    #[Test]
    public function it_holds_a_subject_and_a_value(): void
    {
        $fact = new Fact(new ItemId(447), new ItemName('Sweeping Broom'));

        static::assertSame(447, $fact->subject->value);
        static::assertSame('Sweeping Broom', $fact->value->value);
    }
}
