<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Event\Quest;

use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Knowledge\Fact;
use Lorekeeper\Core\Knowledge\Subject;
use Lorekeeper\Core\World\Faction\FactionId;
use Lorekeeper\Core\World\Faction\FactionName;
use Lorekeeper\Core\World\Quest\QuestDescription;
use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Core\World\Quest\QuestName;
use Lorekeeper\Core\World\Quest\TurnInItem;
use Lorekeeper\Protocol\Event\Item\ItemFacts;
use Psl\Type;

/**
 * @psalm-import-type Item from ItemFacts
 *
 * @psalm-type Quest = array{
 *     QuestID: positive-int,
 *     sName: non-empty-string,
 *     sDesc: string,
 *     sEndText?: string,
 *     iLvl: non-negative-int,
 *     iExp: non-negative-int,
 *     iGold: non-negative-int,
 *     iClass: non-negative-int,
 *     iReqCP: non-negative-int,
 *     FactionID: positive-int,
 *     sFaction: string,
 *     iReqRep: non-negative-int,
 *     iRep: non-negative-int,
 *     bUpg: bool,
 *     bQuest?: bool|int,
 *     strPrevious?: string,
 *     turnin?: list<array{ItemID: positive-int, iQty: positive-int, ...}>,
 *     reward?: list<array{ItemID: positive-int, iQty: positive-int, iRate: int|float, ...}>,
 *     reqd?: list<array{ItemID: positive-int, ...}>,
 *     oItems?: array<array-key, Item>,
 *     oRewards?: array{itemsS?: array<array-key, Item>, ...},
 *     ...
 * }
 */
final readonly class QuestFacts
{
    public const int NO_FACTION = 1;

    /** @return Type\TypeInterface<Quest> */
    public static function type(): Type\TypeInterface
    {
        $item = Type\shape(['ItemID' => Type\positive_int()], allowUnknownFields: true);

        return Type\shape([
            'QuestID' => Type\positive_int(),
            'sName' => Type\non_empty_string(),
            'sDesc' => Type\string(),
            'sEndText' => Type\optional(Type\string()),
            'iLvl' => Type\uint(),
            'iExp' => Type\uint(),
            'iGold' => Type\uint(),
            'iClass' => Type\uint(),
            'iReqCP' => Type\uint(),
            'FactionID' => Type\positive_int(),
            'sFaction' => Type\string(),
            'iReqRep' => Type\uint(),
            'iRep' => Type\uint(),
            'bUpg' => Type\bool(),
            'bQuest' => Type\optional(Type\union(Type\bool(), Type\int())),
            'strPrevious' => Type\optional(Type\string()),
            'turnin' => Type\optional(Type\vec(Type\shape([
                'ItemID' => Type\positive_int(),
                'iQty' => Type\positive_int(),
            ], allowUnknownFields: true))),
            'reward' => Type\optional(Type\vec(Type\shape([
                'ItemID' => Type\positive_int(),
                'iQty' => Type\positive_int(),
                'iRate' => Type\num(),
            ], allowUnknownFields: true))),
            'reqd' => Type\optional(Type\vec($item)),
            'oItems' => Type\optional(Type\dict(Type\array_key(), ItemFacts::type())),
            'oRewards' => Type\optional(Type\shape([
                'itemsS' => Type\optional(Type\dict(Type\array_key(), ItemFacts::type())),
            ], allowUnknownFields: true)),
        ], allowUnknownFields: true);
    }

    /**
     * @param Quest $quest
     *
     * @return list<Fact<Subject, object>>
     */
    public static function of(array $quest): array
    {
        $questId = new QuestId($quest['QuestID']);

        return [
            new Fact($questId, new QuestName($quest['sName'])),
            ...self::descriptionFacts($questId, $quest),
            ...self::factionFacts($quest),
            ...self::turnInFacts($questId, $quest),
            ...QuestRequirementFacts::of($questId, $quest),
            ...QuestRewardFacts::of($questId, $quest),
            ...self::itemFacts($quest),
        ];
    }

    /**
     * @param Quest $quest
     *
     * @return list<Fact<QuestId, QuestDescription>>
     */
    private static function descriptionFacts(QuestId $questId, array $quest): array
    {
        if (trim($quest['sDesc']) === '') {
            return [];
        }

        return [new Fact($questId, new QuestDescription($quest['sDesc'], trim($quest['sEndText'] ?? '')))];
    }

    /**
     * @param Quest $quest
     *
     * @return list<Fact<FactionId, FactionName>>
     */
    private static function factionFacts(array $quest): array
    {
        if ($quest['FactionID'] === self::NO_FACTION || $quest['sFaction'] === '') {
            return [];
        }

        return [new Fact(new FactionId($quest['FactionID']), new FactionName($quest['sFaction']))];
    }

    /**
     * @param Quest $quest
     *
     * @return list<Fact<QuestId, TurnInItem>>
     */
    private static function turnInFacts(QuestId $questId, array $quest): array
    {
        return array_map(
            static fn(array $turnIn): Fact => new Fact(
                $questId,
                new TurnInItem(new ItemId($turnIn['ItemID']), $turnIn['iQty']),
            ),
            $quest['turnin'] ?? [],
        );
    }

    /**
     * @param Quest $quest
     *
     * @return list<Fact<ItemId, object>>
     */
    private static function itemFacts(array $quest): array
    {
        $items = [...array_values($quest['oItems'] ?? []), ...array_values($quest['oRewards']['itemsS'] ?? [])];
        $facts = [];
        $seen = [];

        foreach ($items as $item) {
            if (array_key_exists($item['ItemID'], $seen)) {
                continue;
            }

            $seen[$item['ItemID']] = true;
            $facts = [...$facts, ...ItemFacts::of($item)];
        }

        return $facts;
    }
}
