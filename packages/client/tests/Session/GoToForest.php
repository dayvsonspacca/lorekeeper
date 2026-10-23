<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Session;

use Generator;
use Lorekeeper\Client\Character;
use Lorekeeper\Client\Command\Map\JoinMapCommand;
use Lorekeeper\Client\Script\Done;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Progress;
use Lorekeeper\Client\Script\Script;
use Lorekeeper\Client\Script\Send;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Map\MapJoins;
use Lorekeeper\Server\Event\Map\MapRefusals;
use Override;
use Psl\DateTime\Duration;
use Psl\Option;

/** @implements Script<Done> */
final readonly class GoToForest implements Script
{
    #[Override]
    public function title(): string
    {
        return 'Go to forest';
    }

    /** @return Generator<int, Instruction, null|Event|TimedOut, Done> */
    #[Override]
    public function run(Character $me): Generator
    {
        yield new Progress(0, 1);

        if ($me->location()->map->name->value !== 'forest') {
            yield new Send(new JoinMapCommand($me->hero()->name, new MapName('forest'), Option\none()))->awaits(
                Duration::seconds(15),
                MapJoins::class,
                MapRefusals::class,
            );
        }

        yield new Progress(1, 1);

        return new Done('Now in forest');
    }
}
