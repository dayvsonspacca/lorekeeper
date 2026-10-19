<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Player;

use Lorekeeper\Core\World\Quest\QuestMark;
use Lorekeeper\Core\World\Quest\QuestPeriod;
use Lorekeeper\Server\Event\Player\PlayerDataLoadedEvent;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PlayerDataLoadedEventTest extends TestCase
{
    private const string MADE2903 = <<<'JSON'
        {"t":"xt","b":{"r":-1,"o":{"cmd":"initUserDatas","a":[{"uid":45466,"data":{"strUsername":"made2903","intLevel":81,"intExp":4514754,"intExpToLevel":5280000,"intGold":6800132,"intCoins":2899,"iUpg":0,"iUpgDays":-5772,"iBagSlots":131,"iBankSlots":131,"id0":0,"id1":0,"id2":64,"id3":0,"id4":0,"id5":0,"id6":0,"id7":0,"iw0":0,"im0":0,"ia0":139536,"ip0":0}}]}}}
        JSON;

    #[Test]
    public function it_reads_the_progress_wallet_membership_bag_and_marks_of_a_real_player(): void
    {
        $event = PlayerDataLoadedEvent::from(JsonMessage::from(self::MADE2903)->unwrap())->unwrap();
        $player = $event->players[0];

        static::assertSame([45_466, 'made2903'], [$player->socketId->value, $player->name->value]);
        static::assertSame([81, 4_514_754, 5_280_000], [
            $player->progress->level->value,
            $player->progress->experience->current->value,
            $player->progress->experience->maximum->value,
        ]);
        static::assertSame([6_800_132, 2899], [$player->wallet->gold->value, $player->wallet->coins->value]);
        static::assertFalse($player->member);
        static::assertSame(131, $player->bagSlots->value);
        static::assertEquals([new QuestMark(QuestPeriod::Day, 2, 6)], $player->marks->marks);
        static::assertSame('Data of made2903', $event->summary());
    }

    #[Test]
    public function it_rejects_player_data_it_cannot_read(): void
    {
        $this->expectException(MalformedEventException::class);

        PlayerDataLoadedEvent::from(
            JsonMessage::from(
                '{"t":"xt","b":{"r":-1,"o":{"cmd":"initUserDatas","a":[{"uid":45466,"data":{"strUsername":"made2903"}}]}}}',
            )->unwrap(),
        );
    }
}
