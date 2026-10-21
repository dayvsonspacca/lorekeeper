<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests;

use Lorekeeper\Apps\Desktop\Bots\Launcher\Launcher;
use Lorekeeper\Apps\Desktop\Models\Account;
use Override;

final class FakeLauncher implements Launcher
{
    /** @var list<int> */
    public array $launched = [];

    /** @var array<int, true> */
    public array $alive = [];

    #[Override]
    public function launch(Account $account): void
    {
        $this->launched[] = $account->id;
        $this->alive[$account->id] = true;
    }

    #[Override]
    public function alive(Account $account): bool
    {
        return array_key_exists($account->id, $this->alive);
    }
}
