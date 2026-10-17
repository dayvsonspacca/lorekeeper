<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Native;

use Lorekeeper\Apps\Desktop\Models\Account;
use Native\Desktop\Facades\Menu;
use Native\Desktop\Facades\MenuBar;
use Native\Desktop\Menu\Items\MenuItem;
use Native\Desktop\Menu\Menu as ContextMenu;

final readonly class Tray
{
    public const string OPEN_WINDOW = 'lorekeeper.tray.open-window';

    public static function create(): void
    {
        MenuBar::create()
            ->url(url('/up'))
            ->showDockIcon()
            ->tooltip('Lorekeeper')
            ->withContextMenu(self::menu());
    }

    public static function menu(): ContextMenu
    {
        $bots = Account::query()
            ->with('latestRun')
            ->orderBy('name')
            ->get()
            ->map(self::bot(...))
            ->all();

        return Menu::make(...[
            Menu::label('Open Lorekeeper')->event(self::OPEN_WINDOW),
            Menu::separator(),
            ...$bots,
            Menu::separator(),
            Menu::quit('Quit and stop the bots'),
        ]);
    }

    private static function bot(Account $account): MenuItem
    {
        return $account->latestRun?->status->isActive() === true
            ? Menu::label("● {$account->name} · on")->disabled()
            : Menu::label("○ {$account->name} · off")->disabled();
    }
}
