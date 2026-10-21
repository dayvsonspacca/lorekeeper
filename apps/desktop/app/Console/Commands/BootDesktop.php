<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Console\Commands;

use Illuminate\Console\Command;
use Lorekeeper\Apps\Desktop\Models\BotRun;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'desktop:boot',
    description: 'Bring the database up to date and close the runs the last session left open',
)]
final class BootDesktop extends Command
{
    /** @var string */
    protected $signature = 'desktop:boot';

    public function handle(): int
    {
        $this->call('migrate', ['--force' => true]);
        BotRun::interrupted();

        return self::SUCCESS;
    }
}
