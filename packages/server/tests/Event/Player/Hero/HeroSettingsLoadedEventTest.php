<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Player\Hero;

use Lorekeeper\Core\Game\Setting\GameSetting;
use Lorekeeper\Server\Event\Player\Hero\HeroSettingsLoadedEvent;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use Lorekeeper\Server\Message\XmlMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class HeroSettingsLoadedEventTest extends TestCase
{
    private const array ALL = [
        GameSetting::Goto,
        GameSetting::FriendRequests,
        GameSetting::PartyInvites,
        GameSetting::GuildInvites,
        GameSetting::Whispers,
        GameSetting::Duels,
    ];

    #[Test]
    public function a_setting_turned_off_sets_its_bit_and_turning_it_on_clears_it(): void
    {
        static::assertSame([], self::allowed(10_128));
        static::assertSame(self::ALL, self::allowed(0));
        static::assertSame(self::ALL, self::allowed(36));
        static::assertSame(
            [GameSetting::FriendRequests, GameSetting::PartyInvites, GameSetting::GuildInvites, GameSetting::Duels],
            self::allowed(0b100_0001_0000),
        );
    }

    #[Test]
    public function it_reads_our_player_among_the_others(): void
    {
        $event = HeroSettingsLoadedEvent::from(self::message([
            ['uid' => 1, 'data' => ['strUsername' => 'Hero2', 'intLevel' => 81]],
            ['uid' => 45_466, 'data' => ['strUsername' => 'Hero', 'intGold' => 0, 'ia1' => 10_128]],
        ]))->unwrap();

        static::assertSame([45_466, 'Hero'], [$event->data->spawnId->value, $event->data->name->value]);
        static::assertSame('Settings of Hero', $event->summary());
    }

    #[Test]
    public function it_hears_nothing_when_our_player_sent_no_settings(): void
    {
        static::assertTrue(
            HeroSettingsLoadedEvent::from(self::message([
                ['uid' => 45_466, 'data' => ['strUsername' => 'Hero', 'intGold' => 0]],
            ]))->isNone(),
        );
        static::assertTrue(
            HeroSettingsLoadedEvent::from(self::message([
                ['uid' => 1, 'data' => ['strUsername' => 'Hero2', 'intLevel' => 81]],
            ]))->isNone(),
        );
        static::assertTrue(
            HeroSettingsLoadedEvent::from(
                JsonMessage::from('{"t":"xt","b":{"r":-1,"o":{"cmd":"loadPrefs"}}}')->unwrap(),
            )->isNone(),
        );
        static::assertTrue(HeroSettingsLoadedEvent::from(XmlMessage::from('<msg t="sys"></msg>')->unwrap())->isNone());
    }

    #[Test]
    public function it_rejects_settings_it_cannot_read(): void
    {
        $this->expectException(MalformedEventException::class);

        HeroSettingsLoadedEvent::from(self::message([
            ['uid' => 45_466, 'data' => ['strUsername' => 'Hero', 'intGold' => 0, 'ia1' => 'all']],
        ]));
    }

    /** @return list<GameSetting> */
    private static function allowed(int $bits): array
    {
        return HeroSettingsLoadedEvent::from(self::message([
            ['uid' => 45_466, 'data' => ['strUsername' => 'Hero', 'intGold' => 0, 'ia1' => $bits]],
        ]))
            ->unwrap()
            ->data
            ->settings
            ->allowed;
    }

    /** @param list<array<string, mixed>> $players */
    private static function message(array $players): JsonMessage
    {
        return JsonMessage::from((string) json_encode([
            't' => 'xt',
            'b' => ['r' => -1, 'o' => ['cmd' => 'initUserDatas', 'a' => $players]],
        ]))->unwrap();
    }
}
