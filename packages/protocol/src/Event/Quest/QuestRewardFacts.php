<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Event\Quest;

use Lorekeeper\Core\Currency\Gold;
use Lorekeeper\Core\Item\DropRate;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Knowledge\Fact;
use Lorekeeper\Core\Progression\Experience;
use Lorekeeper\Core\World\Faction\FactionId;
use Lorekeeper\Core\World\Faction\Reputation;
use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Core\World\Quest\Reward\ExperienceReward;
use Lorekeeper\Core\World\Quest\Reward\GoldReward;
use Lorekeeper\Core\World\Quest\Reward\ItemReward;
use Lorekeeper\Core\World\Quest\Reward\QuestReward;
use Lorekeeper\Core\World\Quest\Reward\ReputationReward;

/** @psalm-import-type Quest from QuestFacts */
final readonly class QuestRewardFacts
{
    /**
     * @param Quest $quest
     *
     * @return list<Fact<QuestId, QuestReward>>
     */
    public static function of(QuestId $questId, array $quest): array
    {
        $rewards = [];

        if ($quest['iGold'] > 0) {
            $rewards[] = new GoldReward(new Gold($quest['iGold']));
        }

        if ($quest['iExp'] > 0) {
            $rewards[] = new ExperienceReward(new Experience($quest['iExp']));
        }

        if ($quest['iRep'] > 0 && $quest['FactionID'] !== QuestFacts::NO_FACTION) {
            $rewards[] = new ReputationReward(new FactionId($quest['FactionID']), new Reputation($quest['iRep']));
        }

        foreach ($quest['reward'] ?? [] as $item) {
            $rewards[] = new ItemReward(
                new ItemId($item['ItemID']),
                new DropRate((float) $item['iRate']),
                $item['iQty'],
            );
        }

        return array_map(static fn(QuestReward $reward): Fact => new Fact($questId, $reward), $rewards);
    }
}
