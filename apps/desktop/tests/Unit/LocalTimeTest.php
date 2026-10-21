<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests\Unit;

use Lorekeeper\Apps\Desktop\Native\LocalTime;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LocalTimeTest extends TestCase
{
    #[Test]
    public function it_finds_a_timezone_php_knows_on_this_computer(): void
    {
        static::assertContains(LocalTime::detect(), timezone_identifiers_list());
    }
}
