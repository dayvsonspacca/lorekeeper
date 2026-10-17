<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Command;

use InvalidArgumentException;
use Lorekeeper\Client\Command\AttackMonsterCommand;
use Lorekeeper\Core\CharacterClass\Skill\SkillSlot;
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
    public function a_potion_is_not_an_attack(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new AttackMonsterCommand(new AreaId(1), SkillSlot::Potion, new MonsterSpawnId(3));
    }
}
