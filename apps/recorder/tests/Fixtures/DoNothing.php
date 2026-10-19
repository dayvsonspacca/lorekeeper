<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Recorder\Tests\Fixtures;

use Generator;
use Lorekeeper\Client\Character;
use Lorekeeper\Client\Script\Done;
use Lorekeeper\Client\Script\Script;
use Override;

/** @implements Script<Done> */
final readonly class DoNothing implements Script
{
    #[Override]
    public function title(): string
    {
        return 'Nothing to do';
    }

    #[Override]
    public function run(Character $me): Generator
    {
        yield from [];

        return new Done('nothing done');
    }
}

return new DoNothing();
