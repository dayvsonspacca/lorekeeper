<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\Player\Hero\Class;

use Lorekeeper\Core\Game\Attribute\Mana;
use Lorekeeper\Core\Item\Equipment\CharacterClass\CharacterClass;
use Lorekeeper\Core\Item\Equipment\CharacterClass\ClassCategory;
use Lorekeeper\Core\Item\Equipment\CharacterClass\ClassName;
use Lorekeeper\Core\Item\Equipment\CharacterClass\ClassPoints;
use Lorekeeper\Core\Item\Equipment\CharacterClass\Passive\ClassPassive;
use Lorekeeper\Core\Item\Equipment\CharacterClass\Passive\PassiveDescription;
use Lorekeeper\Core\Item\Equipment\CharacterClass\Passive\PassiveName;
use Lorekeeper\Core\Item\Equipment\CharacterClass\Skill\Cost\ManaRestore;
use Lorekeeper\Core\Item\Equipment\CharacterClass\Skill\Skill;
use Lorekeeper\Core\Item\Equipment\CharacterClass\Skill\SkillName;
use Lorekeeper\Core\Item\Equipment\CharacterClass\Skill\SkillSlot;
use Lorekeeper\Core\Item\Equipment\CharacterClass\Skill\SkillTarget;
use Lorekeeper\Core\Player\Hero\Class\HeroClass;
use Lorekeeper\Core\Player\Hero\Class\HeroSkill;
use Lorekeeper\Core\Support\Quantity;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Psl\DateTime\Duration;
use Psl\Option;

final class HeroClassTest extends TestCase
{
    #[Test]
    #[TestWith([0, 1])]
    #[TestWith([3_599, 2])]
    #[TestWith([302_500, 10])]
    public function it_ranks_the_player_by_the_points_they_have_in_the_class(int $points, int $rank): void
    {
        $mage = new CharacterClass(new ClassName('Mage'), ClassCategory::Caster, Option\none(), [], []);

        static::assertSame($rank, new HeroClass($mage, new ClassPoints($points), [])->rank()->value);
    }

    #[Test]
    public function it_earns_class_points_and_ranks_up_with_them(): void
    {
        $class = new HeroClass(self::mage(), new ClassPoints(3_000));

        $class->earn(new ClassPoints(600));

        static::assertSame(3_600, $class->points->value);
        static::assertSame(3, $class->rank()->value);
    }

    #[Test]
    public function changing_class_forgets_the_skills_of_the_old_one(): void
    {
        $class = new HeroClass(self::mage(), new ClassPoints(302_500), [new HeroSkill(self::burst(), true)]);
        $healer = new CharacterClass(new ClassName('Healer'), ClassCategory::Caster, Option\none(), [], []);

        $class->change($healer, new ClassPoints(900));

        static::assertSame($healer, $class->class);
        static::assertSame(900, $class->points->value);
        static::assertSame([], $class->skills);
    }

    #[Test]
    public function it_learns_the_skills_and_passives_of_its_class(): void
    {
        $class = new HeroClass(self::mage(), new ClassPoints(302_500));
        $burst = new HeroSkill(self::burst(), true);
        $passive = new ClassPassive(new PassiveName('Arcane Mastery'), new PassiveDescription('Spells hit harder.'));

        $class->learn([$burst], [$passive]);

        static::assertSame([$burst], $class->skills);
        static::assertSame([$burst->skill], $class->class->skills);
        static::assertSame([$passive], $class->class->passives);
        static::assertSame('Mage', $class->class->name->value);
    }

    private static function mage(): CharacterClass
    {
        return new CharacterClass(new ClassName('Mage'), ClassCategory::Caster, Option\none(), [], []);
    }

    private static function burst(): Skill
    {
        return new Skill(
            SkillSlot::AutoAttack,
            new SkillName('Malevolent Burst'),
            Option\none(),
            SkillTarget::Enemy,
            new ManaRestore(new Mana(15)),
            Duration::milliseconds(1500),
            new Quantity(3),
            Option\none(),
        );
    }
}
