<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Lorekeeper\Apps\Desktop\Models\Account;
use Lorekeeper\Apps\Desktop\Models\BotLog;
use Lorekeeper\Apps\Desktop\Tests\TestCase;
use Override;
use PDO;
use PHPUnit\Framework\Attributes\Test;

final class BootDesktopTest extends TestCase
{
    use DatabaseMigrations;

    private string $folder = '';

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->folder = sys_get_temp_dir() . '/lorekeeper-boot-' . bin2hex(random_bytes(4));
        File::ensureDirectoryExists("{$this->folder}/storage");
        File::ensureDirectoryExists("{$this->folder}/migrations");
        $this->app?->useStoragePath("{$this->folder}/storage");
    }

    #[Override]
    protected function tearDown(): void
    {
        File::deleteDirectory($this->folder);

        parent::tearDown();
    }

    #[Test]
    public function an_update_keeps_the_database_as_it_was_before_migrating(): void
    {
        Account::query()->create(['name' => 'Hero', 'password' => bin2hex(random_bytes(8))]);
        $this->update('Schema::create("treasures", static fn ($table) => $table->id());');

        $this->artisan('desktop:boot')->assertSuccessful();

        static::assertTrue(Schema::hasTable('treasures'));
        $backups = File::glob("{$this->folder}/storage/backups/database-*.sqlite");
        static::assertCount(1, $backups);
        $before = new PDO("sqlite:{$backups[0]}");
        static::assertSame('Hero', $before->query('select name from accounts')?->fetchColumn());
        static::assertFalse($before->query("select 1 from sqlite_master where name = 'treasures'")?->fetchColumn());
    }

    #[Test]
    public function a_failed_update_says_where_the_database_was_kept(): void
    {
        $this->update('throw new RuntimeException("the treasure chest is locked");');

        $this
            ->artisan('desktop:boot')
            ->expectsOutputToContain('the treasure chest is locked')
            ->expectsOutputToContain("kept at {$this->folder}/storage/backups/database-")
            ->assertFailed();
    }

    #[Test]
    public function a_start_without_updates_takes_no_backup(): void
    {
        $this->artisan('desktop:boot')->assertSuccessful();

        static::assertDirectoryDoesNotExist("{$this->folder}/storage/backups");
    }

    #[Test]
    public function a_start_trims_the_logs_a_long_session_left(): void
    {
        $account = Account::query()->create(['name' => 'Hero', 'password' => bin2hex(random_bytes(8))]);
        BotLog::query()->insert(array_map(
            static fn(int $line): array => [
                'account_id' => $account->id,
                'message' => "Line {$line}",
                'level' => 'info',
                'created_at' => now(),
            ],
            range(1, BotLog::KEEP + 10),
        ));

        $this->artisan('desktop:boot')->assertSuccessful();

        static::assertSame(BotLog::KEEP, $account->logs()->count());
    }

    private function update(string $up): void
    {
        File::put("{$this->folder}/migrations/2099_01_01_000000_an_update.php", <<<PHP
            <?php

            use Illuminate\\Database\\Migrations\\Migration;
            use Illuminate\\Support\\Facades\\Schema;

            return new class extends Migration {
                public function up(): void
                {
                    {$up}
                }
            };
            PHP);
        $this->app?->make('migrator')->path("{$this->folder}/migrations");
    }
}
