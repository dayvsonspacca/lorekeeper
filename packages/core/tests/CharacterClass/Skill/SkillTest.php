<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\CharacterClass\Skill;

use InvalidArgumentException;
use Lorekeeper\Core\Attribute\Mana;
use Lorekeeper\Core\CharacterClass\Skill\Cost\ManaRestore;
use Lorekeeper\Core\CharacterClass\Skill\Damage\DamageMultiplier;
use Lorekeeper\Core\CharacterClass\Skill\Damage\DamageSource;
use Lorekeeper\Core\CharacterClass\Skill\Damage\SkillDamage;
use Lorekeeper\Core\CharacterClass\Skill\Skill;
use Lorekeeper\Core\CharacterClass\Skill\SkillDescription;
use Lorekeeper\Core\CharacterClass\Skill\SkillName;
use Lorekeeper\Core\CharacterClass\Skill\SkillSlot;
use Lorekeeper\Core\CharacterClass\Skill\SkillTarget;
use Lorekeeper\Core\Support\Quantity;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\DateTime\Duration;
use Psl\Option;

final class SkillTest extends TestCase
{
    #[Test]
    public function it_carries_what_the_game_shows_and_how_hard_it_hits(): void
    {
        $burst = new Skill(
            SkillSlot::AutoAttack,
            new SkillName('Malevolent Burst'),
            Option\some(new SkillDescription('A sinister spell of powerful dark energy. Recovers 15 mana on hit.')),
            SkillTarget::Enemy,
            new ManaRestore(new Mana(15)),
            Duration::milliseconds(1500),
            new Quantity(3),
            Option\some(new SkillDamage(new DamageMultiplier(0.57), DamageSource::Magical)),
        );

        static::assertSame('Malevolent Burst', $burst->name->value);
        static::assertSame(0.57, $burst->damage->unwrap()->multiplier->value);
        static::assertSame(DamageSource::Magical, $burst->damage->unwrap()->source);
    }

    #[Test]
    public function a_skill_cannot_hit_for_nothing(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new DamageMultiplier(0.0);
    }
}
