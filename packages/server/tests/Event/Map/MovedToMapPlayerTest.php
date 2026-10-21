<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Map;

use Lorekeeper\Core\World\Map\MapPad;
use Lorekeeper\Server\Event\Map\MovedToMapEvent;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use Lorekeeper\Server\Message\Message;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class MovedToMapPlayerTest extends TestCase
{
    #[Test]
    #[TestWith(['Left', MapPad::Left])]
    #[TestWith(['Right', MapPad::Right])]
    #[TestWith(['Top', MapPad::Top])]
    #[TestWith(['Bottom', MapPad::Bottom])]
    #[TestWith(['Up', MapPad::Up])]
    #[TestWith(['Down', MapPad::Down])]
    #[TestWith(['Center', MapPad::Center])]
    #[TestWith(['Spawn', MapPad::Spawn])]
    public function it_translates_the_pad_each_player_stands_on(string $pad, MapPad $expected): void
    {
        static::assertSame(
            $expected,
            MovedToMapEvent::from(self::message(self::arrive($pad, 2585)))->unwrap()->players[0]->player->spot->pad,
        );
    }

    #[Test]
    public function a_player_with_more_health_than_its_maximum_is_rejected(): void
    {
        $this->expectException(MalformedEventException::class);
        $this->expectExceptionMessage('health or mana is above its maximum');

        MovedToMapEvent::from(self::message(self::arrive('Spawn', 2586)));
    }

    private static function message(string $json): Message
    {
        return JsonMessage::from($json)->unwrap();
    }

    private static function arrive(string $pad, int $health): string
    {
        return json_encode(['b' => ['o' => [
            'cmd' => 'moveToArea',
            'areaId' => 687_803,
            'areaName' => 'forest-1',
            'strMapName' => 'forest',
            'strMapFileName' => 'GreenGuard/town-Forest-13Aug24.swf',
            'uoBranch' => [[
                'entID' => 116_500,
                'strUsername' => 'Hero',
                'strFrame' => 'Enter',
                'strPad' => $pad,
                'intState' => 1,
                'intLevel' => 81,
                'intHP' => $health,
                'intMP' => 100,
                'intHPMax' => 2585,
                'intMPMax' => 100,
                'afk' => false,
            ]],
        ]]], flags: JSON_THROW_ON_ERROR);
    }
}
