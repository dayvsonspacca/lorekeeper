<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Quest;

use Lorekeeper\Core\CharacterClass\ClassId;
use Lorekeeper\Core\Currency\Gold;
use Lorekeeper\Core\Item\Item;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\ItemTag;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\Progression\ClassPoints;
use Lorekeeper\Core\Progression\Experience;
use Lorekeeper\Core\Progression\Rank;
use Lorekeeper\Core\Progression\Reputation;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Core\World\Faction\FactionId;
use Lorekeeper\Core\World\Quest\Objective\ItemObjective;
use Lorekeeper\Core\World\Quest\Objective\QuestObjective;
use Lorekeeper\Core\World\Quest\Quest;
use Lorekeeper\Core\World\Quest\QuestDescription;
use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Core\World\Quest\QuestName;
use Lorekeeper\Core\World\Quest\Requirement\ClassRankRequirement;
use Lorekeeper\Core\World\Quest\Requirement\FactionRankRequirement;
use Lorekeeper\Core\World\Quest\Requirement\ItemRequirement;
use Lorekeeper\Core\World\Quest\Requirement\MemberRequirement;
use Lorekeeper\Core\World\Quest\Requirement\PlayerLevelRequirement;
use Lorekeeper\Core\World\Quest\Requirement\PreviousQuestRequirement;
use Lorekeeper\Core\World\Quest\Requirement\QuestRequirement;
use Lorekeeper\Core\World\Quest\Reward\DropRate;
use Lorekeeper\Core\World\Quest\Reward\ExperienceReward;
use Lorekeeper\Core\World\Quest\Reward\GoldReward;
use Lorekeeper\Core\World\Quest\Reward\ItemReward;
use Lorekeeper\Core\World\Quest\Reward\ItemRewardType;
use Lorekeeper\Core\World\Quest\Reward\QuestReward;
use Lorekeeper\Core\World\Quest\Reward\ReputationReward;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Item\Helper\ItemKinds;
use Lorekeeper\Server\Event\NamedAfterClass;
use Lorekeeper\Server\Event\Quest\Helper\QuestMarks;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use Lorekeeper\Server\Message\Message;
use Override;
use Psl\Option;
use Psl\Type as Coerce;
use Psl\Type\Exception\CoercionException;

/**
 * @psalm-type ItemData = array{ItemID: positive-int, sName: non-empty-string, sType: string, bUpg?: bool, ...}
 * @psalm-type QuestData = array{QuestID: positive-int, sName: non-empty-string, sDesc: string, sEndText?: string, iLvl: non-negative-int, iExp: non-negative-int, iGold: non-negative-int, iClass: non-negative-int, iReqCP: non-negative-int, FactionID: positive-int, iReqRep: non-negative-int, iRep: non-negative-int, bUpg: bool, bQuest?: bool|int, strPrevious?: string, sField?: string, iIndex?: int, turnin?: list<array{ItemID: positive-int, iQty: positive-int, ...}>, reward?: list<array{ItemID: positive-int, iQty: positive-int, iRate: float|int, iType: int, ...}>, reqd?: list<array{ItemID: positive-int, iQty: positive-int, ...}>, oItems?: array<array-key, ItemData>, oReqd?: array<array-key, ItemData>, oRewards?: array{itemsS?: array<array-key, ItemData>, itemsR?: array<array-key, ItemData>, ...}, ...}
 *
 * @mago-expect lint:cyclomatic-complexity
 * @mago-expect lint:kan-defect
 */
