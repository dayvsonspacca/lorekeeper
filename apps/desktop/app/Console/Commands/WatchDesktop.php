<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Lorekeeper\Apps\Desktop\Native\MainWindow;
use Lorekeeper\Apps\Desktop\Native\Tray;
use Native\Desktop\Facades\MenuBar;
use Psl\Async;
use Psl\DateTime\Duration;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'desktop:watch', description: 'Keep the tray menu current and bring back a window that went blank')]
final class WatchDesktop extends Command
{
    /** @var string */
    protected $signature = 'desktop:watch';

    public function handle(): int
    {
        $shown = '';
        while (true) {
            $menu = Tray::menu();
            $now = (string) json_encode($menu);

            try {
                if ($now !== $shown) {
                    MenuBar::contextMenu($menu);
                    $shown = $now;
                }

                MainWindow::reviveIfSilent();
            } catch (ConnectionException) {
                $shown = '';
            }

            Async\sleep(Duration::seconds(3));
        }
    }
}
