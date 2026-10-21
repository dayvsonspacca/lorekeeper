<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Filament\Pages;

use BackedEnum;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
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
        $this->form->fill(Preferences::current()->only(array_keys(Preferences::BLOCKS)));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make('Preferences')
                    ->extraAttributes(['class' => 'lk-tabs'])
                    ->tabs([
                        Tab::make('Game')->icon(Heroicon::OutlinedPuzzlePiece)->schema(self::game()),
                        Tab::make('Application')->icon(Heroicon::OutlinedComputerDesktop)->schema(self::app()),
                    ]),
            ]);
    }

    /** @return list<Section> */
    private static function game(): array
    {
        return [
            Section::make('Hide from other players')
                ->contained(false)
                ->description('Applied each time a bot enters the game.')
                ->schema([
                    self::toggle('block_goto', 'Block goto'),
                    self::toggle('block_whispers', 'Ignore whispers'),
                    self::toggle('block_friend_requests', 'Ignore friend requests'),
                    self::toggle('block_party_invites', 'Ignore party invites'),
                    self::toggle('block_guild_invites', 'Ignore guild invites'),
                    self::toggle('block_duels', 'Ignore duel invites'),
                ])
                ->columns(2),
        ];
    }

    /** @return list<Section> */
    private static function app(): array
    {
        return [
            Section::make('Updates')
                ->contained(false)
                ->description('How the app keeps itself up to date.')
                ->schema([self::unavailable('update_automatically', 'Update automatically')]),
            Section::make('Startup')
                ->contained(false)
                ->description('When the app opens.')
                ->schema([self::unavailable('start_with_computer', 'Start when the computer starts')]),
        ];
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

    private static function unavailable(string $name, string $label): Toggle
    {
        return Toggle::make($name)
            ->label($label)
            ->default(false)
            ->disabled()
            ->dehydrated(false)
            ->hint(new HtmlString(Blade::render(
                '<x-filament::badge color="primary" icon="heroicon-m-lock-closed">Soon</x-filament::badge>',
            )))
            ->extraFieldWrapperAttributes(['class' => 'lk-unavailable'])
            ->helperText('Not available yet.');
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
