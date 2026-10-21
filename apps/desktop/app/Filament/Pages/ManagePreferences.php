<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Filament\Pages;

use BackedEnum;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Lorekeeper\Apps\Desktop\Models\Preferences;
use Override;

final class ManagePreferences extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static ?int $navigationSort = 10;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationLabel = 'Preferences';

    protected static ?string $title = 'Preferences';

    protected static ?string $slug = 'preferences';

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(): void
    {
        $this->form->fill(Preferences::current()->only([
            'private_rooms',
            'play_together',
            ...array_keys(Preferences::BLOCKS),
        ]));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Rooms')
                    ->description('Where the bots play once they join a map.')
                    ->schema([
                        self::toggle('private_rooms', 'Play in private rooms')
                            ->helperText(
                                'Every map is joined in a room with a random number, so other players rarely share it.',
                            ),
                        self::toggle('play_together', 'Play together')
                            ->helperText(
                                'Bots of this app that join the same map share one private room, so they fight side by side.',
                            )
                            ->disabled(static fn(Get $get): bool => !$get('private_rooms')),
                    ]),
                Section::make('Hide from other players')
                    ->description('Applied each time a bot enters the game.')
                    ->schema([
                        self::toggle('block_goto', 'Block goto')->helperText('Nobody can /goto a bot.'),
                        self::toggle('block_whispers', 'Ignore whispers'),
                        self::toggle('block_friend_requests', 'Ignore friend requests'),
                        self::toggle('block_party_invites', 'Ignore party invites'),
                        self::toggle('block_guild_invites', 'Ignore guild invites'),
                        self::toggle('block_duels', 'Ignore duel invites'),
                    ])
                    ->columns(2),
            ]);
    }

    #[Override]
    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedSchema::make('form')]);
    }

    public function save(): void
    {
        Preferences::current()->update($this->form->getState());
    }

    private static function toggle(string $name, string $label): Toggle
    {
        return Toggle::make($name)
            ->label($label)
            ->live()
            ->afterStateUpdated(static function (ManagePreferences $livewire): void {
                $livewire->save();
            });
    }
}
