<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Player;

use Lorekeeper\Server\Event\Player\PlayerData;
use Lorekeeper\Server\Event\Player\PlayerDataLoadedEvent;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\DelimitedMessage;
use Lorekeeper\Server\Message\JsonMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class PlayerDataLoadedEventTest extends TestCase
{
    private const string OTHER = <<<'JSON'
        {"uid":144193,"data":{"intColorAccessory":"0","iCP":2154,"intLevel":78,"iUpgDays":"-1","intColorTrim":"0","intAccessLevel":"1","intColorSkin":"15388042","strClassName":"Warrior","intColorBase":"0","strGender":"M","intColorEye":"0","strHairName":"Default","strUsername":"Hero2","strHairFilename":"hair/M/Default.swf","intColorHair":"0","eqp":{}}}
        JSON;

    private const string OURS =
        '{"uid":45466,"data":{"strUsername":"Hero","intLevel":81,"intExp":0,"intExpToLevel":1000,'
            . '"intGold":0,"intCoins":0,"iUpg":0,"iUpgDays":0,"iBagSlots":30,"iBankSlots":30}}';

    #[Test]
    public function it_reads_the_spawn_name_and_level_of_each_player(): void
    {
        $event = PlayerDataLoadedEvent::from(
            JsonMessage::from(self::initUserDatas(
                self::OTHER,
                '{"uid":3,"data":{"strUsername":"Hero3","intLevel":1}}',
            ))->unwrap(),
        )->unwrap();

        static::assertSame(
            [
                [144_193, 'Hero2', 78],
                [3,       'Hero3', 1],
            ],
            array_map(static fn(PlayerData $player): array => [
                $player->spawnId->value,
                $player->name->value,
                $player->level->value,
            ], $event->players),
        );
        static::assertSame('Data of Hero2, Hero3', $event->summary());
    }

    #[Test]
    public function it_leaves_our_player_out(): void
    {
        $event = PlayerDataLoadedEvent::from(
            JsonMessage::from(self::initUserDatas(self::OURS, self::OTHER))->unwrap(),
        )->unwrap();

        static::assertCount(1, $event->players);
        static::assertSame('Hero2', $event->players[0]->name->value);
    }

    #[Test]
    public function it_is_not_the_data_of_other_players_when_only_ours_came(): void
    {
        static::assertTrue(
            PlayerDataLoadedEvent::from(JsonMessage::from(self::initUserDatas(self::OURS))->unwrap())->isNone(),
        );
    }

    #[Test]
    public function it_ignores_other_messages(): void
    {
        static::assertTrue(
            PlayerDataLoadedEvent::from(
                JsonMessage::from('{"t":"xt","b":{"r":-1,"o":{"cmd":"uotls"}}}')->unwrap(),
            )->isNone(),
        );
        static::assertTrue(
            PlayerDataLoadedEvent::from(DelimitedMessage::from('%xt%exitArea%-1%1%hero%')->unwrap())->isNone(),
        );
    }

    #[Test]
    #[TestWith(['{"t":"xt","b":{"r":-1,"o":{"cmd":"initUserDatas","a":[{"uid":144193}]}}}'])]
    #[TestWith([
        '{"t":"xt","b":{"r":-1,"o":{"cmd":"initUserDatas","a":[{"data":{"strUsername":"Hero2","intLevel":78}}]}}}',
    ])]
    #[TestWith([
        '{"t":"xt","b":{"r":-1,"o":{"cmd":"initUserDatas","a":[{"uid":144193,"data":{"strUsername":"Hero2","intLevel":101}}]}}}',
    ])]
    public function it_rejects_player_data_it_cannot_read(string $json): void
    {
        $this->expectException(MalformedEventException::class);
        $this->expectExceptionMessage('player data is missing or invalid');

        PlayerDataLoadedEvent::from(JsonMessage::from($json)->unwrap());
    }

    private static function initUserDatas(string ...$entries): string
    {
        return '{"t":"xt","b":{"r":-1,"o":{"cmd":"initUserDatas","a":[' . implode(',', $entries) . ']}}}';
    }
}
