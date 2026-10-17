<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Lorekeeper\Apps\Desktop\Native\Tray;
use Native\Desktop\Facades\MenuBar;
use Psl\Async;
use Psl\DateTime\Duration;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'tray:watch', description: 'Keep the tray menu telling which bots are on')]
final class WatchTray extends Command
{
    /** @var string */
    protected $signature = 'tray:watch';

    public function handle(): int
    {
        $shown = '';
        while (true) {
            $menu = Tray::menu();
            $now = (string) json_encode($menu);

            if ($now !== $shown) {
                try {
                    MenuBar::contextMenu($menu);
                    $shown = $now;
                } catch (ConnectionException) {
                    $shown = '';
                }
            }

            Async\sleep(Duration::seconds(3));
        }
    }
}
