<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Steps\Pauses;

use Generator;
use Lorekeeper\Bot\Steps\Action;
use Lorekeeper\Bot\Steps\StepScript;
use Lorekeeper\Client\Script\Pause;
use Override;
use Psl\DateTime\Duration;

final readonly class Wait implements Action
{
    public function __construct(
        public Duration $time,
    ) {}

    #[Override]
    public function describe(): string
    {
        return 'Wait ' . (int) $this->time->getTotalSeconds() . 's';
    }

    #[Override]
    public function play(StepScript $bot): Generator
    {
        yield new Pause($this->time);

        return null;
    }
}
