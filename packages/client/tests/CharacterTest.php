<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests;

use Lorekeeper\Client\Character;
use Lorekeeper\Core\Attribute\Health;
use Lorekeeper\Core\CharacterClass\ClassCategory;
use Lorekeeper\Core\CharacterClass\ClassName;
use Lorekeeper\Core\Combat\CombatState;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\Misc\Resource;
use Lorekeeper\Core\Player\PlayerInventory;
use Lorekeeper\Core\Player\PlayerItem;
use Lorekeeper\Core\Player\PlayerItems;
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
use Lorekeeper\Server\Event\Combat\PlayersHealthChangedEvent;
use Lorekeeper\Server\Event\Inventory\PlayerInventoryLoadedEvent;
use Lorekeeper\Server\Event\Map\RoomPlayer;
use Lorekeeper\Server\Event\Player\PlayerVitalsUpdatedEvent;
use Lorekeeper\Server\Event\Progression\LeveledUpEvent;
use Lorekeeper\Server\SocketId;
use Override;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Observer\ObserverInterface;
use Psl\Observer\SubjectInterface;
use Psl\Option;

final class CharacterTest extends TestCase
{
    #[Test]
    public function it_keeps_up_with_what_the_server_says_about_the_player(): void
    {
        $me = Fixture::me(level: 4);
        $scales = new PlayerItem(new Resource(new ItemId(11_475), new ItemName('Dragon Scale')));

        $me->hear(new LeveledUpEvent(new PlayerLevel(5), new Experience(500)));
        $me->hear(new PlayersHealthChangedEvent([new PlayerHealth(new PlayerName('lorekeeper03'), new Health(189))]));
        $me->hear(
            new ClassChangedEvent(
                new SocketId(122_628),
                new ClassName('Blaze Binder'),
                ClassCategory::Caster,
                new ClassPoints(302_500),
                Option\none(),
            ),
        );
        $me->hear(new PlayerInventoryLoadedEvent(new PlayerInventory(new PlayerItems([$scales]))));

        $player = $me->player();
        static::assertSame('lorekeeper03', $player->name->value);
        static::assertSame(5, $player->level->value);
        static::assertSame(189, $player->vitals->health->current->value);
        static::assertSame('Blaze Binder', $player->class->class->name->value);
        static::assertSame($scales, $player->inventory->find(new ItemId(11_475))->unwrap());
    }

    #[Test]
    public function it_keeps_up_with_where_it_stands_but_takes_no_level_from_a_map(): void
    {
        $me = Fixture::me(level: 4);
        $spot = new MapSpot(new MapCell('r3'), MapPad::Left);

        $me->hear(Fixture::arrivalOf(
            'oaklore',
            946,
            [],
            [
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

        static::assertSame('oaklore', $me->location()->map->name->value);
        static::assertSame($spot, $me->location()->spot);
        static::assertSame(CombatState::Fighting, $me->state());
        static::assertTrue($me->away());
        static::assertSame(4, $me->player()->level->value);

        $me->hear(new PlayerDiedEvent());
        static::assertSame(CombatState::Dead, $me->state());
        static::assertFalse($me->player()->vitals->alive());
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
        $me->hear(new LeveledUpEvent(new PlayerLevel(4), new Experience(400)));
        $me->hear(new LeveledUpEvent(new PlayerLevel(5), new Experience(500)));
        $me->unsubscribe($watcher);
        $me->hear(new LeveledUpEvent(new PlayerLevel(6), new Experience(600)));

        static::assertSame([5], $watcher->levels);
    }
}
