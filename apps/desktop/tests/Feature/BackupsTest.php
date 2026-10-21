<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests\Feature;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Lorekeeper\Apps\Desktop\Native\Backups;
use Lorekeeper\Apps\Desktop\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class BackupsTest extends TestCase
{
    #[Test]
    public function only_the_latest_backups_are_kept(): void
    {
        $folder = sys_get_temp_dir() . '/lorekeeper-backups-' . bin2hex(random_bytes(4));
        File::ensureDirectoryExists($folder);
        foreach (range(1, Backups::KEEP) as $day) {
            File::put(sprintf('%s/database-2026-10-%02d_120000.sqlite', $folder, $day), 'old');
        }

        $backup = new Backups($folder)->take(DB::connection(), Carbon::parse('2026-10-21 14:30:00'));

        static::assertSame("{$folder}/database-2026-10-21_143000.sqlite", $backup);
        static::assertSame(0o600, fileperms($backup) & 0o777);
        static::assertFileDoesNotExist("{$folder}/database-2026-10-01_120000.sqlite");
        static::assertCount(Backups::KEEP, File::glob("{$folder}/database-*.sqlite"));

        File::deleteDirectory($folder);
    }
}
