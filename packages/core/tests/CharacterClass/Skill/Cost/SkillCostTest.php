<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\CharacterClass\Skill\Cost;

use Lorekeeper\Core\CharacterClass\Skill\Cost\ManaCost;
use Lorekeeper\Core\CharacterClass\Skill\Cost\ManaRestore;
use Lorekeeper\Core\Game\Attribute\Health;
use Lorekeeper\Core\Game\Attribute\Mana;
use Lorekeeper\Core\Game\Attribute\Vitals;
use Lorekeeper\Core\Support\Gauge;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SkillCostTest extends TestCase
{
    #[Test]
    public function a_skill_that_spends_mana_needs_that_much_mana(): void
    {
        $cost = new ManaCost(new Mana(20));

        static::assertTrue($cost->affordable(self::vitals(mana: 20)));
        static::assertFalse($cost->affordable(self::vitals(mana: 19)));
        static::assertTrue(new ManaCost(new Mana(0))->affordable(self::vitals(mana: 0)));
    }

    #[Test]
    public function a_skill_that_restores_mana_can_always_be_used(): void
    {
        $restore = new ManaRestore(new Mana(15));

        static::assertTrue($restore->affordable(self::vitals(mana: 0)));
        static::assertSame(15, $restore->mana->value);
    }

    private static function vitals(int $mana): Vitals
    {
        return new Vitals(new Gauge(new Health(390), new Health(390)), new Gauge(new Mana($mana), new Mana(100)));
    }
}
