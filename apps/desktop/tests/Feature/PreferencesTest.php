<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Lorekeeper\Apps\Desktop\Filament\Pages\ManagePreferences;
use Lorekeeper\Apps\Desktop\Models\Preferences;
use Lorekeeper\Apps\Desktop\Tests\TestCase;
use Lorekeeper\Client\Command\Settings\GameSetting;
use PHPUnit\Framework\Attributes\Test;

final class PreferencesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_new_install_plays_in_private_rooms_and_hides_from_everyone(): void
    {
        $preferences = Preferences::current();

        static::assertTrue($preferences->private_rooms);
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
            ->assertSee(['Play in private rooms', 'Block goto', 'Ignore duel invites']);

        Livewire::test(ManagePreferences::class)
            ->assertSet('data.private_rooms', true)
            ->set('data.private_rooms', false)
            ->set('data.block_whispers', false);

        $preferences = Preferences::current();
        static::assertFalse($preferences->private_rooms);
        static::assertFalse($preferences->blocks(GameSetting::Whispers));
        static::assertTrue($preferences->blocks(GameSetting::Goto));
    }
}
