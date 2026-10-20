<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Tests\Abilities\Fight;

use Lorekeeper\Bot\Abilities\Fight\GlobalCooldown;
use Lorekeeper\Client\Tests\Fixture;
use Lorekeeper\Core\Attribute\Mana;
use Lorekeeper\Core\Attribute\Stat\Haste;
use Lorekeeper\Core\CharacterClass\Skill\Cost\ManaCost;
use Lorekeeper\Core\CharacterClass\Skill\Skill;
use Lorekeeper\Core\CharacterClass\Skill\SkillSlot;
use Lorekeeper\Core\CharacterClass\Skill\SkillTarget;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\DateTime\Duration;
use Psl\DateTime\Timestamp;

final class GlobalCooldownTest extends TestCase
{
    #[Test]
    public function skills_wait_a_second_and_a_half_between_them_while_the_auto_attack_fills_the_gap(): void
    {
        $cooldown = new GlobalCooldown()->used(self::skill(SkillSlot::First), self::at(0), self::still());

        static::assertTrue($cooldown->holds(self::skill(SkillSlot::AutoAttack), self::at(100)));
        static::assertFalse($cooldown->holds(self::skill(SkillSlot::AutoAttack), self::at(200)));
        static::assertTrue($cooldown->holds(self::skill(SkillSlot::Second), self::at(1000)));
        static::assertTrue($cooldown->holds(self::skill(SkillSlot::Third), self::at(1499)));
        static::assertFalse($cooldown->holds(self::skill(SkillSlot::Second), self::at(1500)));
        static::assertSame(SkillSlot::First, $cooldown->last);
    }

    #[Test]
    public function the_auto_attack_leaves_the_skills_waiting_as_they_were(): void
    {
        $cooldown = new GlobalCooldown()
            ->used(self::skill(SkillSlot::First), self::at(0), self::still())
            ->used(self::skill(SkillSlot::AutoAttack), self::at(1000), self::still());

        static::assertTrue($cooldown->holds(self::skill(SkillSlot::Second), self::at(1300)));
        static::assertFalse($cooldown->holds(self::skill(SkillSlot::Second), self::at(1500)));
        static::assertSame(SkillSlot::First, $cooldown->last);
    }

    #[Test]
    public function a_refusal_lets_the_skills_try_again_shortly_after_it(): void
    {
        $cooldown = new GlobalCooldown()
            ->used(self::skill(SkillSlot::First), self::at(0), self::still())
            ->refused(self::at(300));

        static::assertTrue($cooldown->holds(self::skill(SkillSlot::Second), self::at(500)));
        static::assertFalse($cooldown->holds(self::skill(SkillSlot::Second), self::at(550)));
        static::assertNull($cooldown->last);
    }

    private static function still(): Haste
    {
        return new Haste(0.0);
    }

    private static function at(int $milliseconds): Timestamp
    {
        return Timestamp::fromParts(2026, 10, 16)->plus(Duration::milliseconds($milliseconds));
    }

    private static function skill(SkillSlot $slot): Skill
    {
        return Fixture::skill($slot, SkillTarget::Enemy, new ManaCost(new Mana(0)), Duration::seconds(2));
    }
}
