<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests\Feature;

use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Lorekeeper\Apps\Desktop\Filament\Resources\Scripts\Pages\ManageScripts;
use Lorekeeper\Apps\Desktop\Models\Script;
use Lorekeeper\Apps\Desktop\Scripts\FlowFile;
use Lorekeeper\Apps\Desktop\Scripts\Library;
use Lorekeeper\Apps\Desktop\Tests\ScriptFiles;
use Lorekeeper\Apps\Desktop\Tests\TestCase;
use Override;
use PHPUnit\Framework\Attributes\Test;

final class ScriptsTest extends TestCase
{
    use RefreshDatabase;

    private string $downloads;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->downloads = sys_get_temp_dir() . '/lorekeeper-downloads-' . bin2hex(random_bytes(4));
        mkdir($this->downloads);
        config(['desktop.downloads' => $this->downloads]);
    }

    #[Override]
    protected function tearDown(): void
    {
        $files = glob("{$this->downloads}/*");
        array_map(unlink(...), $files === false ? [] : $files);
        rmdir($this->downloads);

        parent::tearDown();
    }

    #[Test]
    public function each_script_shows_how_big_it_is_and_whether_it_can_play(): void
    {
        $ready = ScriptFiles::crypto();
        $empty = Script::query()->create(['name' => 'Blank']);
        $broken = Script::query()->create([
            'name' => 'Broken',
            'drawing' => ['start' => 'a', 'nodes' => [['id' => 'a', 'do' => 'join']], 'wires' => []],
        ]);

        Livewire::test(ManageScripts::class)
            ->assertCanSeeTableRecords([$ready, $empty, $broken])
            ->assertTableColumnStateSet('state', 'Ready', $ready)
            ->assertTableColumnStateSet('state', 'Empty', $empty)
            ->assertTableColumnStateSet('state', '1 problem', $broken)
            ->assertTableColumnStateSet('nodes', '6 nodes', $ready)
            ->assertTableColumnStateSet('nodes', '1 node', $broken)
            ->assertSee(['by Hero', 'No author']);
        $this->get('/scripts')->assertOk()->assertSee('Scripts');
    }

    #[Test]
    public function a_new_script_starts_empty(): void
    {
        Livewire::test(ManageScripts::class)
            ->callAction('create', ['name' => 'Daily run', 'author' => 'Hero', 'group' => 'Dailies'])
            ->assertHasNoActionErrors();

        $script = Script::query()->sole();
        static::assertSame('Daily run', $script->name);
        static::assertSame('Dailies', $script->group);
        static::assertSame(0, $script->nodes());
        static::assertSame(['start' => null, 'nodes' => [], 'wires' => []], $script->drawing);
    }

    #[Test]
    public function a_shared_file_is_imported_as_it_was_drawn(): void
    {
        Livewire::test(ManageScripts::class)
            ->callAction('import', ['file' => ScriptFiles::upload(ScriptFiles::fixture())])
            ->assertHasNoActionErrors()
            ->assertNotified('Imported Crypto Token daily');

        $script = Script::query()->sole();
        static::assertSame('Hero', $script->author);
        static::assertSame('Dailies', $script->group);
        static::assertEquals(FlowFile::parse(ScriptFiles::fixture()), $script->flow());
    }

    #[Test]
    public function a_file_that_is_not_a_script_is_turned_away_with_its_problems(): void
    {
        Livewire::test(ManageScripts::class)
            ->callAction('import', ['file' => ScriptFiles::upload('{"lorekeeper": 1, "name": "X"}')])
            ->assertNotified('This file is not a script Lorekeeper can read');

        static::assertSame(0, Script::query()->count());
    }

    #[Test]
    public function an_export_lands_in_the_downloads_folder_without_overwriting(): void
    {
        $script = ScriptFiles::crypto();

        Livewire::test(ManageScripts::class)
            ->callAction(TestAction::make('export')->table($script))
            ->callAction(TestAction::make('export')->table($script))
            ->assertNotified("Exported {$script->name}");

        static::assertEquals(
            FlowFile::parse(ScriptFiles::fixture()),
            FlowFile::parse((string) file_get_contents("{$this->downloads}/crypto-token-daily.lorebot")),
        );
        static::assertFileExists("{$this->downloads}/crypto-token-daily-2.lorebot");
    }

    #[Test]
    public function a_duplicate_keeps_the_drawing_under_a_new_name(): void
    {
        $script = ScriptFiles::crypto();

        Livewire::test(ManageScripts::class)->callAction(TestAction::make('replicate')->table($script), [
            'name' => 'Crypto Token for alts',
        ])->assertHasNoActionErrors();

        $copy = Script::query()->where('name', 'Crypto Token for alts')->sole();
        static::assertSame($script->drawing, $copy->drawing);
        static::assertSame('Hero', $copy->author);
        static::assertSame('Dailies', $copy->group);
    }

    #[Test]
    public function the_scripts_ready_to_play_are_offered_by_group(): void
    {
        $crypto = ScriptFiles::crypto();
        $tokens = ScriptFiles::crypto();
        $tokens->update(['name' => 'Tokens of Collection daily']);
        $loose = ScriptFiles::crypto();
        $loose->update(['name' => 'Level up', 'group' => null]);
        Script::query()->create(['name' => 'Half drawn', 'group' => 'Dailies']);

        static::assertSame(
            [
                $loose->id => 'Level up',
                'Dailies' => [$crypto->id => 'Crypto Token daily', $tokens->id => 'Tokens of Collection daily'],
            ],
            Library::playable(),
        );
        static::assertSame(['Dailies'], Library::groups());
    }

    #[Test]
    public function a_script_can_be_renamed_and_deleted(): void
    {
        $script = ScriptFiles::crypto();

        Livewire::test(ManageScripts::class)->callAction(TestAction::make('edit')->table($script), [
            'name' => 'Crypto',
            'author' => null,
        ])->assertHasNoActionErrors();
        static::assertSame('Crypto', $script->refresh()->name);
        static::assertNull($script->author);

        Livewire::test(ManageScripts::class)->callAction(TestAction::make('delete')->table($script));
        static::assertSame(0, Script::query()->count());
    }
}