final readonly class QuestLoadedEvent implements Event
{
    use NamedAfterClass;

    private const int NO_FACTION = 1;

    /** @param list<Quest> $quests */
    public function __construct(
        public array $quests,
    ) {}

    /** @return Option\Option<static> */
    #[Override]
    public static function from(Message $message): Option\Option
    {
        if (!$message instanceof JsonMessage || ($message->data['cmd'] ?? null) !== 'getQuests') {
            return Option\none();
        }

        try {
            $quests = array_values(Coerce\dict(Coerce\array_key(), self::questType())->coerce(
                $message->data['quests'] ?? null,
            ));
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'quests are missing or invalid', $exception);
        }

        return Option\some(new static(array_map(self::quest(...), $quests)));
    }

    /** @return Coerce\TypeInterface<QuestData> */
    private static function questType(): Coerce\TypeInterface
    {
        $items = Coerce\optional(Coerce\dict(Coerce\array_key(), Coerce\shape([
            'ItemID' => Coerce\positive_int(),
            'sName' => Coerce\non_empty_string(),
            'sType' => Coerce\string(),
            'bUpg' => Coerce\optional(Coerce\bool()),
        ], allowUnknownFields: true)));
        $entry = Coerce\shape([
            'ItemID' => Coerce\positive_int(),
            'iQty' => Coerce\positive_int(),
        ], allowUnknownFields: true);

        return Coerce\shape([
            'QuestID' => Coerce\positive_int(),
            'sName' => Coerce\non_empty_string(),
            'sDesc' => Coerce\string(),
            'sEndText' => Coerce\optional(Coerce\string()),
            'iLvl' => Coerce\uint(),
            'iExp' => Coerce\uint(),
            'iGold' => Coerce\uint(),
            'iClass' => Coerce\uint(),
            'iReqCP' => Coerce\uint(),
            'FactionID' => Coerce\positive_int(),
            'iReqRep' => Coerce\uint(),
            'iRep' => Coerce\uint(),
            'bUpg' => Coerce\bool(),
            'bQuest' => Coerce\optional(Coerce\union(Coerce\bool(), Coerce\int())),
            'strPrevious' => Coerce\optional(Coerce\string()),
            'sField' => Coerce\optional(Coerce\string()),
            'iIndex' => Coerce\optional(Coerce\int()),
            'turnin' => Coerce\optional(Coerce\vec($entry)),
            'reward' => Coerce\optional(Coerce\vec(Coerce\shape([
                'ItemID' => Coerce\positive_int(),
                'iQty' => Coerce\positive_int(),
                'iRate' => Coerce\num(),
                'iType' => Coerce\int(),
            ], allowUnknownFields: true))),
            'reqd' => Coerce\optional(Coerce\vec($entry)),
            'oItems' => $items,
            'oReqd' => $items,
            'oRewards' => Coerce\optional(Coerce\shape([
                'itemsS' => $items,
                'itemsR' => $items,
            ], allowUnknownFields: true)),
        ], allowUnknownFields: true);
    }

    /**
     * @param QuestData $quest
     *
     * @throws MalformedEventException
     */
    private static function quest(array $quest): Quest
    {
        $description = trim($quest['sDesc']);
        $items = self::items($quest);

        return new Quest(
            new QuestId($quest['QuestID']),
            new QuestName($quest['sName']),
            $description === ''
                ? Option\none()
                : Option\some(new QuestDescription($quest['sDesc'], trim($quest['sEndText'] ?? ''))),
            self::requirements($quest, $items),
            array_map(
                static fn(array $turnIn): QuestObjective => new ItemObjective(
                    self::described($items, $turnIn['ItemID']),
                    new Quantity($turnIn['iQty']),
                ),
                $quest['turnin'] ?? [],
            ),
            self::rewards($quest, $items),
            QuestMarks::of($quest['sField'] ?? '', $quest['iIndex'] ?? -1),
        );
    }

    /**
     * @param QuestData $quest
     * @param array<int, Item> $items
     *
     * @throws MalformedEventException
     *
     * @return list<QuestRequirement>
     */
    private static function requirements(array $quest, array $items): array
    {
        $requirements = [];

        if ($quest['iLvl'] > 1) {
            $requirements[] = new PlayerLevelRequirement(new PlayerLevel($quest['iLvl']));
        }

        if ($quest['iClass'] > 0) {
            $requirements[] = new ClassRankRequirement(
                new ClassId($quest['iClass']),
                Rank::fromPoints(new ClassPoints($quest['iReqCP'])),
            );
        }

        if ($quest['iReqRep'] > 0 && $quest['FactionID'] !== self::NO_FACTION) {
            $requirements[] = new FactionRankRequirement(
                new FactionId($quest['FactionID']),
                Rank::fromPoints(new Reputation($quest['iReqRep'])),
            );
        }

        if ($quest['bUpg']) {
            $requirements[] = new MemberRequirement();
        }

        $previous = $quest['strPrevious'] ?? '';
        if (($quest['bQuest'] ?? false) && ctype_digit($previous) && (int) $previous > 0) {
            $requirements[] = new PreviousQuestRequirement(new QuestId((int) $previous));
        }

        foreach ($quest['reqd'] ?? [] as $item) {
            $requirements[] = new ItemRequirement(
                self::described($items, $item['ItemID']),
                new Quantity($item['iQty']),
            );
        }

        return $requirements;
    }

    /**
     * @param QuestData $quest
     * @param array<int, Item> $items
     *
     * @throws MalformedEventException
     *
     * @return list<QuestReward>
     */
    private static function rewards(array $quest, array $items): array
    {
        $rewards = [];

        if ($quest['iGold'] > 0) {
            $rewards[] = new GoldReward(new Gold($quest['iGold']));
        }

        if ($quest['iExp'] > 0) {
            $rewards[] = new ExperienceReward(new Experience($quest['iExp']));
        }

        if ($quest['iRep'] > 0 && $quest['FactionID'] !== self::NO_FACTION) {
            $rewards[] = new ReputationReward(new FactionId($quest['FactionID']), new Reputation($quest['iRep']));
        }

        foreach ($quest['reward'] ?? [] as $item) {
            $rewards[] = new ItemReward(
                self::described($items, $item['ItemID']),
                new DropRate((float) $item['iRate']),
                new Quantity($item['iQty']),
                self::rewardType($item['iType']),
            );
        }

        return $rewards;
    }

    /**
     * @param QuestData $quest
     *
     * @throws MalformedEventException
     *
     * @return array<int, Item>
     */
    private static function items(array $quest): array
    {
        $rewards = $quest['oRewards'] ?? [];
        $items = [];
        foreach ([
            ...array_values($quest['oItems'] ?? []),
            ...array_values($quest['oReqd'] ?? []),
            ...array_values($rewards['itemsS'] ?? []),
            ...array_values($rewards['itemsR'] ?? []),
        ] as $described) {
            if (array_key_exists($described['ItemID'], $items)) {
                continue;
            }

            $item = ItemKinds::read(
                new ItemId($described['ItemID']),
                new ItemName($described['sName']),
                $described['sType'],
                static::class,
            );
            if ($described['bUpg'] ?? false) {
                $item->tags->add(ItemTag::MemberOnly);
            }

            $items[$described['ItemID']] = $item;
        }

        return $items;
    }

    /**
     * @param array<int, Item> $items
     *
     * @throws MalformedEventException
     */
    private static function described(array $items, int $id): Item
    {
        return $items[$id] ?? throw new MalformedEventException(static::class, "item {$id} is not described");
    }

    /** @throws MalformedEventException */
    private static function rewardType(int $type): ItemRewardType
    {
        return match ($type) {
            0 => ItemRewardType::Guaranteed,
            1 => ItemRewardType::Random,
            default => throw new MalformedEventException(static::class, "unknown reward type '{$type}'"),
        };
    }

    #[Override]
    public function summary(): string
    {
        return (
            count($this->quests)
            . ' quests: '
            . implode(', ', array_map(static fn(Quest $quest): string => $quest->name->value, $this->quests))
        );
    }
}
