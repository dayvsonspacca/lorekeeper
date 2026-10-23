<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\World\Quest\Requirement;

use Lorekeeper\Core\Game\Rank\Rank;
use Lorekeeper\Core\World\Faction\FactionId;
use Lorekeeper\Core\World\Quest\Requirement\FactionRankRequirement;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FactionRankRequirementTest extends TestCase
{
    #[Test]
    public function it_holds_faction_and_rank(): void
    {
        $requirement = new FactionRankRequirement(new FactionId(5), new Rank(3));

        static::assertSame(5, $requirement->factionId->value);
        static::assertSame(3, $requirement->rank->value);
    }
}
