<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Native;

use Native\Desktop\Facades\Window;

final readonly class MainWindow
{
    public const string ID = 'main';

    public static function open(): void
    {
        Window::open(self::ID)
            ->title('Lorekeeper')
            ->width(1280)
            ->height(820)
            ->minWidth(900)
            ->minHeight(600)
            ->rememberState();
    }

    public static function reviveIfSilent(): void
    {
        if (!Heartbeat::silent()) {
            return;
        }

        Heartbeat::beat();
        Window::reload(self::ID);
    }
}
