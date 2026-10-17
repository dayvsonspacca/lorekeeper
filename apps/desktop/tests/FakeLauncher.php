<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests;

use Lorekeeper\Apps\Desktop\Bots\Launcher\Launcher;
use Lorekeeper\Apps\Desktop\Models\BotRun;
use Override;

final class FakeLauncher implements Launcher
{
    /** @var list<int> */
    public array $launched = [];

    /** @var array<int, true> */
    public array $alive = [];

    #[Override]
    public function launch(BotRun $run): void
    {
        $this->launched[] = $run->id;
        $this->alive[$run->id] = true;
    }

    #[Override]
    public function alive(BotRun $run): bool
    {
        return array_key_exists($run->id, $this->alive);
    }
}
