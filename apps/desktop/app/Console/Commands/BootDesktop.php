<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\DB;
use Lorekeeper\Apps\Desktop\Models\BotRun;
use Lorekeeper\Apps\Desktop\Native\Backups;
use Symfony\Component\Console\Attribute\AsCommand;
use Throwable;

#[AsCommand(
    name: 'desktop:boot',
    description: 'Bring the database up to date and close the runs the last session left open',
)]
final class BootDesktop extends Command
{
    /** @var string */
    protected $signature = 'desktop:boot';

    public function handle(Migrator $migrator): int
    {
        $backup =
            $this->pending($migrator) === [] || DB::connection()->getDriverName() !== 'sqlite'
                ? null
                : new Backups(storage_path('backups'))->take(DB::connection(), now());

        try {
            $this->call('migrate', ['--force' => true]);
        } catch (Throwable $failure) {
            $this->error($failure->getMessage());
            if ($backup !== null) {
                $this->error("The database as it was before this update is kept at {$backup}");
            }

            return self::FAILURE;
        }

        BotRun::interrupted();

        return self::SUCCESS;
    }

    /** @return list<string> */
    private function pending(Migrator $migrator): array
    {
        if (!$migrator->repositoryExists()) {
            return [];
        }

        $files = $migrator->getMigrationFiles([...$migrator->paths(), database_path('migrations')]);

        return array_values(array_diff(array_keys($files), $migrator->getRepository()->getRan()));
    }
}
