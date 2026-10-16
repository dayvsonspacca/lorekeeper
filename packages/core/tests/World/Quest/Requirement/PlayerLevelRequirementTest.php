<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\World\Quest\Requirement;

use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\World\Quest\Requirement\PlayerLevelRequirement;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PlayerLevelRequirementTest extends TestCase
{
    #[Test]
    public function it_holds_the_level(): void
    {
        static::assertSame(30, new PlayerLevelRequirement(new PlayerLevel(30))->level->value);
    }
}
