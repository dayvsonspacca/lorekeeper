<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Player\Hero;

use Lorekeeper\Core\World\Quest\QuestGroup;
use Lorekeeper\Core\World\Quest\QuestGroupId;
use Lorekeeper\Core\World\Quest\QuestGroupNumber;
use Lorekeeper\Server\Event\Player\Hero\HeroDataLoadedEvent;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class HeroDataLoadedEventTest extends TestCase
{
    private const string HERO = <<<'JSON'
        {"t":"xt","b":{"r":-1,"o":{"cmd":"initUserDatas","a":[{"uid":45466,"data":{"strUsername":"Hero","intLevel":81,"intExp":4514754,"intExpToLevel":5280000,"intGold":6800132,"intCoins":2899,"iUpg":0,"iUpgDays":-5772,"iBagSlots":131,"iBankSlots":131,"id0":0,"id1":0,"id2":64,"id3":0,"id4":0,"id5":0,"id6":0,"id7":0,"iw0":0,"im0":0,"ia0":139536,"ip0":0}}]}}}
        JSON;

    private const string OURS =
        '{"uid":45466,"data":{"strUsername":"Hero","intLevel":81,"intExp":0,"intExpToLevel":1000,'
            . '"intGold":0,"intCoins":0,"iUpg":0,"iUpgDays":0,"iBagSlots":30,"iBankSlots":30}}';

    private const string OTHER = '{"uid":1,"data":{"strUsername":"Hero","intLevel":81}}';

    #[Test]
    public function it_reads_the_level_wallet_membership_slots_and_quests_of_our_player(): void
    {
        $event = HeroDataLoadedEvent::from(JsonMessage::from(self::HERO)->unwrap())->unwrap();
        $player = $event->data;

        static::assertSame([45_466, 'Hero'], [$player->spawnId->value, $player->name->value]);
        static::assertSame([81, 4_514_754, 5_280_000], [
            $player->level->value,
            $player->experience->current->value,
            $player->experience->maximum->value,
        ]);
        static::assertSame([6_800_132, 2899], [$player->wallet->gold->value, $player->wallet->coins->value]);
        static::assertFalse($player->member);
        static::assertSame([131, 131], [$player->bagSlots->value, $player->bankSlots->value]);
        static::assertEquals([new QuestGroupId(QuestGroup::Daily, new QuestGroupNumber(70))], $player->quests);
        static::assertSame('Data of Hero', $event->summary());
    }

    #[Test]
    public function it_reads_our_player_among_the_others(): void
    {
        $event = HeroDataLoadedEvent::from(
            JsonMessage::from(self::initUserDatas(self::OTHER, self::OURS))->unwrap(),
        )->unwrap();

        static::assertSame([45_466, 'Hero'], [$event->data->spawnId->value, $event->data->name->value]);
    }

    #[Test]
    public function it_is_not_the_data_of_another_player(): void
    {
        static::assertTrue(
            HeroDataLoadedEvent::from(JsonMessage::from(self::initUserDatas(self::OTHER))->unwrap())->isNone(),
        );
    }

    #[Test]
    public function it_rejects_the_data_of_more_than_one_hero(): void
    {
        $this->expectException(MalformedEventException::class);

        HeroDataLoadedEvent::from(JsonMessage::from(self::initUserDatas(self::OURS, self::OURS))->unwrap());
    }

    #[Test]
    public function it_rejects_player_data_it_cannot_read(): void
    {
        $this->expectException(MalformedEventException::class);

        HeroDataLoadedEvent::from(
            JsonMessage::from('{"t":"xt","b":{"r":-1,"o":{"cmd":"initUserDatas","a":[{"uid":45466}]}}}')->unwrap(),
        );
    }

    #[Test]
    public function it_rejects_hero_data_it_cannot_read(): void
    {
        $this->expectException(MalformedEventException::class);

        HeroDataLoadedEvent::from(
            JsonMessage::from(self::initUserDatas('{"uid":45466,"data":{"strUsername":"Hero","intGold":0}}'))->unwrap(),
        );
    }

    private static function initUserDatas(string ...$entries): string
    {
        return '{"t":"xt","b":{"r":-1,"o":{"cmd":"initUserDatas","a":[' . implode(',', $entries) . ']}}}';
    }
}
