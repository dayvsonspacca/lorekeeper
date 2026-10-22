<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Steps\Pauses;

use Generator;
use Lorekeeper\Bot\Steps\Action;
use Lorekeeper\Bot\Steps\StepScript;
use Override;

final readonly class Rest implements Action
{
    #[Override]
    public function describe(): string
    {
        return 'Rest';
    }

    #[Override]
    public function play(StepScript $bot): Generator
    {
        yield from $bot->rest();

        return null;
    }
}
