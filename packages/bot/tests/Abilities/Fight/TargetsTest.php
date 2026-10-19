<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Tests\Abilities\Fight;

use Lorekeeper\Bot\Abilities\Fight\Targets;
use Lorekeeper\Client\Command\Combat\AttackMonsterCommand;
use Lorekeeper\Client\Tests\Fixture;
use Lorekeeper\Core\CharacterClass\Skill\SkillSlot;
use Lorekeeper\Core\Currency\Gold;
use Lorekeeper\Core\Progression\ClassPoints;
use Lorekeeper\Core\Progression\Experience;
use Lorekeeper\Core\World\Map\Map;
use Lorekeeper\Core\World\Map\MapCell;
use Lorekeeper\Core\World\Monster\MonsterSpawnId;
use Lorekeeper\Server\AreaId;
use Lorekeeper\Server\Event\Combat\MonsterRespawnedEvent;
use Lorekeeper\Server\Event\Combat\MonstersDiedEvent;
use Lorekeeper\Server\Event\Reward\MonsterRewardedEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class TargetsTest extends TestCase
{
    #[Test]
    public function it_aims_at_the_monster_and_then_at_the_others_of_its_kind_in_the_cell(): void
    {
        $targets = Targets::in(self::arena(), new MapCell('r7'), self::attack(13));

        static::assertSame(13, $targets->next()?->value);

        $targets = $targets->heard(new MonstersDiedEvent([new MonsterSpawnId(13)]));
        static::assertSame(14, $targets->next()?->value);

        $targets = $targets->heard(
            new MonsterRewardedEvent(new Gold(3), new Experience(10), new ClassPoints(0), new MonsterSpawnId(14)),
        );
        static::assertNull($targets->next());

        $targets = $targets->heard(new MonsterRespawnedEvent(new MonsterSpawnId(13)));
        static::assertSame(13, $targets->next()?->value);
    }

    #[Test]
    public function coming_back_from_the_dead_counts_every_target_alive_again(): void
    {
        $targets = Targets::in(self::arena(), new MapCell('r7'), self::attack(13))->heard(new MonstersDiedEvent([
            new MonsterSpawnId(13),
            new MonsterSpawnId(14),
        ]));

        static::assertNull($targets->next());
        static::assertSame(13, $targets->revived()->next()?->value);
    }

    #[Test]
    public function it_refuses_a_monster_that_does_not_stand_in_the_cell(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Monster 13 does not stand in r8.');

        Targets::in(self::arena(), new MapCell('r8'), self::attack(13));
    }

    private static function arena(): Map
    {
        $grunt = Fixture::kind(20, 'Icewing Grunt');

        return Fixture::arrivalOf('icestormarena', 732_692, [
            Fixture::spawn(13, 'r7', $grunt),
            Fixture::spawn(50, 'r7', Fixture::kind(9, 'Frost Spirit')),
            Fixture::spawn(14, 'r7', $grunt),
            Fixture::spawn(16, 'r8', $grunt),
        ])->map;
    }

    private static function attack(int $spawn): AttackMonsterCommand
    {
        return new AttackMonsterCommand(new AreaId(732_692), SkillSlot::AutoAttack, new MonsterSpawnId($spawn));
    }
}
