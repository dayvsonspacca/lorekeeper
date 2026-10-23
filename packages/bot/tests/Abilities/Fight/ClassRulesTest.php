<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Tests\Abilities\Fight;

use Lorekeeper\Bot\Abilities\Fight\ClassRules;
use Lorekeeper\Bot\Abilities\Fight\Cooldowns;
use Lorekeeper\Client\Tests\Fixture;
use Lorekeeper\Core\Game\Attribute\Health;
use Lorekeeper\Core\Game\Attribute\Mana;
use Lorekeeper\Core\Game\Attribute\Vitals;
use Lorekeeper\Core\Item\Equipment\CharacterClass\ClassName;
use Lorekeeper\Core\Item\Equipment\CharacterClass\Skill\Cost\ManaCost;
use Lorekeeper\Core\Item\Equipment\CharacterClass\Skill\Skill;
use Lorekeeper\Core\Item\Equipment\CharacterClass\Skill\SkillSlot;
use Lorekeeper\Core\Item\Equipment\CharacterClass\Skill\SkillTarget;
use Lorekeeper\Core\Support\Gauge;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\DateTime\Duration;
use Psl\DateTime\Timestamp;

final class ClassRulesTest extends TestCase
{
    #[Test]
    public function the_oracle_keeps_its_heal_while_its_health_is_high(): void
    {
        static::assertSame(SkillSlot::First, self::pick(health: 900, mana: 100));
        static::assertSame(SkillSlot::First, self::pick(health: 500, mana: 100));
    }

    #[Test]
    public function the_oracle_heals_before_anything_else_once_it_is_hurt(): void
    {
        static::assertSame(SkillSlot::Second, self::pick(health: 499, mana: 100));
        static::assertSame(SkillSlot::Second, self::pick(health: 120, mana: 51));
    }

    #[Test]
    public function the_oracle_does_not_heal_without_half_its_mana(): void
    {
        static::assertSame(SkillSlot::First, self::pick(health: 120, mana: 50));
    }

    #[Test]
    public function a_class_without_rules_picks_as_before(): void
    {
        $rules = ClassRules::of(new ClassName('Mage'));

        static::assertFalse($rules->guards(self::heal()));
        static::assertTrue($rules->allows(self::heal(), self::vitals(health: 1000, mana: 100)));
    }

    private static function pick(int $health, int $mana): ?SkillSlot
    {
        return new Cooldowns()->ready(
            [
                Fixture::skill(
                    SkillSlot::AutoAttack,
                    SkillTarget::Enemy,
                    new ManaCost(new Mana(0)),
                    Duration::seconds(2),
                ),
                Fixture::skill(SkillSlot::First, SkillTarget::Self, new ManaCost(new Mana(5)), Duration::seconds(8)),
                self::heal(),
            ],
            self::vitals($health, $mana),
            ClassRules::of(new ClassName('Oracle')),
            Timestamp::fromParts(2026, 10, 19),
        )?->slot;
    }

    private static function heal(): Skill
    {
        return Fixture::skill(
            SkillSlot::Second,
            SkillTarget::Ally,
            new ManaCost(new Mana(30)),
            Duration::seconds(8),
            7,
        );
    }

    private static function vitals(int $health, int $mana): Vitals
    {
        return new Vitals(new Gauge(new Health($health), new Health(1000)), new Gauge(new Mana($mana), new Mana(100)));
    }
}
