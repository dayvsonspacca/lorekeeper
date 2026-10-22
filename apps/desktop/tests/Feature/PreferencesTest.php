<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Lorekeeper\Apps\Desktop\Filament\Pages\ManagePreferences;
use Lorekeeper\Apps\Desktop\Models\Preferences;
use Lorekeeper\Apps\Desktop\Tests\TestCase;
use Lorekeeper\Core\Player\GameSetting;
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
    public function playing_together_needs_private_rooms(): void
    {
        Livewire::test(ManagePreferences::class)
            ->assertFormFieldEnabled('play_together')
            ->set('data.private_rooms', false)
            ->assertFormFieldDisabled('play_together');

        static::assertFalse(Preferences::current()->private_rooms);
    }

    #[Test]
    public function the_app_settings_wait_for_a_later_version(): void
    {
        $page = Livewire::test(ManagePreferences::class)
            ->assertSee([
                'Game',
                'Application',
                'Update automatically',
                'Start when the computer starts',
                'Not available yet.',
            ])
            ->assertFormFieldDisabled('update_automatically')
            ->assertFormFieldDisabled('start_with_computer')
            ->set('data.update_automatically', true)
            ->set('data.start_with_computer', true)
            ->set('data.block_duels', false);

        $page->call('save');

        static::assertFalse(Preferences::current()->blocks(GameSetting::Duels));
        static::assertArrayNotHasKey('update_automatically', Preferences::current()->getAttributes());
        static::assertArrayNotHasKey('start_with_computer', Preferences::current()->getAttributes());
    }
}
