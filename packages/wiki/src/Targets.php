<?php

declare(strict_types=1);

namespace Lorekeeper\Wiki;

use Lorekeeper\Core\Knowledge\Subject;
use Lorekeeper\Core\World\Map\MapLink;
use Lorekeeper\Core\World\Map\MapMonster;
use Lorekeeper\Core\World\Map\MapNpc;
use Lorekeeper\Core\World\Npc\NpcQuest;
use Lorekeeper\Core\World\Npc\NpcShop;
use Lorekeeper\Core\World\Quest\Requirement\FactionRankRequirement;
use Lorekeeper\Core\World\Quest\Requirement\ItemRequirement;
use Lorekeeper\Core\World\Quest\Requirement\PreviousQuestRequirement;
use Lorekeeper\Core\World\Quest\Reward\ItemReward;
use Lorekeeper\Core\World\Quest\Reward\ReputationReward;
use Lorekeeper\Core\World\Quest\TurnInItem;
use Lorekeeper\Core\World\Region\RegionMap;
use Lorekeeper\Core\World\Shop\ShopItem;

final readonly class Targets
{
    public const array PROPERTIES = [
        MapMonster::class => 'monsterId',
        MapLink::class => 'mapName',
        MapNpc::class => 'npcId',
        NpcQuest::class => 'questId',
        NpcShop::class => 'shopId',
        ShopItem::class => 'itemId',
        RegionMap::class => 'mapName',
        TurnInItem::class => 'itemId',
        ItemReward::class => 'itemId',
        ItemRequirement::class => 'itemId',
        PreviousQuestRequirement::class => 'questId',
        ReputationReward::class => 'factionId',
        FactionRankRequirement::class => 'factionId',
    ];

    public static function of(object $value): ?Subject
    {
        $property = self::PROPERTIES[$value::class] ?? null;
        if ($property === null) {
            return null;
        }

        return self::subject(get_object_vars($value)[$property] ?? null);
    }

    private static function subject(mixed $candidate): ?Subject
    {
        return $candidate instanceof Subject ? $candidate : null;
    }
}
