<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Tests\Abilities\Fight;

use Lorekeeper\Bot\Abilities\Fight\ClassRules;
use Lorekeeper\Bot\Abilities\Fight\Cooldowns;
use Lorekeeper\Client\Tests\Fixture;
use Lorekeeper\Core\Attribute\Health;
use Lorekeeper\Core\Attribute\Mana;
use Lorekeeper\Core\Attribute\Stat\Haste;
use Lorekeeper\Core\Attribute\Vitals;
use Lorekeeper\Core\CharacterClass\Skill\Cost\ManaCost;
use Lorekeeper\Core\CharacterClass\Skill\Cost\ManaRestore;
use Lorekeeper\Core\CharacterClass\Skill\Skill;
use Lorekeeper\Core\CharacterClass\Skill\SkillSlot;
use Lorekeeper\Core\CharacterClass\Skill\SkillTarget;
use Lorekeeper\Core\Support\Gauge;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\DateTime\Duration;
use Psl\DateTime\Timestamp;

final class CooldownsTest extends TestCase
{
    #[Test]
    public function it_picks_a_skill_before_the_auto_attack_and_skips_what_it_cannot_afford_and_the_potion(): void
    {
        $cooldowns = new Cooldowns();
        $skills = [self::auto(), self::bolt(cost: 20)];
        $potion = Fixture::skill(
            SkillSlot::Potion,
            SkillTarget::Ally,
            new ManaCost(new Mana(0)),
            Duration::seconds(60),
        );

        static::assertSame(
            SkillSlot::First,
            $cooldowns->ready($skills, self::vitals(mana: 100), ClassRules::none(), self::at(0))?->slot,
        );
        static::assertSame(
            SkillSlot::AutoAttack,
            $cooldowns->ready($skills, self::vitals(mana: 10), ClassRules::none(), self::at(0))?->slot,
        );
        static::assertSame(
            SkillSlot::AutoAttack,
            $cooldowns->ready([$potion, self::auto()], self::vitals(mana: 100), ClassRules::none(), self::at(0))?->slot,
        );
        static::assertNull($cooldowns->ready([$potion], self::vitals(mana: 100), ClassRules::none(), self::at(0)));

        $fire = Fixture::skill(
            SkillSlot::Second,
            SkillTarget::Enemy,
            new ManaRestore(new Mana(15)),
            Duration::seconds(7),
        );
        static::assertSame(
            SkillSlot::Second,
            $cooldowns->ready([self::auto(), $fire], self::vitals(mana: 0), ClassRules::none(), self::at(0))?->slot,
        );
    }

    #[Test]
    public function skills_on_itself_and_on_allies_take_their_turn_like_attacks(): void
    {
        $skills = [self::auto(), self::bolt(cost: 20), self::shield(), self::heal()];
        $cooldowns = new Cooldowns();

        static::assertSame(
            SkillSlot::First,
            $cooldowns->ready($skills, self::vitals(mana: 100), ClassRules::none(), self::at(0))?->slot,
        );

        $cooldowns = $cooldowns->used(self::bolt(cost: 20), self::at(0), new Haste(0.0));
        static::assertSame(
            SkillSlot::Third,
            $cooldowns->ready($skills, self::vitals(mana: 100), ClassRules::none(), self::at(1500))?->slot,
        );

        $cooldowns = $cooldowns->used(self::shield(), self::at(1500), new Haste(0.0));
        static::assertSame(
            SkillSlot::Fourth,
            $cooldowns->ready($skills, self::vitals(mana: 100), ClassRules::none(), self::at(3000))?->slot,
        );
    }

    #[Test]
    public function a_refused_skill_can_be_cast_again_a_moment_later(): void
    {
        $skills = [self::bolt(cost: 20)];
        $cooldowns = new Cooldowns()
            ->used(self::bolt(cost: 20), self::at(0), new Haste(0.0))
            ->refused(self::at(300));

        static::assertNull($cooldowns->ready($skills, self::vitals(mana: 100), ClassRules::none(), self::at(400)));
        static::assertSame(
            SkillSlot::First,
            $cooldowns->ready($skills, self::vitals(mana: 100), ClassRules::none(), self::at(550))?->slot,
        );
    }

    #[Test]
    public function it_waits_until_the_next_skill_is_ready_but_never_too_little_or_too_long(): void
    {
        $cooldowns = new Cooldowns()->used(self::bolt(cost: 20), self::at(0), new Haste(0.0));

        static::assertSame(200, (int) $cooldowns->wait(self::at(0))->getTotalMilliseconds());
        static::assertSame(1000, (int) $cooldowns->wait(self::at(500))->getTotalMilliseconds());
        static::assertSame(200, (int) $cooldowns->wait(self::at(1400))->getTotalMilliseconds());
        static::assertSame(
            2000,
            (int) new Cooldowns()
                ->wait(self::at(0))
                ->getTotalMilliseconds(),
        );
    }

    private static function vitals(int $mana): Vitals
    {
        return new Vitals(new Gauge(new Health(390), new Health(390)), new Gauge(new Mana($mana), new Mana(100)));
    }

    private static function at(int $milliseconds): Timestamp
    {
        return Timestamp::fromParts(2026, 10, 16)->plus(Duration::milliseconds($milliseconds));
    }

    private static function auto(): Skill
    {
        return Fixture::skill(
            SkillSlot::AutoAttack,
            SkillTarget::Enemy,
            new ManaCost(new Mana(0)),
            Duration::seconds(2),
        );
    }

    private static function bolt(int $cost): Skill
    {
        return Fixture::skill(
            SkillSlot::First,
            SkillTarget::Enemy,
            new ManaCost(new Mana($cost)),
            Duration::seconds(6),
        );
    }

    private static function shield(): Skill
    {
        return Fixture::skill(SkillSlot::Third, SkillTarget::Ally, new ManaCost(new Mana(15)), Duration::seconds(12));
    }

    private static function heal(): Skill
    {
        return Fixture::skill(SkillSlot::Fourth, SkillTarget::Self, new ManaCost(new Mana(0)), Duration::seconds(20));
    }
}
