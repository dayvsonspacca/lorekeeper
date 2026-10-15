<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Event\Quest;

use Lorekeeper\Core\CharacterClass\ClassId;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Knowledge\Fact;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\Progression\ClassPoints;
use Lorekeeper\Core\Progression\Rank;
use Lorekeeper\Core\World\Faction\FactionId;
use Lorekeeper\Core\World\Faction\Reputation;
use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Core\World\Quest\Requirement\ClassRankRequirement;
use Lorekeeper\Core\World\Quest\Requirement\FactionRankRequirement;
use Lorekeeper\Core\World\Quest\Requirement\ItemRequirement;
use Lorekeeper\Core\World\Quest\Requirement\MemberRequirement;
use Lorekeeper\Core\World\Quest\Requirement\PlayerLevelRequirement;
use Lorekeeper\Core\World\Quest\Requirement\PreviousQuestRequirement;
use Lorekeeper\Core\World\Quest\Requirement\QuestRequirement;

/** @psalm-import-type Quest from QuestFacts */
final readonly class QuestRequirementFacts
{
    /**
     * @param Quest $quest
     *
     * @return list<Fact<QuestId, QuestRequirement>>
     */
    public static function of(QuestId $questId, array $quest): array
    {
        return array_map(static fn(QuestRequirement $requirement): Fact => new Fact($questId, $requirement), [
            ...self::level($quest),
            ...self::classRank($quest),
            ...self::factionRank($quest),
            ...self::member($quest),
            ...self::previousQuest($quest),
            ...self::items($quest),
        ]);
    }

    /**
     * @param Quest $quest
     *
     * @return list<PlayerLevelRequirement>
     */
    private static function level(array $quest): array
    {
        return $quest['iLvl'] > 1 ? [new PlayerLevelRequirement(new PlayerLevel($quest['iLvl']))] : [];
    }

    /**
     * @param Quest $quest
     *
     * @return list<ClassRankRequirement>
     */
    private static function classRank(array $quest): array
    {
        if ($quest['iClass'] === 0) {
            return [];
        }

        return [new ClassRankRequirement(
            new ClassId($quest['iClass']),
            Rank::fromPoints(new ClassPoints($quest['iReqCP'])),
        )];
    }

    /**
     * @param Quest $quest
     *
     * @return list<FactionRankRequirement>
     */
    private static function factionRank(array $quest): array
    {
        if ($quest['iReqRep'] === 0 || $quest['FactionID'] === QuestFacts::NO_FACTION) {
            return [];
        }

        return [new FactionRankRequirement(
            new FactionId($quest['FactionID']),
            Rank::fromPoints(new Reputation($quest['iReqRep'])),
        )];
    }

    /**
     * @param Quest $quest
     *
     * @return list<MemberRequirement>
     */
    private static function member(array $quest): array
    {
        return $quest['bUpg'] ? [new MemberRequirement()] : [];
    }

    /**
     * @param Quest $quest
     *
     * @return list<PreviousQuestRequirement>
     */
    private static function previousQuest(array $quest): array
    {
        $previous = $quest['strPrevious'] ?? '';
        if (!($quest['bQuest'] ?? false) || !ctype_digit($previous) || (int) $previous < 1) {
            return [];
        }

        return [new PreviousQuestRequirement(new QuestId((int) $previous))];
    }

    /**
     * @param Quest $quest
     *
     * @return list<ItemRequirement>
     */
    private static function items(array $quest): array
    {
        return array_map(
            static fn(array $item): ItemRequirement => new ItemRequirement(new ItemId($item['ItemID'])),
            $quest['reqd'] ?? [],
        );
    }
}
