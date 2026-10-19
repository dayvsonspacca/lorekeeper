<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests;

use Lorekeeper\Client\Character;
use Lorekeeper\Core\Attribute\Health;
use Lorekeeper\Core\Attribute\Mana;
use Lorekeeper\Core\CharacterClass\ClassCategory;
use Lorekeeper\Core\CharacterClass\ClassName;
use Lorekeeper\Core\CharacterClass\Skill\Skill;
use Lorekeeper\Core\CharacterClass\Skill\SkillSlot;
use Lorekeeper\Core\CharacterClass\Skill\SkillTarget;
use Lorekeeper\Core\Combat\CombatState;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\Misc\Resource;
use Lorekeeper\Core\Player\PlayerInventory;
use Lorekeeper\Core\Player\PlayerItem;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Progression\ClassPoints;
use Lorekeeper\Core\Progression\Experience;
use Lorekeeper\Core\World\Map\MapCell;
use Lorekeeper\Core\World\Map\MapPad;
use Lorekeeper\Core\World\Map\MapPlayer;
use Lorekeeper\Core\World\Map\MapSpot;
use Lorekeeper\Server\Event\CharacterClass\ClassChangedEvent;
use Lorekeeper\Server\Event\Combat\PlayerDiedEvent;
use Lorekeeper\Server\Event\Combat\PlayerHealth;
use Lorekeeper\Server\Event\Combat\PlayerMana;
use Lorekeeper\Server\Event\Combat\PlayersHealthChangedEvent;
use Lorekeeper\Server\Event\Combat\PlayersManaChangedEvent;
use Lorekeeper\Server\Event\Combat\ResurrectedEvent;
use Lorekeeper\Server\Event\Combat\SkillsLoadedEvent;
use Lorekeeper\Server\Event\Inventory\PlayerInventoryLoadedEvent;
use Lorekeeper\Server\Event\Map\RoomPlayer;
use Lorekeeper\Server\Event\Player\PlayerVitalsUpdatedEvent;
use Lorekeeper\Server\Event\Progression\LeveledUpEvent;
use Lorekeeper\Server\SocketId;
use Override;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\DateTime\Duration;
use Psl\Observer\ObserverInterface;
use Psl\Observer\SubjectInterface;
use Psl\Option;

final class CharacterTest extends TestCase
{
    #[Test]
    public function it_takes_its_health_and_mana_from_what_the_server_says_about_it(): void
    {
        $me = Fixture::me();

        $me->hear(new PlayerVitalsUpdatedEvent(new PlayerName('Someone'), Fixture::vitals(10, 10, 10, 10)));
        $me->hear(new PlayerVitalsUpdatedEvent(new PlayerName('LoreKeeper03'), Fixture::vitals(2585, 2585, 90, 100)));

        static::assertSame([2585, 2585, 90, 100], self::vitals($me));
    }

    #[Test]
    public function a_fight_changes_its_health_and_mana_but_not_how_high_they_go(): void
    {
        $me = Fixture::me();

        $me->hear(new PlayersHealthChangedEvent([
            new PlayerHealth(new PlayerName('lorekeeper03'), new Health(189)),
            new PlayerHealth(new PlayerName('Someone'), new Health(1)),
        ]));
        $me->hear(new PlayersManaChangedEvent([new PlayerMana(new PlayerName('lorekeeper03'), new Mana(87))]));

        static::assertSame([189, 390, 87, 100], self::vitals($me));
    }

    #[Test]
    public function it_dies_with_no_health_left_and_comes_back_with_all_of_it(): void
    {
        $me = Fixture::me();

        $me->hear(new PlayerDiedEvent());
        static::assertSame(CombatState::Dead, $me->state());
        static::assertFalse($me->player()->vitals->alive());

        $me->hear(new ResurrectedEvent());
        static::assertSame(CombatState::Idle, $me->state());
        static::assertSame([390, 390, 100, 100], self::vitals($me));
    }

    #[Test]
    public function it_levels_up(): void
    {
        $me = Fixture::me(level: 4);

        $me->hear(new LeveledUpEvent(new PlayerLevel(5), new Experience(500)));

        static::assertSame(5, $me->player()->level->value);
    }

