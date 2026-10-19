<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Tracking;

use Lorekeeper\Client\Tests\Fixture;
use Lorekeeper\Client\Tracking\ClassTracking;
use Lorekeeper\Client\Tracking\Identity;
use Lorekeeper\Core\Attribute\Mana;
use Lorekeeper\Core\CharacterClass\ClassCategory;
use Lorekeeper\Core\CharacterClass\ClassName;
use Lorekeeper\Core\CharacterClass\Skill\Cost\ManaCost;
use Lorekeeper\Core\CharacterClass\Skill\Skill;
use Lorekeeper\Core\CharacterClass\Skill\SkillSlot;
use Lorekeeper\Core\CharacterClass\Skill\SkillTarget;
use Lorekeeper\Core\Currency\Gold;
use Lorekeeper\Core\Player\PlayerClass;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Player\PlayerSkill;
use Lorekeeper\Core\Progression\ClassPoints;
use Lorekeeper\Core\Progression\Experience;
use Lorekeeper\Core\World\Monster\MonsterSpawnId;
use Lorekeeper\Server\Event\CharacterClass\ClassChangedEvent;
use Lorekeeper\Server\Event\Combat\SkillsLoadedEvent;
use Lorekeeper\Server\Event\Reward\MonsterRewardedEvent;
use Lorekeeper\Server\SocketId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\DateTime\Duration;
use Psl\Option;

final class ClassTrackingTest extends TestCase
{
    #[Test]
    public function it_changes_class_at_once_and_with_no_skills_yet(): void
    {
        $mage = self::mage();

        $theirs = ClassTracking::follow($mage, self::changed(new SocketId(1), 'Warrior'), self::me());
        $mine = ClassTracking::follow($mage, self::changed(new SocketId(122_628), 'Blaze Binder'), self::me());

        static::assertSame($mage, $theirs);
        static::assertSame('Blaze Binder', $mine->class->name->value);
        static::assertSame(302_500, $mine->points->value);
        static::assertSame([], $mine->skills);
    }

    #[Test]
    public function it_learns_the_skills_of_the_class_it_plays(): void
    {
        $fireBolt = new Skill(
            SkillSlot::AutoAttack,
            SkillTarget::Enemy,
            new ManaCost(new Mana(0)),
            Duration::seconds(2),
        );
        $burn = new Skill(SkillSlot::First, SkillTarget::Enemy, new ManaCost(new Mana(10)), Duration::seconds(3));
        $skills = [new PlayerSkill($fireBolt, true), new PlayerSkill($burn, false)];
        $changed = ClassTracking::follow(
            self::mage(),
            self::changed(new SocketId(122_628), 'Blaze Binder'),
            self::me(),
        );

        $learned = ClassTracking::follow($changed, new SkillsLoadedEvent($skills), self::me());

        static::assertSame('Blaze Binder', $learned->class->name->value);
        static::assertSame([$fireBolt, $burn], $learned->class->skills);
        static::assertSame($skills, $learned->skills);
    }

    #[Test]
    public function it_earns_the_class_points_of_each_reward(): void
    {
        $mage = self::mage();
        $kill = static fn(int $points): MonsterRewardedEvent => new MonsterRewardedEvent(
            new Gold(3),
            new Experience(10),
            new ClassPoints($points),
            new MonsterSpawnId(4),
        );

        $earned = ClassTracking::follow(ClassTracking::follow($mage, $kill(510), self::me()), $kill(3_090), self::me());

        static::assertSame(3_600, $earned->points->value);
        static::assertSame(3, $earned->rank()->value);
        static::assertSame($mage, ClassTracking::follow($mage, $kill(0), self::me()));
    }

    private static function mage(): PlayerClass
    {
        return Fixture::me()->player()->class;
    }

    private static function me(): Identity
    {
        return new Identity(new SocketId(122_628), new PlayerName('lorekeeper03'));
    }

    private static function changed(SocketId $player, string $class): ClassChangedEvent
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
