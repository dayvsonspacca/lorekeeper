<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Bots\Scripts;

use Generator;
use Lorekeeper\Client\Login\GameLogin;
use Lorekeeper\Client\Script\Game\IdleScript;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Script;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Server\AreaId;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\LoginFailedEvent;
use Lorekeeper\Server\Event\Map\MapLockedEvent;
use Lorekeeper\Server\Event\Map\MapMemberOnlyEvent;
use Lorekeeper\Server\Event\Map\MapNotAvailableEvent;
use Lorekeeper\Server\Event\Map\MapRequiresQuestEvent;
use Override;

/** @implements Script<LoginFailedEvent|TimedOut|MapLockedEvent|MapMemberOnlyEvent|MapNotAvailableEvent|MapRequiresQuestEvent> */
final readonly class StayInMapScript implements Script
{
    public function __construct(
        private GameLogin $login,
        private MapName $map,
    ) {}

    /** @return Generator<int, Instruction, null|Event|TimedOut, LoginFailedEvent|TimedOut|MapLockedEvent|MapMemberOnlyEvent|MapNotAvailableEvent|MapRequiresQuestEvent> */
    #[Override]
    public function run(): Generator
    {
        $area = yield from new EnterMapScript($this->login, $this->map)->run();
        if (!$area instanceof AreaId) {
            return $area;
        }

        yield from new IdleScript()->run();
    }
}