    #[Test]
    public function it_takes_where_it_stands_from_each_map_it_arrives_in_but_not_its_level(): void
    {
        $me = Fixture::me(level: 4);
        $spot = new MapSpot(new MapCell('r3'), MapPad::Left);

        $me->hear(Fixture::arrivalOf(
            'oaklore',
            946,
            [],
            [
                new RoomPlayer(
                    new SocketId(1),
                    new MapPlayer(
                        new PlayerName('Someone'),
                        new PlayerLevel(80),
                        Fixture::vitals(),
                        new MapSpot(new MapCell('Enter'), MapPad::Spawn),
                        CombatState::Idle,
                        false,
                    ),
                ),
                new RoomPlayer(
                    new SocketId(122_628),
                    new MapPlayer(
                        new PlayerName('lorekeeper03'),
                        new PlayerLevel(50),
                        Fixture::vitals(),
                        $spot,
                        CombatState::Fighting,
                        true,
                    ),
                ),
            ],
        ));

        static::assertSame(946, $me->area()->value);
        static::assertSame('oaklore', $me->map()->name->value);
        static::assertSame($spot, $me->spot());
        static::assertSame(CombatState::Fighting, $me->state());
        static::assertTrue($me->away());
        static::assertSame(4, $me->player()->level->value);
    }

    #[Test]
    public function it_changes_class_only_once_the_skills_of_the_new_one_arrive(): void
    {
        $me = Fixture::me();
        $fireBolt = new Skill(SkillSlot::AutoAttack, SkillTarget::Enemy, new Mana(0), Duration::seconds(2));
        $burn = new Skill(SkillSlot::First, SkillTarget::Enemy, new Mana(10), Duration::seconds(3));

        $me->hear(self::changed(new SocketId(1), 'Warrior'));
        $me->hear(self::changed(new SocketId(122_628), 'Blaze Binder'));
        static::assertSame('Mage', $me->player()->class->class->name->value);

        $me->hear(new SkillsLoadedEvent([$fireBolt, $burn], [SkillSlot::AutoAttack]));
        static::assertSame('Blaze Binder', $me->player()->class->class->name->value);
        static::assertSame(302_500, $me->player()->class->points->value);
        static::assertSame([$fireBolt, $burn], $me->player()->class->class->skills);
        static::assertSame([$fireBolt], $me->usableSkills());
    }

    #[Test]
    public function it_takes_the_inventory_it_is_sent(): void
    {
        $me = Fixture::me();
        $scales = new PlayerItem(new Resource(new ItemId(11_475), new ItemName('Dragon Scale')));

        $me->hear(new PlayerInventoryLoadedEvent(new PlayerInventory([$scales])));

        static::assertSame($scales, $me->player()->inventory->find(new ItemId(11_475))->unwrap());
    }

    #[Test]
    public function it_tells_its_observers_when_it_changes_and_only_then(): void
    {
        $me = Fixture::me(level: 4);
        $watcher = new class() implements ObserverInterface {
            /** @var list<null|int> */
            public array $levels = [];

            #[Override]
            public function update(SubjectInterface $subject): void
            {
                $this->levels[] = $subject instanceof Character ? $subject->player()->level->value : null;
            }
        };

        $me->subscribe($watcher);
        $me->subscribe($watcher);
        $me->hear(new PlayerVitalsUpdatedEvent(new PlayerName('lorekeeper03'), Fixture::vitals()));
        $me->hear(new LeveledUpEvent(new PlayerLevel(5), new Experience(500)));
        $me->unsubscribe($watcher);
        $me->hear(new LeveledUpEvent(new PlayerLevel(6), new Experience(600)));

        static::assertSame([5], $watcher->levels);
    }

    /** @return list<int> */
    private static function vitals(Character $me): array
    {
        $vitals = $me->player()->vitals;

        return [
            $vitals->health->current->value,
            $vitals->health->maximum->value,
            $vitals->mana->current->value,
            $vitals->mana->maximum->value,
        ];
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
