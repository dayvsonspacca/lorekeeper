<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Lorekeeper\Apps\Wiki\Filament\Resources\Players\Pages\ListPlayers;
use Lorekeeper\Apps\Wiki\Models\Player;
use Lorekeeper\Apps\Wiki\Models\User;
use Lorekeeper\Apps\Wiki\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class PlayersPageTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_players_the_bots_met(): void
    {
        $this->actingAs(User::query()->create([
            'name' => 'Hero',
            'email' => 'hero@example.com',
            'password' => bin2hex(random_bytes(16)),
        ]));
        $hero = Player::query()->create(['name' => 'Hero']);
        $other = Player::query()->create(['name' => 'Hero 2', 'aqw_id' => 42]);

        $this->get('/admin/players')->assertOk()->assertSee('Players');

        Livewire::test(ListPlayers::class)
            ->assertCanSeeTableRecords([$hero, $other])
            ->searchTable('2')
            ->assertCanSeeTableRecords([$other])
            ->assertCanNotSeeTableRecords([$hero]);
    }
}
