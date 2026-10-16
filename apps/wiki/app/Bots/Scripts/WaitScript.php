<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Bots\Scripts;

use Generator;
use Lorekeeper\Client\Script\Pause;
use Lorekeeper\Client\Script\Script;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Server\Event\Event;
use Override;
use Psl\DateTime\Duration;

/** @implements Script<null> */
final readonly class WaitScript implements Script
{
    public function __construct(
        private Duration $duration,
    ) {}

    /** @return Generator<int, Pause, null|Event|TimedOut, null> */
    #[Override]
    public function run(): Generator
    {
        yield new Pause($this->duration);

        return null;
    }
}
