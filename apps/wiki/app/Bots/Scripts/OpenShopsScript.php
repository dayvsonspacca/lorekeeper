<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Bots\Scripts;

use Generator;
use Lorekeeper\Apps\Wiki\Bots\Position;
use Lorekeeper\Client\Script\Game\OpenShopScript;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Pause;
use Lorekeeper\Client\Script\Script;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Shop\ShopId;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Map\MapLockedEvent;
use Lorekeeper\Server\Event\Map\MapMemberOnlyEvent;
use Lorekeeper\Server\Event\Map\MapNotAvailableEvent;
use Lorekeeper\Server\Event\Map\MapRequiresQuestEvent;
use Lorekeeper\Server\Event\Shop\ShopLoadedEvent;
use Override;
use Psl\DateTime\Duration;

/** @implements Script<Done|TimedOut|MapLockedEvent|MapMemberOnlyEvent|MapNotAvailableEvent|MapRequiresQuestEvent> */
final readonly class OpenShopsScript implements Script
{
    /** @param non-empty-list<ShopId> $shops */
    public function __construct(
        private PlayerName $player,
        private Position $here,
        private MapName $map,
        private array $shops,
    ) {}

    /** @return Generator<int, Instruction, null|Event|TimedOut, Done|TimedOut|MapLockedEvent|MapMemberOnlyEvent|MapNotAvailableEvent|MapRequiresQuestEvent> */
    #[Override]
    public function run(): Generator
    {
        $there = yield from new GoToScript($this->player, $this->here, $this->map)->run();
        if (!$there instanceof Position) {
            return $there;
        }

        $opened = 0;
        foreach ($this->shops as $index => $shop) {
            if ($index > 0) {
                yield new Pause(Duration::seconds(2));
            }

            $answer = yield from new OpenShopScript($there->area, $shop)->run();
            $opened += $answer instanceof ShopLoadedEvent ? 1 : 0;
        }

        return new Done("Opened {$opened} of " . count($this->shops) . ' shops');
    }
}
