<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Bot\Script;

use Generator;
use Lorekeeper\Client\Bot\Done;
use Lorekeeper\Client\Bot\Position;
use Lorekeeper\Client\Script\Game\OpenShopScript;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Pause;
use Lorekeeper\Client\Script\Progress;
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
        $going = new GoToScript($this->player, $this->here, $this->map)->run();
        yield from $going;
        $there = $going->getReturn();
        if (!$there instanceof Position) {
            return $there;
        }

        $total = count($this->shops);
        yield new Progress(0, $total);

        $opened = 0;
        foreach ($this->shops as $index => $shop) {
            if ($index > 0) {
                yield new Pause(Duration::seconds(2));
            }

            $opening = new OpenShopScript($there->area, $shop)->run();
            yield from $opening;
            $answer = $opening->getReturn();
            $opened += $answer instanceof ShopLoadedEvent ? 1 : 0;

            yield new Progress($index + 1, $total);
        }

        return new Done("Opened {$opened} of {$total} shops");
    }
}
