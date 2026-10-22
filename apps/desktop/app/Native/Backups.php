<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Native;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

final readonly class Backups
{
    public const int KEEP = 5;

    public function __construct(
        private string $folder,
    ) {}

    public function take(ConnectionInterface $database, Carbon $at): string
    {
        if (!is_dir($this->folder)) {
            mkdir($this->folder, permissions: 0o700, recursive: true);
        }

        $backup = "{$this->folder}/database-{$at->format('Y-m-d_His')}.sqlite";
        $database->statement('VACUUM INTO ?', [$backup]);
        chmod($backup, permissions: 0o600);
        $this->prune();

        return $backup;
    }

    private function prune(): void
    {
        $backups = File::glob("{$this->folder}/database-*.sqlite");
        sort($backups);

        foreach (array_slice($backups, offset: 0, length: -self::KEEP) as $old) {
            unlink($old);
        }
    }
}
