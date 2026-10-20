<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Lorekeeper\Apps\Desktop\Bots\RunStatus;
use Lorekeeper\Apps\Desktop\Bots\Scene\Backpack;
use Lorekeeper\Apps\Desktop\Bots\Scene\Feed;
use Lorekeeper\Apps\Desktop\Bots\Scene\Pulse;
use Lorekeeper\Apps\Desktop\Bots\Scene\Scene;
use Lorekeeper\Apps\Desktop\Filament\Resources\Accounts\Pages\ManageAccounts;
use Lorekeeper\Apps\Desktop\Livewire\CharacterSheet;
use Lorekeeper\Apps\Desktop\Models\Account;
use Lorekeeper\Apps\Desktop\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class CharacterSheetTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_sheet_shows_what_the_character_wears_and_what_its_class_does(): void
    {
        $account = $this->account();
        $account->runs()->create()->update(['status' => RunStatus::Stopped, 'scene' => self::scene()->toArray()]);
        $account->runs()->create()->update(['status' => RunStatus::Failed]);

        Livewire::test(CharacterSheet::class, ['account' => $account])
            ->assertSeeInOrder(['Level 34', 'Healer', 'Last seen', 'battleon-1234', '2,300', '123,456'])
            ->assertSeeInOrder([
                'Equipment',
                'Healer',
                'Not enhanced',
                'Necrotic Sword of Doom',
                'level 100',
                '±10% damage',
            ])
            ->assertSeeInOrder(['Lucky +6', 'level 40', 'Health Vampire'])
            ->assertSeeInOrder(['Auto Attack', '57% physical damage', 'A basic attack, taught to all adventurers.'])
            ->assertSeeInOrder(['Heal', 'restores 10 mana', 'up to 3 targets'])
            ->assertSeeInOrder(['Purify', 'Locked'])
            ->assertSeeInOrder(['Passives', 'Healing Hands', 'Heals 20% more.'])
            ->assertSeeInOrder(['Inventory', 'Crypto Token']);
    }

    #[Test]
    public function a_character_the_bot_never_saw_has_an_empty_sheet(): void
    {
        Livewire::test(CharacterSheet::class, ['account' => $this->account()])->assertSee(
            'The bot has not seen Hero in the game yet.',
        );
    }

    #[Test]
    public function a_row_on_the_home_opens_the_sheet(): void
    {
        $account = $this->account();

        Livewire::test(ManageAccounts::class)->assertSee("/accounts/{$account->id}");
    }

    /** @mago-expect lint:halstead */
    private static function scene(): Scene
    {
        return new Scene(
            [
                'name' => 'Hero',
                'level' => 34,
                'experience' => 21_000,
                'maxExperience' => 60_000,
                'gold' => 123_456,
                'member' => false,
                'health' => 1840,
                'maxHealth' => 2300,
                'mana' => 61,
                'maxMana' => 100,
                'class' => 'Healer',
                'category' => 'caster',
                'rank' => 7,
                'points' => 90_000,
                'rankFrom' => 78_400,
                'rankTo' => 129_600,
                'state' => 'idle',
                'away' => false,
                'map' => 'battleon',
                'room' => 1234,
                'items' => 3,
                'skills' => [
                    [
                        'slot' => 'AutoAttack',
                        'cost' => 0,
                        'cooldown' => 2.0,
                        'targets' => 1,
                        'target' => 'enemy',
                        'unlocked' => true,
                        'name' => 'Auto Attack',
                        'description' => 'A basic attack, taught to all adventurers.',
                        'damage' => 0.57,
                        'source' => 'physical',
                    ],
                    [
                        'slot' => 'First',
                        'cost' => 0,
                        'restores' => 10,
                        'cooldown' => 6.0,
                        'targets' => 3,
                        'target' => 'ally',
                        'unlocked' => true,
                        'name' => 'Heal',
                        'description' => null,
                        'damage' => null,
                        'source' => null,
                    ],
                    [
                        'slot' => 'Second',
                        'cost' => 20,
                        'cooldown' => 10.0,
                        'targets' => 1,
                        'target' => 'self',
                        'unlocked' => false,
                        'name' => 'Purify',
                        'description' => null,
                        'damage' => null,
                        'source' => null,
                    ],
                ],
                'passives' => [['name' => 'Healing Hands', 'description' => 'Heals 20% more.']],
            ],
            [],
            [],
            new Pulse(),
            new Feed(),
            now()->subHour()->getTimestamp(),
            new Backpack([
                [
                    'id' => 1,
                    'name' => 'Necrotic Sword of Doom',
                    'kind' => 'weapon',
                    'type' => 'Sword',
                    'quantity' => 1,
                    'member' => false,
                    'level' => 100,
                    'equipped' => true,
                    'enhancement' => [
                        'type' => 'Lucky',
                        'rarity' => '+6',
                        'level' => 40,
                        'effect' => ['name' => 'Health Vampire', 'description' => 'Heals you as you hit.'],
                    ],
                    'variance' => 10,
                ],
                [
                    'id' => 2,
                    'name' => 'Healer',
                    'kind' => 'class',
                    'type' => 'Class',
                    'quantity' => 1,
                    'member' => false,
                    'level' => 1,
                    'equipped' => true,
                    'enhancement' => null,
                ],
                [
                    'id' => 3,
                    'name' => 'Crypto Token',
                    'kind' => 'resource',
                    'type' => 'Resource',
                    'quantity' => 4,
                    'member' => false,
                    'level' => 1,
                    'equipped' => false,
                    'enhancement' => null,
                ],
            ]),
        );
    }

    private function account(): Account
    {
        return Account::query()->create(['name' => 'Hero', 'password' => bin2hex(random_bytes(8))]);
    }
}
