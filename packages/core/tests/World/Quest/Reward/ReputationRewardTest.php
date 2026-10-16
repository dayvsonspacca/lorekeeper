<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\World\Quest\Reward;

use Lorekeeper\Core\Progression\Reputation;
use Lorekeeper\Core\World\Faction\FactionId;
use Lorekeeper\Core\World\Quest\Reward\ReputationReward;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ReputationRewardTest extends TestCase
{
    #[Test]
    public function it_holds_faction_and_reputation(): void
    {
        $reward = new ReputationReward(new FactionId(5), new Reputation(500));

        static::assertSame(5, $reward->factionId->value);
        static::assertSame(500, $reward->reputation->value);
    }
}
