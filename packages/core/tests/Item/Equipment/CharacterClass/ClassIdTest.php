<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\Item\Equipment\CharacterClass;

use Lorekeeper\Core\Item\Equipment\CharacterClass\ClassId;
use Lorekeeper\Core\Item\ItemId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ClassIdTest extends TestCase
{
    #[Test]
    public function a_class_is_an_item(): void
    {
        $healer = new ClassId(15_651);

        static::assertInstanceOf(ItemId::class, $healer);
        static::assertSame(15_651, $healer->value);
    }
}
