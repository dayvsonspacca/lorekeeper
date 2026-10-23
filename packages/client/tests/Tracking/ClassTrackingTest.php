<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Tracking;

use Lorekeeper\Client\Tests\Fixture;
use Lorekeeper\Client\Tracking\ClassTracking;
use Lorekeeper\Client\Tracking\Identity;
use Lorekeeper\Core\Game\Attribute\Mana;
use Lorekeeper\Core\Game\Level\Experience;
use Lorekeeper\Core\Item\Equipment\CharacterClass\ClassCategory;
use Lorekeeper\Core\Item\Equipment\CharacterClass\ClassName;
use Lorekeeper\Core\Item\Equipment\CharacterClass\ClassPoints;
use Lorekeeper\Core\Item\Equipment\CharacterClass\Skill\Cost\ManaCost;
use Lorekeeper\Core\Item\Equipment\CharacterClass\Skill\SkillSlot;
use Lorekeeper\Core\Item\Equipment\CharacterClass\Skill\SkillTarget;
use Lorekeeper\Core\Player\Hero\Class\HeroClass;
use Lorekeeper\Core\Player\Hero\Class\HeroSkill;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Player\PlayerSpawnId;
use Lorekeeper\Core\World\Currency\Gold;
use Lorekeeper\Core\World\Monster\MonsterSpawnId;
use Lorekeeper\Server\Event\CharacterClass\ClassChangedEvent;
use Lorekeeper\Server\Event\Combat\SkillsLoadedEvent;
use Lorekeeper\Server\Event\Reward\MonsterRewardedEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\DateTime\Duration;
use Psl\Option;

final class ClassTrackingTest extends TestCase
{
    #[Test]
    public function it_changes_class_at_once_and_with_no_skills_yet(): void
    {
        $class = self::mage();

        static::assertFalse(ClassTracking::follow($class, self::changed(new PlayerSpawnId(1), 'Warrior'), self::me()));
        static::assertSame('Mage', $class->class->name->value);

        static::assertTrue(ClassTracking::follow(
            $class,
            self::changed(new PlayerSpawnId(122_628), 'Blaze Binder'),
            self::me(),
        ));
        static::assertSame('Blaze Binder', $class->class->name->value);
        static::assertSame(302_500, $class->points->value);
        static::assertSame([], $class->skills);
    }

    #[Test]
    public function it_learns_the_skills_of_the_class_it_plays(): void
    {
        $fireBolt = Fixture::skill(
            SkillSlot::AutoAttack,
            SkillTarget::Enemy,
            new ManaCost(new Mana(0)),
            Duration::seconds(2),
        );
        $burn = Fixture::skill(SkillSlot::First, SkillTarget::Enemy, new ManaCost(new Mana(10)), Duration::seconds(3));
        $skills = [new HeroSkill($fireBolt, true), new HeroSkill($burn, false)];
        $class = self::mage();
        ClassTracking::follow($class, self::changed(new PlayerSpawnId(122_628), 'Blaze Binder'), self::me());

        static::assertTrue(ClassTracking::follow($class, new SkillsLoadedEvent($skills, []), self::me()));
        static::assertSame('Blaze Binder', $class->class->name->value);
        static::assertSame([$fireBolt, $burn], $class->class->skills);
        static::assertSame($skills, $class->skills);
    }

    #[Test]
    public function it_earns_the_class_points_of_each_reward(): void
    {
        $class = self::mage();
        $kill = static fn(int $points): MonsterRewardedEvent => new MonsterRewardedEvent(
            new Gold(3),
            new Experience(10),
            new ClassPoints($points),
            new MonsterSpawnId(4),
        );

        ClassTracking::follow($class, $kill(510), self::me());
        ClassTracking::follow($class, $kill(3_090), self::me());

        static::assertSame(3_600, $class->points->value);
        static::assertSame(3, $class->rank()->value);
        static::assertFalse(ClassTracking::follow($class, $kill(0), self::me()));
        static::assertSame(3_600, $class->points->value);
    }

    private static function mage(): HeroClass
    {
        return Fixture::me()->hero()->class;
    }

    private static function me(): Identity
    {
        return new Identity(new PlayerSpawnId(122_628), new PlayerName('Hero'));
    }

    private static function changed(PlayerSpawnId $player, string $class): ClassChangedEvent
    {
        return new ClassChangedEvent(
            $player,
            new ClassName($class),
            ClassCategory::Caster,
            new ClassPoints(302_500),
            Option\none(),
        );
    }
}
