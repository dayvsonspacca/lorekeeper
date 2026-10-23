<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Player;

use Lorekeeper\Core\Game\Setting\GameSetting;
use Lorekeeper\Server\Event\Player\PlayerSettingsLoadedEvent;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use Lorekeeper\Server\Message\XmlMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PlayerSettingsLoadedEventTest extends TestCase
{
    #[Test]
    public function a_setting_turned_off_sets_its_bit_and_turning_it_on_clears_it(): void
    {
        $event = PlayerSettingsLoadedEvent::from(self::message([
            ['uid' => 45_466, 'data' => ['strUsername' => 'Hero', 'ia1' => 10_128]],
            ['uid' => 45_467, 'data' => ['strUsername' => 'Hero2', 'ia1' => 0]],
            ['uid' => 45_468, 'data' => ['strUsername' => 'Hero3', 'ia1' => 36]],
            ['uid' => 45_469, 'data' => ['strUsername' => 'Hero4', 'ia1' => 0b100_0001_0000]],
            ['uid' => 45_470, 'data' => ['strUsername' => 'Hero5']],
        ]))->unwrap();
        $all = [
            GameSetting::Goto,
            GameSetting::FriendRequests,
            GameSetting::PartyInvites,
            GameSetting::GuildInvites,
            GameSetting::Whispers,
            GameSetting::Duels,
        ];

        static::assertSame([45_466, 'Hero'], [
            $event->players[0]->spawnId->value,
            $event->players[0]->name->value,
        ]);
        static::assertSame([], $event->players[0]->settings->allowed);
        static::assertSame($all, $event->players[1]->settings->allowed);
        static::assertSame($all, $event->players[2]->settings->allowed);
        static::assertSame(
            [GameSetting::FriendRequests, GameSetting::PartyInvites, GameSetting::GuildInvites, GameSetting::Duels],
            $event->players[3]->settings->allowed,
        );
        static::assertCount(4, $event->players);
        static::assertSame('Settings of Hero, Hero2, Hero3, Hero4', $event->summary());
    }

    #[Test]
    public function it_hears_nothing_when_no_player_sent_its_settings(): void
    {
        static::assertTrue(
            PlayerSettingsLoadedEvent::from(self::message([
                ['uid' => 45_466, 'data' => ['strUsername' => 'Hero']],
            ]))->isNone(),
        );
        static::assertTrue(
            PlayerSettingsLoadedEvent::from(
                JsonMessage::from('{"t":"xt","b":{"r":-1,"o":{"cmd":"loadPrefs"}}}')->unwrap(),
            )->isNone(),
        );
        static::assertTrue(
            PlayerSettingsLoadedEvent::from(XmlMessage::from('<msg t="sys"></msg>')->unwrap())->isNone(),
        );
    }

    #[Test]
    public function it_rejects_settings_it_cannot_read(): void
    {
        $this->expectException(MalformedEventException::class);

        PlayerSettingsLoadedEvent::from(self::message([
            ['uid' => 45_466, 'data' => ['strUsername' => 'Hero', 'ia1' => 'all']],
        ]));
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
