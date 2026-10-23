<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Lorekeeper\Apps\Desktop\Filament\Pages\ManagePreferences;
use Lorekeeper\Apps\Desktop\Models\Preferences;
use Lorekeeper\Apps\Desktop\Tests\TestCase;
use Lorekeeper\Core\Game\Setting\GameSetting;
use PHPUnit\Framework\Attributes\Test;

final class PreferencesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_new_install_plays_together_in_private_rooms_and_hides_from_everyone(): void
    {
        $preferences = Preferences::current();

        static::assertTrue($preferences->private_rooms);
        static::assertTrue($preferences->play_together);

        foreach (GameSetting::cases() as $setting) {
            static::assertTrue($preferences->blocks($setting));
        }
        static::assertSame(1, Preferences::query()->count());
        static::assertSame($preferences->id, Preferences::current()->id);
    }

    #[Test]
    public function each_toggle_is_saved_as_soon_as_it_changes(): void
    {
        $this
            ->get('/preferences')
            ->assertOk()
            ->assertSee(['Play in private rooms', 'Play together', 'Block goto', 'Ignore duel invites']);

        Livewire::test(ManagePreferences::class)->assertSet('data.block_whispers', true)->set(
            'data.block_whispers',
            false,
        );

        $preferences = Preferences::current();
        static::assertFalse($preferences->blocks(GameSetting::Whispers));
        static::assertTrue($preferences->blocks(GameSetting::Goto));
    }

    #[Test]
    public function the_rooms_come_back_on_a_database_that_kept_them(): void
    {
        $migration = require database_path('migrations/2026_10_22_000001_let_the_bots_play_together_again.php');

        $migration->up();

        static::assertTrue(Schema::hasColumns('preferences', ['private_rooms', 'play_together']));
        static::assertTrue(Preferences::current()->play_together);
    }

    #[Test]
    public function playing_together_needs_private_rooms(): void
    {
        Livewire::test(ManagePreferences::class)
            ->assertFormFieldEnabled('play_together')
            ->set('data.private_rooms', false)
            ->assertFormFieldDisabled('play_together');

        static::assertFalse(Preferences::current()->private_rooms);
    }

    #[Test]
    public function new_versions_are_announced_until_turned_off(): void
    {
        static::assertTrue(Preferences::current()->notify_updates);

        Livewire::test(ManagePreferences::class)
            ->assertSee(['Game', 'Application', 'Tell me when a new version is out'])
            ->assertDontSee('Not available yet.')
            ->assertFormFieldEnabled('notify_updates')
            ->set('data.notify_updates', false);

        static::assertFalse(Preferences::current()->notify_updates);
    }

    #[Test]
    public function starting_with_the_computer_is_off_until_asked(): void
    {
        static::assertFalse(Preferences::current()->start_with_computer);

        Livewire::test(ManagePreferences::class)
            ->assertSee('Start when the computer starts')
            ->assertFormFieldEnabled('start_with_computer')
            ->set('data.start_with_computer', true);

        static::assertTrue(Preferences::current()->start_with_computer);
    }
}
