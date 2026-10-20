<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Tests\Abilities\Fight;

use Lorekeeper\Bot\Abilities\Fight\ClassRules;
use Lorekeeper\Bot\Abilities\Fight\Cooldowns;
use Lorekeeper\Bot\Abilities\Fight\GlobalCooldown;
use Lorekeeper\Client\Tests\Fixture;
use Lorekeeper\Core\Attribute\Health;
use Lorekeeper\Core\Attribute\Mana;
use Lorekeeper\Core\Attribute\Stat\Haste;
use Lorekeeper\Core\Attribute\Vitals;
use Lorekeeper\Core\CharacterClass\Skill\Cost\ManaCost;
use Lorekeeper\Core\CharacterClass\Skill\Skill;
use Lorekeeper\Core\CharacterClass\Skill\SkillSlot;
use Lorekeeper\Core\CharacterClass\Skill\SkillTarget;
use Lorekeeper\Core\Support\Gauge;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\DateTime\Duration;
use Psl\DateTime\Timestamp;

final class HasteTest extends TestCase
{
    #[Test]
    public function haste_shortens_a_skills_own_cooldown_up_to_half(): void
    {
        $skills = [self::skill(SkillSlot::First, cooldown: 6)];
        $hasted = new Cooldowns()->used(self::skill(SkillSlot::First, cooldown: 6), self::at(0), new Haste(0.25));
        $capped = new Cooldowns()->used(self::skill(SkillSlot::First, cooldown: 6), self::at(0), new Haste(0.731));

        static::assertNull($hasted->ready($skills, self::vitals(), ClassRules::none(), self::at(4499)));
        static::assertNotNull($hasted->ready($skills, self::vitals(), ClassRules::none(), self::at(4500)));
        static::assertNotNull($capped->ready($skills, self::vitals(), ClassRules::none(), self::at(3000)));
    }

    #[Test]
    public function haste_brings_the_next_skill_closer_up_to_half_the_wait(): void
    {
        $hasted = new GlobalCooldown()->used(self::skill(SkillSlot::First), self::at(0), new Haste(0.25));
        $capped = new GlobalCooldown()->used(self::skill(SkillSlot::First), self::at(0), new Haste(0.731));

        static::assertTrue($hasted->holds(self::skill(SkillSlot::Second), self::at(1124)));
        static::assertFalse($hasted->holds(self::skill(SkillSlot::Second), self::at(1125)));
        static::assertFalse($capped->holds(self::skill(SkillSlot::Second), self::at(750)));
    }

    private static function vitals(): Vitals
    {
        return new Vitals(new Gauge(new Health(390), new Health(390)), new Gauge(new Mana(100), new Mana(100)));
    }

    private static function at(int $milliseconds): Timestamp
    {
        return Timestamp::fromParts(2026, 10, 16)->plus(Duration::milliseconds($milliseconds));
    }

    private static function skill(SkillSlot $slot, int $cooldown = 2): Skill
    {
        return Fixture::skill($slot, SkillTarget::Enemy, new ManaCost(new Mana(0)), Duration::seconds($cooldown));
    }
}
