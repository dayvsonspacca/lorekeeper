<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Native;

use Native\Desktop\Facades\Window;

final readonly class MainWindow
{
    public static function open(): void
    {
        Window::open()
            ->title('Lorekeeper')
            ->width(1280)
            ->height(820)
            ->minWidth(900)
            ->minHeight(600)
            ->rememberState();
    }
}
