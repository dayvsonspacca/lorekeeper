<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\Player\Hero\Progression;

use Lorekeeper\Core\Player\Hero\Progression\FactionsProgression;
use Lorekeeper\Core\World\Faction\FactionId;
use Lorekeeper\Core\World\Faction\Reputation;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FactionsProgressionTest extends TestCase
{
    #[Test]
    public function it_knows_the_reputation_and_rank_the_hero_holds_in_each_faction(): void
    {
        $factions = new FactionsProgression([5 => new Reputation(22_500)]);

        static::assertSame(22_500, $factions->reputation(new FactionId(5))->value);
        static::assertSame(5, $factions->rank(new FactionId(5))->value);
    }

    #[Test]
    public function a_faction_the_hero_never_served_starts_at_no_reputation(): void
    {
        $factions = new FactionsProgression();

        static::assertSame(0, $factions->reputation(new FactionId(5))->value);
        static::assertSame(1, $factions->rank(new FactionId(5))->value);
    }
}
