<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests\Unit\Scene;

use Lorekeeper\Apps\Desktop\Bots\Scene\Backpack;
use Lorekeeper\Apps\Desktop\Bots\Scene\Scene;
use Lorekeeper\Core\Item\Equipment\CharacterClass\ClassItem;
use Lorekeeper\Core\Item\Equipment\Weapon\DamageVariance;
use Lorekeeper\Core\Item\Equipment\Weapon\Weapon;
use Lorekeeper\Core\Item\Equipment\Weapon\WeaponDamage;
use Lorekeeper\Core\Item\Equipment\Weapon\WeaponType;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemLevel;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\ItemTag;
use Lorekeeper\Core\Item\Misc\Resource;
use Lorekeeper\Core\Player\Hero\Inventory\HeroInventory;
use Lorekeeper\Core\Support\Quantity;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Option;

final class BackpackTest extends TestCase
{
    #[Test]
    public function it_tells_each_held_item_by_its_kind_kept_or_for_now(): void
    {
        $sword = new Weapon(
            new ItemId(4767),
            new ItemName('Unidentified 20'),
            new ItemLevel(1),
            WeaponType::Sword,
            new WeaponDamage(new DamageVariance(10)),
            Option\none(),
        );
        $sword->tags->add(ItemTag::MemberOnly);
        $ore = new Resource(new ItemId(42_658), new ItemName('Metal Ore'), new ItemLevel(1), new Quantity(1));
        $ore->tags->add(ItemTag::Temporary);
        $inventory = new HeroInventory(new Quantity(131), new Quantity(30));
        $inventory
            ->add($sword)
            ->add(new ClassItem(new ItemId(15_651), new ItemName('Healer'), new ItemLevel(1), Option\none()))
            ->add(
                new Resource(new ItemId(42_657), new ItemName('Crypto Token'), new ItemLevel(1), new Quantity(99)),
                new Quantity(4),
            )
            ->add($ore);
        $backpack = Backpack::of($inventory);

        static::assertSame(
            [
                ['class',    'Class',    false],
                ['resource', 'Resource', false],
                ['weapon',   'Sword',    true],
            ],
            array_map(static fn(array $held): array => [
                $held['kind'],
                $held['type'],
                $held['member'],
            ], $backpack->kept),
        );
        static::assertSame(['Metal Ore'], array_column($backpack->temporary, 'name'));
        static::assertSame(['class' => 1, 'resource' => 1, 'weapon' => 1], $backpack->kinds());
        static::assertSame(4, $backpack->count());
    }

    #[Test]
    public function a_scene_saved_before_the_backpack_still_opens_with_it_empty(): void
    {
        $saved = new Scene(
            [
                'name' => 'Hero',
                'level' => 1,
                'health' => 1,
                'maxHealth' => 1,
                'mana' => 1,
                'maxMana' => 1,
                'class' => 'Mage',
                'category' => 'caster',
                'rank' => 1,
                'points' => 0,
                'rankFrom' => 0,
                'rankTo' => 900,
                'state' => 'idle',
                'away' => false,
                'map' => 'battleon',
                'items' => 0,
                'skills' => [],
            ],
            [],
            [],
            new \Lorekeeper\Apps\Desktop\Bots\Scene\Pulse(),
            new \Lorekeeper\Apps\Desktop\Bots\Scene\Feed(),
            1,
        )->toArray();
        unset($saved['backpack']);

        static::assertSame(0, Scene::fromArray($saved)?->backpack->count());
    }
}
