<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\Item\Equipment\Weapon;

use Lorekeeper\Core\Item\Equipment\Weapon\DamageVariance;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Type\Exception\AssertException;

final class DamageVarianceTest extends TestCase
{
    #[Test]
    public function it_goes_from_a_steady_blow_to_almost_double(): void
    {
        static::assertSame(0, new DamageVariance(0)->percent);
        static::assertSame('99%', (string) new DamageVariance(99));
    }

    #[Test]
    public function it_cannot_vary_more_than_the_whole_blow(): void
    {
        $this->expectException(AssertException::class);

        new DamageVariance(101);
    }
}
