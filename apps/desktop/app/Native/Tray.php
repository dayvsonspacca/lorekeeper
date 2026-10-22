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
            ->orderBy('name')
            ->get()
            ->map(static fn(Account $account): string => $account->playing()
                ? "● {$account->name} · on"
                : "○ {$account->name} · off")
            ->values()
            ->all();
    }

    public static function playing(): bool
    {
        return Account::query()->get()->contains(static fn(Account $account): bool => $account->playing());
    }
}
