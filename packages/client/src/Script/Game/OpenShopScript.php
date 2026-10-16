<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Script\Game;

use Generator;
use Lorekeeper\Client\Command\LoadShopCommand;
use Lorekeeper\Client\Script\Await;
use Lorekeeper\Client\Script\Script;
use Lorekeeper\Client\Script\Send;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Core\World\Shop\ShopId;
use Lorekeeper\Server\AreaId;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Shop\ShopLoadedEvent;
use Override;
use Psl\DateTime\Duration;

/** @implements Script<ShopLoadedEvent|TimedOut> */
final readonly class OpenShopScript implements Script
{
    public function __construct(
        private AreaId $area,
        private ShopId $shop,
    ) {}

    /**
     * @return Generator<int, Send, null|Event|TimedOut, ShopLoadedEvent|TimedOut>
     *
     * @mago-expect lint:inline-variable-return
     */
    #[Override]
    public function run(): Generator
    {
        /** @var ShopLoadedEvent|TimedOut $answer */
        $answer = yield new Send(
            new LoadShopCommand($this->area, $this->shop),
            new Await([ShopLoadedEvent::class], Duration::seconds(15)),
        );

        return $answer;
    }
}
