<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Script\Game;

use Generator;
use Lorekeeper\Client\Script\Pause;
use Lorekeeper\Client\Script\Script;
use Override;
use Psl\DateTime\Duration;

/** @implements Script<never> */
final readonly class IdleScript implements Script
{
    /** @return Generator<int, Pause, null, never> */
    #[Override]
    public function run(): Generator
    {
        while (true) {
            yield new Pause(Duration::minutes(1));
        }
    }
}
