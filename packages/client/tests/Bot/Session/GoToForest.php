<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Bot\Session;

use Generator;
use Lorekeeper\Client\Bot\Character;
use Lorekeeper\Client\Bot\Done;
use Lorekeeper\Client\Bot\Script\BotScript;
use Lorekeeper\Client\Bot\Task\Task;
use Lorekeeper\Client\Script\Progress;
use Lorekeeper\Client\Script\Script;
use Lorekeeper\Core\World\Map\MapName;
use Override;

final readonly class GoToForest implements Task
{
    #[Override]
    public function title(): string
    {
        return 'Go to forest';
    }

    #[Override]
    public function script(Character $me): Script
    {
        return new readonly class($me) extends BotScript {
            #[Override]
            protected function play(): Generator
            {
                yield new Progress(0, 1);
                yield from $this->join(new MapName('forest'));
                yield new Progress(1, 1);

                return new Done('Now in forest');
            }
        };
    }
}
