<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Session;

use Generator;
use Lorekeeper\Client\Bot\BotScript;
use Lorekeeper\Client\Script\Done;
use Lorekeeper\Client\Script\Progress;
use Lorekeeper\Core\World\Map\MapName;
use Override;

final class GoToForest extends BotScript
{
    #[Override]
    public function title(): string
    {
        return 'Go to forest';
    }

    #[Override]
    protected function rules(): Generator
    {
        yield new Progress(0, 1);
        yield from $this->join(new MapName('forest'));
        yield new Progress(1, 1);

        return new Done('Now in forest');
    }
}
