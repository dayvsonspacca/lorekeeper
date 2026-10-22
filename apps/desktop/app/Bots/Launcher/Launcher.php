<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Bots\Launcher;

use Lorekeeper\Apps\Desktop\Models\Account;

interface Launcher
{
    public function launch(Account $account): void;

    public function alive(Account $account): bool;
}
