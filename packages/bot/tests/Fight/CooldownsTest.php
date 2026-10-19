<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Tests\Fight;

use Lorekeeper\Bot\Fight\Cooldowns;
use Lorekeeper\Core\Attribute\Mana;
use Lorekeeper\Core\CharacterClass\Skill\Skill;
use Lorekeeper\Core\CharacterClass\Skill\SkillSlot;
use Lorekeeper\Core\CharacterClass\Skill\SkillTarget;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\DateTime\Duration;
use Psl\DateTime\Timestamp;

final class CooldownsTest extends TestCase
{
    #[Test]
    public function it_picks_an_attack_before_the_auto_attack_and_skips_what_it_cannot_afford(): void
    {
        $cooldowns = new Cooldowns();
        $skills = [self::auto(), self::bolt(cost: 20), self::heal()];

        static::assertSame(SkillSlot::First, $cooldowns->ready($skills, new Mana(100), self::at(0))?->slot);
        static::assertSame(SkillSlot::AutoAttack, $cooldowns->ready($skills, new Mana(10), self::at(0))?->slot);
    }

    #[Test]
    public function a_used_skill_waits_its_cooldown_and_any_skill_waits_a_second(): void
    {
        $skills = [self::auto(), self::bolt(cost: 20)];
        $cooldowns = new Cooldowns()->used(self::bolt(cost: 20), self::at(0));

        static::assertNull($cooldowns->ready($skills, new Mana(100), self::at(0)));
        static::assertSame(SkillSlot::AutoAttack, $cooldowns->ready($skills, new Mana(100), self::at(1))?->slot);
        static::assertSame(SkillSlot::First, $cooldowns->ready($skills, new Mana(100), self::at(6))?->slot);
    }

    #[Test]
    public function it_waits_until_the_next_skill_is_ready_but_never_too_little_or_too_long(): void
    {
        $cooldowns = new Cooldowns()->used(self::bolt(cost: 20), self::at(0));

        static::assertSame(1000, (int) $cooldowns->wait(self::at(0))->getTotalMilliseconds());
        static::assertSame(
            200,
            (int) $cooldowns->wait(self::at(0)->plus(Duration::milliseconds(900)))->getTotalMilliseconds(),
        );
        static::assertSame(
            2000,
            (int) new Cooldowns()
                ->wait(self::at(0))
                ->getTotalMilliseconds(),
        );
    }

    private static function at(int $seconds): Timestamp
    {
        return Timestamp::fromParts(2026, 10, 16)->plus(Duration::seconds($seconds));
    }

    private static function auto(): Skill
    {
        return new Skill(SkillSlot::AutoAttack, SkillTarget::Enemy, new Mana(0), Duration::seconds(2));
    }

    private static function bolt(int $cost): Skill
    {
        return new Skill(SkillSlot::First, SkillTarget::Enemy, new Mana($cost), Duration::seconds(6));
    }

    private static function heal(): Skill
    {
        return new Skill(SkillSlot::Fourth, SkillTarget::Self, new Mana(0), Duration::seconds(20));
    }
}
