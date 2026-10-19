<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Command\Combat;

use InvalidArgumentException;
use Lorekeeper\Client\Command\Combat\SupportPlayerCommand;
use Lorekeeper\Core\CharacterClass\Skill\SkillSlot;
use Lorekeeper\Server\AreaId;
use Lorekeeper\Server\SocketId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SupportPlayerCommandTest extends TestCase
{
    #[Test]
    public function it_serializes_a_class_skill_aimed_at_a_player(): void
    {
        $support = static fn(SkillSlot $skill): string => new SupportPlayerCommand(
            new AreaId(732_692),
            $skill,
            new SocketId(122_628),
        )->toMessage();

        static::assertSame('%xt%zm%gar%732692%0%a1>p:122628%wvz%', $support(SkillSlot::First));
        static::assertSame('%xt%zm%gar%732692%0%a2>p:122628%wvz%', $support(SkillSlot::Second));
        static::assertSame('%xt%zm%gar%732692%0%a3>p:122628%wvz%', $support(SkillSlot::Third));
        static::assertSame('%xt%zm%gar%732692%0%a4>p:122628%wvz%', $support(SkillSlot::Fourth));
    }

    #[Test]
    public function the_auto_attack_cannot_support_a_player(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SupportPlayerCommand(new AreaId(1), SkillSlot::AutoAttack, new SocketId(1));
    }

    #[Test]
    public function a_potion_cannot_support_a_player(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SupportPlayerCommand(new AreaId(1), SkillSlot::Potion, new SocketId(1));
    }
}
