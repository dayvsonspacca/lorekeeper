<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\World\Quest\Reward;

use Lorekeeper\Core\Currency\Gold;
use Lorekeeper\Core\World\Quest\Reward\GoldReward;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class GoldRewardTest extends TestCase
{
    #[Test]
    public function it_holds_the_gold(): void
    {
        static::assertSame(1500, new GoldReward(new Gold(1500))->gold->value);
    }
}
