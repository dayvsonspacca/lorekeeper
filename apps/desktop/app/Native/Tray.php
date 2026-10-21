<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Native;

use Lorekeeper\Apps\Desktop\Models\Account;

final readonly class Tray
{
    /** @return list<string> */
    public static function bots(): array
    {
        return Account::query()
            ->with('latestRun')
            ->orderBy('name')
            ->get()
            ->map(static fn(Account $account): string => $account->latestRun?->status->isActive() === true
                ? "● {$account->name} · on"
                : "○ {$account->name} · off")
            ->values()
            ->all();
    }
}
