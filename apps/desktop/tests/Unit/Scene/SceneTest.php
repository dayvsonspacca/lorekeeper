<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests\Unit\Scene;

use Lorekeeper\Apps\Desktop\Bots\Scene\Feed;
use Lorekeeper\Apps\Desktop\Bots\Scene\Pulse;
use Lorekeeper\Apps\Desktop\Bots\Scene\Room;
use Lorekeeper\Apps\Desktop\Bots\Scene\Scene;
use Lorekeeper\Apps\Desktop\Tests\Characters;
use Lorekeeper\Core\Attribute\Mana;
use Lorekeeper\Core\CharacterClass\CharacterClass;
use Lorekeeper\Core\CharacterClass\ClassCategory;
use Lorekeeper\Core\CharacterClass\ClassName;
use Lorekeeper\Core\CharacterClass\Passive\ClassPassive;
use Lorekeeper\Core\CharacterClass\Passive\PassiveDescription;
use Lorekeeper\Core\CharacterClass\Passive\PassiveName;
use Lorekeeper\Core\CharacterClass\Skill\Cost\ManaCost;
use Lorekeeper\Core\CharacterClass\Skill\Damage\DamageMultiplier;
use Lorekeeper\Core\CharacterClass\Skill\Damage\DamageSource;
use Lorekeeper\Core\CharacterClass\Skill\Damage\SkillDamage;
use Lorekeeper\Core\CharacterClass\Skill\Skill;
use Lorekeeper\Core\CharacterClass\Skill\SkillDescription;
use Lorekeeper\Core\CharacterClass\Skill\SkillName;
use Lorekeeper\Core\CharacterClass\Skill\SkillSlot;
use Lorekeeper\Core\CharacterClass\Skill\SkillTarget;
use Lorekeeper\Core\Item\Equipment\Helm;
use Lorekeeper\Core\Item\Equipment\Weapon\DamageVariance;
use Lorekeeper\Core\Item\Equipment\Weapon\Weapon;
use Lorekeeper\Core\Item\Equipment\Weapon\WeaponDamage;
use Lorekeeper\Core\Item\Equipment\Weapon\WeaponType;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemLevel;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\Misc\Enhancement\EffectDescription;
use Lorekeeper\Core\Item\Misc\Enhancement\EffectName;
use Lorekeeper\Core\Item\Misc\Enhancement\Enhancement;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementEffect;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementRarity;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementType;
use Lorekeeper\Core\Player\PlayerClass;
use Lorekeeper\Core\Player\PlayerInventory;
use Lorekeeper\Core\Player\PlayerItem;
use Lorekeeper\Core\Player\PlayerItems;
use Lorekeeper\Core\Player\PlayerSkill;
use Lorekeeper\Core\Progression\ClassPoints;
use Lorekeeper\Core\Support\Quantity;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\DateTime\Duration;
use Psl\Option;

final class SceneTest extends TestCase
{
    #[Test]
    public function it_keeps_what_each_skill_does_and_the_passives_of_the_class(): void
    {
        $hero = self::captured()->hero;

        static::assertSame(
            [
                'name' => 'Wildfire',
                'description' => 'Burns up to three foes.',
                'damage' => 1.4,
                'source' => 'magical',
            ],
            array_intersect_key($hero['skills'][0], array_flip(['name', 'description', 'damage', 'source'])),
        );
        static::assertSame([['name' => 'Arcane Flux', 'description' => 'Spells cost less mana.']], $hero['passives']);
    }

    #[Test]
    public function it_keeps_what_is_worn_with_its_enhancement_in_the_order_it_is_worn(): void
    {
        $worn = self::captured()->backpack->equipped();

        static::assertSame(['Blade of Awe', 'Hood'], array_column($worn, 'name'));
        static::assertSame(
            [
                'type' => 'Spell Breaker',
                'rarity' => '+100',
                'level' => 50,
                'effect' => ['name' => 'Spiral Carve', 'description' => 'Hits harder the longer the fight.'],
            ],
            $worn[0]['enhancement'],
        );
        static::assertSame(15, $worn[0]['variance']);
        static::assertSame(60, $worn[0]['level']);
        static::assertNull($worn[1]['enhancement']);
    }

    #[Test]
    public function a_saved_scene_opens_with_everything_it_kept(): void
    {
        $scene = self::captured();

        static::assertEquals($scene, Scene::fromArray($scene->toArray()));
    }

    private static function captured(): Scene
    {
        $class = new PlayerClass(
            new CharacterClass(
                new ClassName('Mage'),
                ClassCategory::Caster,
                Option\none(),
                [],
                [new ClassPassive(new PassiveName('Arcane Flux'), new PassiveDescription('Spells cost less mana.'))],
            ),
            new ClassPoints(0),
            [new PlayerSkill(
                new Skill(
                    SkillSlot::First,
                    new SkillName('Wildfire'),
                    Option\some(new SkillDescription('Burns up to three foes.')),
                    SkillTarget::Enemy,
                    new ManaCost(new Mana(20)),
                    Duration::seconds(6),
                    new Quantity(3),
                    Option\some(new SkillDamage(new DamageMultiplier(1.4), DamageSource::Magical)),
                ),
                true,
            )],
        );
        $blade = new Weapon(
            new ItemId(1),
            new ItemName('Blade of Awe'),
            new ItemLevel(60),
            WeaponType::Sword,
            new WeaponDamage(new DamageVariance(15)),
            Option\some(
                new Enhancement(
                    EnhancementType::SpellBreaker,
                    EnhancementRarity::PlusOneHundred,
                    new ItemLevel(50),
                    Option\some(
                        new EnhancementEffect(
                            new EffectName('Spiral Carve'),
                            new EffectDescription('Hits harder the longer the fight.'),
                        ),
                    ),
                ),
            ),
        );
        $hood = new Helm(new ItemId(2), new ItemName('Hood'), new ItemLevel(1), Option\none());
        $inventory = new PlayerInventory(new PlayerItems([
            new PlayerItem($hood, equipped: true),
            new PlayerItem($blade, equipped: true),
            new PlayerItem(new Helm(new ItemId(3), new ItemName('Spare Hood'), new ItemLevel(1), Option\none())),
        ]), new Quantity(30));

        return Scene::capture(
            Characters::me('Hero', 10, 'battleon', class: $class, inventory: $inventory),
            new Room(),
            new Pulse(),
            new Feed(),
            1,
        );
    }
}
