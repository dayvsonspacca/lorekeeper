<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Bots\Scripts;

use Generator;
use Lorekeeper\Client\Login\GameLogin;
use Lorekeeper\Client\Script\Game\OpenShopScript;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Pause;
use Lorekeeper\Client\Script\Script;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Shop\ShopId;
use Lorekeeper\Server\AreaId;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\LoginFailedEvent;
use Lorekeeper\Server\Event\Map\MapLockedEvent;
use Lorekeeper\Server\Event\Map\MapMemberOnlyEvent;
use Lorekeeper\Server\Event\Map\MapNotAvailableEvent;
use Lorekeeper\Server\Event\Map\MapRequiresQuestEvent;
use Lorekeeper\Server\Event\Shop\ShopLoadedEvent;
use Override;
use Psl\DateTime\Duration;

/** @implements Script<Done|LoginFailedEvent|TimedOut|MapLockedEvent|MapMemberOnlyEvent|MapNotAvailableEvent|MapRequiresQuestEvent> */
final readonly class OpenShopsScript implements Script
{
    /** @param non-empty-list<ShopId> $shops */
    public function __construct(
        private GameLogin $login,
        private MapName $map,
        private array $shops,
    ) {}

    /** @return Generator<int, Instruction, null|Event|TimedOut, Done|LoginFailedEvent|TimedOut|MapLockedEvent|MapMemberOnlyEvent|MapNotAvailableEvent|MapRequiresQuestEvent> */
    #[Override]
    public function run(): Generator
    {
        $area = yield from new EnterMapScript($this->login, $this->map)->run();
        if (!$area instanceof AreaId) {
            return $area;
        }

        $opened = 0;
        foreach ($this->shops as $index => $shop) {
            if ($index > 0) {
                yield new Pause(Duration::seconds(2));
            }

            $answer = yield from new OpenShopScript($area, $shop)->run();
            $opened += $answer instanceof ShopLoadedEvent ? 1 : 0;
        }

        return new Done("Opened {$opened} of " . count($this->shops) . ' shops');
    }
}
