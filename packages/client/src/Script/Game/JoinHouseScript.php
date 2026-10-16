<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Script\Game;

use Generator;
use Lorekeeper\Client\Command\JoinHouseCommand;
use Lorekeeper\Client\Script\Await;
use Lorekeeper\Client\Script\Script;
use Lorekeeper\Client\Script\Send;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Server\Event\Map\MovedToMapEvent;
use Override;
use Psl\DateTime\Duration;

/** @implements Script<MovedToMapEvent|TimedOut> */
final readonly class JoinHouseScript implements Script
{
    public function __construct(
        private PlayerName $owner,
    ) {}

    /** @return Generator<int, Send, MovedToMapEvent|TimedOut, MovedToMapEvent|TimedOut> */
    #[Override]
    public function run(): Generator
    {
        return yield new Send(
            new JoinHouseCommand($this->owner),
            new Await([MovedToMapEvent::class], Duration::seconds(15)),
        );
    }
}
