<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests\Unit;

use Generator;
use Lorekeeper\Apps\Desktop\Bots\PrivateRooms;
use Lorekeeper\Apps\Desktop\Tests\Characters;
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
use PHPUnit\Framework\TestCase;
use Psl\DateTime\Duration;
use Psl\Option;

final class PrivateRoomsTest extends TestCase
{
    #[Test]
    public function every_map_the_script_joins_is_joined_in_a_room_of_its_own(): void
    {
        $rooms = [24_357, 84_881];
        $script = new PrivateRooms(self::script(), static function () use (&$rooms): RoomId {
            return new RoomId((int) array_shift($rooms));
        });
        $steps = $script->run(Characters::me('lorekeeper03', 23, 'battleon'));

        $sent = [];
        while ($steps->valid()) {
            $instruction = $steps->current();
            static::assertInstanceOf(Send::class, $instruction);
            $sent[] = [$instruction->command->toMessage(), $instruction->await?->events];
            $steps->send(null);
        }

        static::assertSame('Two maps', $script->title());
        static::assertEquals(new Done('joined both'), $steps->getReturn());
        static::assertSame(
            [
                ['%xt%zm%cmd%1%tfer%lorekeeper03%boxes-24357%',      MapJoins::events()],
                ['%xt%zm%moveToCell%1%Closet%Center%',               null],
                ['%xt%zm%cmd%1%tfer%lorekeeper03%arcangrove-84881%', MapJoins::events()],
            ],
            $sent,
        );
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
                $name = new PlayerName('lorekeeper03');
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
