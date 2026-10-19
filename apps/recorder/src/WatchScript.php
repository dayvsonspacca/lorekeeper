<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Recorder;

use Generator;
use Lorekeeper\Client\Character;
use Lorekeeper\Client\Script\Done;
use Lorekeeper\Client\Script\Pause;
use Lorekeeper\Client\Script\Script;
use Override;
use Psl\DateTime\Duration;

/** @implements Script<Done> */
final readonly class WatchScript implements Script
{
    public function __construct(
        private Duration $length,
    ) {}

    #[Override]
    public function title(): string
    {
        return 'Watch for ' . (int) $this->length->getTotalSeconds() . 's';
    }

    #[Override]
    public function run(Character $me): Generator
    {
        yield new Pause($this->length);

        return new Done(
            "Watched {$me->location()->map->name->value} for " . (int) $this->length->getTotalSeconds() . 's',
        );
    }
}
