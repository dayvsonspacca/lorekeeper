<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Command\Combat;

use InvalidArgumentException;
use Lorekeeper\Client\Command\Combat\AttackMonsterCommand;
use Lorekeeper\Core\CharacterClass\Skill\SkillSlot;
use Lorekeeper\Core\Combat\Targetable;
use Lorekeeper\Core\World\Monster\MonsterSpawnId;
use Lorekeeper\Server\AreaId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AttackMonsterCommandTest extends TestCase
{
    #[Test]
    public function it_serializes_an_attack_with_each_skill(): void
    {
        $attack = static fn(SkillSlot $skill): string => new AttackMonsterCommand(
            new AreaId(732_692),
            $skill,
            new MonsterSpawnId(3),
        )->toMessage();

        static::assertSame('%xt%zm%gar%732692%0%aa>m:3%wvz%', $attack(SkillSlot::AutoAttack));
        static::assertSame('%xt%zm%gar%732692%0%a1>m:3%wvz%', $attack(SkillSlot::First));
        static::assertSame('%xt%zm%gar%732692%0%a2>m:3%wvz%', $attack(SkillSlot::Second));
        static::assertSame('%xt%zm%gar%732692%0%a3>m:3%wvz%', $attack(SkillSlot::Third));
        static::assertSame('%xt%zm%gar%732692%0%a4>m:3%wvz%', $attack(SkillSlot::Fourth));
    }

    #[Test]
    public function the_same_attack_changes_skill_and_target(): void
    {
        $attack = new AttackMonsterCommand(new AreaId(732_692), SkillSlot::AutoAttack, new MonsterSpawnId(1));

        static::assertSame('%xt%zm%gar%732692%0%a1>m:1%wvz%', $attack->with(SkillSlot::First)->toMessage());
        static::assertSame('%xt%zm%gar%732692%0%aa>m:2%wvz%', $attack->at(new MonsterSpawnId(2))->toMessage());
        static::assertSame(1, $attack->target()->value);
    }

    #[Test]
    public function a_monster_attack_only_aims_at_monsters(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new AttackMonsterCommand(new AreaId(1), SkillSlot::AutoAttack, new MonsterSpawnId(1))->at(new class() implements
            Targetable {});
    }

    #[Test]
    public function a_potion_is_not_an_attack(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new AttackMonsterCommand(new AreaId(1), SkillSlot::Potion, new MonsterSpawnId(3));
    }
}
