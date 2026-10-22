<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Lorekeeper\Apps\Desktop\Bots\HideFromPlayers;
use Lorekeeper\Apps\Desktop\Models\Account;
use Lorekeeper\Apps\Desktop\Models\Preferences;
use Lorekeeper\Apps\Desktop\Tests\Characters;
use Lorekeeper\Apps\Desktop\Tests\TestCase;
use Lorekeeper\Client\Client;
use Lorekeeper\Client\Connection;
use Lorekeeper\Client\Script\Disconnected;
use Lorekeeper\Core\Player\GameSetting;
use Lorekeeper\Core\Player\PlayerSettings;
use PHPUnit\Framework\Attributes\Test;
use Psl\Async\NullCancellationToken;
use Psl\TCP;

final class HideFromPlayersTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_log_says_what_is_hidden_before_the_settings_take_their_time(): void
    {
        $account = Account::query()->create(['name' => 'Hero', 'password' => bin2hex(random_bytes(8))]);
        $preferences = Preferences::current();
        $preferences->forceFill(array_fill_keys(array_keys(Preferences::BLOCKS), value: false))->save();
        $preferences->forceFill(['block_goto' => true, 'block_duels' => true])->save();

        $left = HideFromPlayers::apply($preferences, $account, $this->closedClient(), new NullCancellationToken());

        static::assertInstanceOf(Disconnected::class, $left);
        static::assertSame(['Hiding from other players: goto, duels'], $account->logs()->pluck('message')->all());
    }

    #[Test]
    public function nothing_is_logged_when_nothing_is_hidden(): void
    {
        $account = Account::query()->create(['name' => 'Hero', 'password' => bin2hex(random_bytes(8))]);
        $preferences = Preferences::current();
        $preferences->forceFill(array_fill_keys(array_keys(Preferences::BLOCKS), value: false))->save();

        HideFromPlayers::apply($preferences, $account, $this->closedClient(), new NullCancellationToken());

        static::assertSame([], $account->logs()->pluck('message')->all());
    }

    #[Test]
    public function settings_the_character_already_has_are_not_sent_again(): void
    {
        $account = Account::query()->create(['name' => 'Hero', 'password' => bin2hex(random_bytes(8))]);
        $preferences = $this->blocking(['block_goto', 'block_duels']);
        $settings = new PlayerSettings([
            GameSetting::Whispers,
            GameSetting::FriendRequests,
            GameSetting::PartyInvites,
            GameSetting::GuildInvites,
        ]);

        $left = HideFromPlayers::apply(
            $preferences,
            $account,
            $this->closedClient($settings),
            new NullCancellationToken(),
        );

        static::assertNull($left);
        static::assertSame([], $account->logs()->pluck('message')->all());
    }

    #[Test]
    public function only_the_settings_that_differ_are_sent_and_logged(): void
    {
        $account = Account::query()->create(['name' => 'Hero', 'password' => bin2hex(random_bytes(8))]);
        $preferences = $this->blocking(['block_goto', 'block_duels']);
        $settings = new PlayerSettings([
            GameSetting::Whispers,
            GameSetting::FriendRequests,
            GameSetting::PartyInvites,
            GameSetting::GuildInvites,
            GameSetting::Duels,
        ]);

        $left = HideFromPlayers::apply(
            $preferences,
            $account,
            $this->closedClient($settings),
            new NullCancellationToken(),
        );

        static::assertInstanceOf(Disconnected::class, $left);
        static::assertSame(['Hiding from other players: duels'], $account->logs()->pluck('message')->all());
    }

    /** @param list<string> $columns */
    private function blocking(array $columns): Preferences
    {
        $preferences = Preferences::current();
        $preferences->forceFill(array_fill_keys(array_keys(Preferences::BLOCKS), value: false))->save();
        $preferences->forceFill(array_fill_keys($columns, value: true))->save();

        return $preferences;
    }

    private function closedClient(?PlayerSettings $settings = null): Client
    {
        return new Client(
            new Connection(static::createConfiguredStub(TCP\StreamInterface::class, [
                'reachedEndOfDataSource' => true,
            ])),
            Characters::me('Hero', level: 4, map: 'battleon', settings: $settings),
        );
    }
}
