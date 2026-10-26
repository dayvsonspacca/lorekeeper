<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Player\Hero;

use Lorekeeper\Core\Game\Level\Experience;
use Lorekeeper\Core\Player\Hero\Inventory\HeroWallet;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Player\PlayerSpawnId;
use Lorekeeper\Core\Support\Gauge;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Core\World\Quest\QuestGroupId;

final readonly class HeroData
{
    /**
     * @param Gauge<Experience> $experience
     * @param list<QuestGroupId> $quests
     *
     * @mago-expect lint:excessive-parameter-list
     */
    public function __construct(
        public PlayerSpawnId $spawnId,
        public PlayerName $name,
        public PlayerLevel $level,
        public Gauge $experience,
        public HeroWallet $wallet,
        public bool $member,
        public Quantity $bagSlots,
        public Quantity $bankSlots,
        public array $quests,
    ) {}
}
