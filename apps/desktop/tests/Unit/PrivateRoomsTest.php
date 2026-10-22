<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests\Unit;

use Generator;
use Lorekeeper\Apps\Desktop\Bots\PrivateRooms;
use Lorekeeper\Apps\Desktop\Bots\SharedRooms;
use Lorekeeper\Apps\Desktop\Models\Preferences;
use Lorekeeper\Apps\Desktop\Tests\Characters;
use Lorekeeper\Apps\Desktop\Tests\TestCase;
use Lorekeeper\Client\Character;
use Lorekeeper\Client\Command\Map\JoinMapCommand;
use Lorekeeper\Client\Command\Map\MoveToCellCommand;
use Lorekeeper\Client\Script\Done;
use Lorekeeper\Client\Script\Script;
use Lorekeeper\Client\Script\Send;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\World\Map\MapCell;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Map\MapPad;
use Lorekeeper\Core\World\Map\MapSpot;
use Lorekeeper\Server\AreaId;
use Lorekeeper\Server\Event\Map\MapJoins;
use Lorekeeper\Server\RoomId;
use PHPUnit\Framework\Attributes\Test;
use Psl\DateTime\Duration;
use Psl\Option;

final class PrivateRoomsTest extends TestCase
{
    #[Test]
    public function every_map_the_script_joins_is_joined_in_a_room_of_its_own(): void
    {
        $rooms = [24_357, 84_881];
        $asked = [];
        $script = new PrivateRooms(self::script(), static function (MapName $map) use (&$rooms, &$asked): RoomId {
            $asked[] = $map->value;

            return new RoomId((int) array_shift($rooms));
        });
        $steps = $script->run(Characters::me('Hero', 23, 'battleon'));

        $sent = [];
        while ($steps->valid()) {
            $instruction = $steps->current();
            static::assertInstanceOf(Send::class, $instruction);
            $sent[] = [$instruction->command->toMessage(), $instruction->await?->events];
            $steps->send(null);
        }

        static::assertSame('Two maps', $script->title());
        static::assertSame(['boxes', 'arcangrove'], $asked);
        static::assertEquals(new Done('joined both'), $steps->getReturn());
        static::assertSame(
            [
                ['%xt%zm%cmd%1%tfer%Hero%boxes-24357%',      MapJoins::events()],
                ['%xt%zm%moveToCell%1%Closet%Center%',       null],
                ['%xt%zm%cmd%1%tfer%Hero%arcangrove-84881%', MapJoins::events()],
            ],
            $sent,
        );
    }

    #[Test]
    public function the_preferences_choose_between_no_rooms_random_ones_and_one_shared_by_the_bots(): void
    {
        $preferences = new Preferences();
        $script = self::script();

        $preferences->private_rooms = false;
        static::assertSame($script, PrivateRooms::asPreferred($script, $preferences));

        $preferences->private_rooms = true;
        $preferences->play_together = true;
        static::assertSame(
            [SharedRooms::of(new MapName('boxes'))->value, SharedRooms::of(new MapName('arcangrove'))->value],
            self::rooms(PrivateRooms::asPreferred($script, $preferences)),
        );

        $preferences->play_together = false;
        foreach (self::rooms(PrivateRooms::asPreferred($script, $preferences)) as $room) {
            static::assertGreaterThanOrEqual(PrivateRooms::LOWEST, $room);
            static::assertLessThanOrEqual(PrivateRooms::HIGHEST, $room);
        }
    }

    /**
     * @param Script<Done> $script
     *
     * @return list<int>
     */
    private static function rooms(Script $script): array
    {
        $steps = $script->run(Characters::me('Hero', 23, 'battleon'));
        $rooms = [];
        while ($steps->valid()) {
            $instruction = $steps->current();
            if (
                $instruction instanceof Send
                && preg_match('/-(\d+)%$/', $instruction->command->toMessage(), $room) === 1
            ) {
                $rooms[] = (int) $room[1];
            }
            $steps->send(null);
        }

        return $rooms;
    }

    /** @return Script<Done> */
    private static function script(): Script
    {
        return new readonly class implements Script {
            public function title(): string
            {
                return 'Two maps';
            }

            public function run(Character $me): Generator
            {
                $name = new PlayerName('Hero');
                yield new Send(new JoinMapCommand($name, new MapName('boxes'), Option\none()))->awaits(
                    Duration::seconds(15),
                    MapJoins::class,
                );
                yield new Send(
                    new MoveToCellCommand(new AreaId(1), new MapSpot(new MapCell('Closet'), MapPad::Center)),
                );
                yield new Send(new JoinMapCommand($name, new MapName('arcangrove'), Option\none()))->awaits(
                    Duration::seconds(15),
                    MapJoins::class,
                );

                return new Done('joined both');
            }
        };
    }
}
