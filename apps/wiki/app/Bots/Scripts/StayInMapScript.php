<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Bots\Scripts;

use Generator;
use Lorekeeper\Client\Login\GameLogin;
use Lorekeeper\Client\Script\Game\EnterGameScript;
use Lorekeeper\Client\Script\Game\IdleScript;
use Lorekeeper\Client\Script\Game\InGame;
use Lorekeeper\Client\Script\Game\JoinMapScript;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Script;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\LoginFailedEvent;
use Lorekeeper\Server\Event\Map\AlreadyInMapEvent;
use Lorekeeper\Server\Event\Map\MapLockedEvent;
use Lorekeeper\Server\Event\Map\MapMemberOnlyEvent;
use Lorekeeper\Server\Event\Map\MapNotAvailableEvent;
use Lorekeeper\Server\Event\Map\MapRequiresQuestEvent;
use Lorekeeper\Server\Event\Map\MovedToMapEvent;
use Override;
use Psl\Option;

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
        $entered = yield from new EnterGameScript($this->login)->run();
        if (!$entered instanceof InGame) {
            return $entered;
        }

        if ($entered->map->map->value !== $this->map->value) {
            $joined = yield from new JoinMapScript($this->login->player, $this->map, Option\none())->run();
            if (!$joined instanceof MovedToMapEvent && !$joined instanceof AlreadyInMapEvent) {
                return $joined;
            }
        }

        yield from new IdleScript()->run();
    }
}
