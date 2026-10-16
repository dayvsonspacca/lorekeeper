<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Quest;

use Lorekeeper\Core\Asset\Gamefile;
use Lorekeeper\Core\Asset\Linkage;
use Lorekeeper\Core\CharacterClass\ClassId;
use Lorekeeper\Core\CharacterClass\ClassName;
use Lorekeeper\Core\Currency\AdventureCoins;
use Lorekeeper\Core\Currency\Gold;
use Lorekeeper\Core\Item\EquipmentSlot;
use Lorekeeper\Core\Item\ItemDescription;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\ItemPrice;
use Lorekeeper\Core\Item\Type;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\Progression\ClassPoints;
use Lorekeeper\Core\Progression\Experience;
use Lorekeeper\Core\Progression\Rank;
use Lorekeeper\Core\Progression\Reputation;
use Lorekeeper\Core\World\Faction\FactionId;
use Lorekeeper\Core\World\Faction\FactionName;
use Lorekeeper\Core\World\Quest\Objective\ItemObjective;
use Lorekeeper\Core\World\Quest\Objective\QuestObjective;
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
use Lorekeeper\Server\Event\Mention\Mention;
use Lorekeeper\Server\Event\Mention\Mentions;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use Lorekeeper\Server\Message\Message;
use Override;
use Psl\Option;
use Psl\Type as Coerce;
use Psl\Type\Exception\CoercionException;

/**
 * @psalm-type Item = array{ItemID: positive-int, sName: non-empty-string, sType: string, sES: string, sDesc?: string, iCost?: non-negative-int, bCoins?: bool, sFile?: null|string, sLink?: string, bUpg?: bool, ...}
 * @psalm-type Quest = array{QuestID: positive-int, sName: non-empty-string, sDesc: string, sEndText?: string, iLvl: non-negative-int, iExp: non-negative-int, iGold: non-negative-int, iClass: non-negative-int, sClass?: string, iReqCP: non-negative-int, FactionID: positive-int, sFaction: string, iReqRep: non-negative-int, iRep: non-negative-int, bUpg: bool, bQuest?: bool|int, strPrevious?: string, turnin?: list<array{ItemID: positive-int, iQty: positive-int, ...}>, reward?: list<array{ItemID: positive-int, iQty: positive-int, iRate: float|int, iType: int, ...}>, reqd?: list<array{ItemID: positive-int, iQty: positive-int, ...}>, oItems?: array<array-key, Item>, oReqd?: array<array-key, Item>, oRewards?: array{itemsS?: array<array-key, Item>, itemsR?: array<array-key, Item>, ...}, ...}
 *
 * @mago-expect lint:cyclomatic-complexity
 * @mago-expect lint:kan-defect
 * @mago-expect lint:too-many-methods
 */
final readonly class QuestLoadedEvent implements Event, Mentions
{
    private const int NO_FACTION = 1;

    /**
     * @param list<QuestLoadedQuest> $quests
     * @param list<Mention> $mentions
     */
    public function __construct(
        public array $quests,
        private array $mentions,
    ) {}

    /** @return list<Mention> */
    #[Override]
    public function mentions(): array
    {
        return $this->mentions;
    }

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

        return Option\some(new static(array_map(self::quest(...), $quests), self::mentionsOf($quests)));
    }

    /** @return Coerce\TypeInterface<Quest> */
    private static function questType(): Coerce\TypeInterface
    {
        $items = Coerce\optional(Coerce\dict(Coerce\array_key(), Coerce\shape([
            'ItemID' => Coerce\positive_int(),
            'sName' => Coerce\non_empty_string(),
            'sType' => Coerce\string(),
            'sES' => Coerce\string(),
            'sDesc' => Coerce\optional(Coerce\string()),
            'iCost' => Coerce\optional(Coerce\uint()),
            'bCoins' => Coerce\optional(Coerce\bool()),
            'sFile' => Coerce\optional(Coerce\nullable(Coerce\string())),
            'sLink' => Coerce\optional(Coerce\string()),
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
            'sClass' => Coerce\optional(Coerce\string()),
            'iReqCP' => Coerce\uint(),
            'FactionID' => Coerce\positive_int(),
            'sFaction' => Coerce\string(),
            'iReqRep' => Coerce\uint(),
            'iRep' => Coerce\uint(),
            'bUpg' => Coerce\bool(),
            'bQuest' => Coerce\optional(Coerce\union(Coerce\bool(), Coerce\int())),
            'strPrevious' => Coerce\optional(Coerce\string()),
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

    /** @param Quest $quest */
    private static function quest(array $quest): QuestLoadedQuest
    {
        $description = trim($quest['sDesc']);

        return new QuestLoadedQuest(
            new QuestId($quest['QuestID']),
            new QuestName($quest['sName']),
            $description === ''
                ? Option\none()
                : Option\some(new QuestDescription($quest['sDesc'], trim($quest['sEndText'] ?? ''))),
            self::requirements($quest),
            array_map(
                static fn(array $turnIn): QuestObjective => new ItemObjective(
                    new ItemId($turnIn['ItemID']),
                    $turnIn['iQty'],
                ),
                $quest['turnin'] ?? [],
            ),
            self::rewards($quest),
        );
    }

    /**
     * @param Quest $quest
     *
     * @return list<QuestRequirement>
     */
    private static function requirements(array $quest): array
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
            $requirements[] = new ItemRequirement(new ItemId($item['ItemID']), $item['iQty']);
        }

        return $requirements;
    }

    /**
     * @param Quest $quest
     *
     * @return list<QuestReward>
     */
    private static function rewards(array $quest): array
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
                new ItemId($item['ItemID']),
                new DropRate((float) $item['iRate']),
                $item['iQty'],
                self::rewardType($item['iType']),
            );
        }

        return $rewards;
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

    /**
     * @param list<Quest> $quests
     *
     * @return list<Mention<ItemId|FactionId, object>>
     */
    private static function mentionsOf(array $quests): array
    {
        $mentions = [];
        foreach ($quests as $quest) {
            $rewards = $quest['oRewards'] ?? [];
            $items = [
                ...($quest['oItems'] ?? []),
                ...($quest['oReqd'] ?? []),
                ...($rewards['itemsS'] ?? []),
                ...($rewards['itemsR'] ?? []),
            ];
            foreach ($items as $item) {
                foreach (self::itemValues($item) as $value) {
                    $mentions['item:' . $item['ItemID'] . ':' . $value::class] ??= new Mention(
                        new ItemId($item['ItemID']),
                        $value,
                    );
                }
            }

            if ($quest['FactionID'] !== self::NO_FACTION && $quest['sFaction'] !== '') {
                $mentions["faction:{$quest['FactionID']}"] ??= new Mention(
                    new FactionId($quest['FactionID']),
                    new FactionName($quest['sFaction']),
                );
            }

            $class = trim($quest['sClass'] ?? '');
            if ($quest['iClass'] > 0 && $class !== '') {
                $mentions["class:{$quest['iClass']}"] ??= new Mention(
                    new ClassId($quest['iClass']),
                    new ClassName($class),
                );
            }
        }

        return array_values($mentions);
    }

    /**
     * @param Item $item
     *
     * @return list<object>
     */
    private static function itemValues(array $item): array
    {
        $description = trim($item['sDesc'] ?? '');
        $cost = $item['iCost'] ?? 0;
        $file = $item['sFile'] ?? '';
        $link = $item['sLink'] ?? '';
        $hasFile = str_ends_with($file, '.swf');

        return [
            new ItemName($item['sName']),
            self::detail($item['sType']),
            ...($description === '' ? [] : [new ItemDescription($description)]),
            ...(
                $cost === 0
                    ? []
                    : [new ItemPrice($item['bCoins'] ?? false ? new AdventureCoins($cost) : new Gold($cost))]
            ),
            ...($hasFile ? [new Gamefile($file)] : []),
            ...($hasFile && $link !== '' ? [new Linkage($link)] : []),
            ...self::slot($item['sES'])
                ->map(static fn(EquipmentSlot $slot): array => [$slot])
                ->unwrapOr([]),
        ];
    }

    /** @throws MalformedEventException */
    private static function detail(string $type): Type\Equipment|Type\Weapon|Type\House|Type\Misc
    {
        return match ($type) {
            'Armor' => Type\Equipment::Armor,
            'Cape' => Type\Equipment::Cape,
            'Class' => Type\Equipment::CharacterClass,
            'Misc' => Type\Equipment::Ground,
            'Helm' => Type\Equipment::Helm,
            'Necklace' => Type\Equipment::Necklace,
            'Pet' => Type\Equipment::Pet,
            'Axe' => Type\Weapon::Axe,
            'Bow' => Type\Weapon::Bow,
            'Dagger' => Type\Weapon::Dagger,
            'Gauntlet' => Type\Weapon::Gauntlet,
            'Gun' => Type\Weapon::Gun,
            'HandGun' => Type\Weapon::HandGun,
            'Mace' => Type\Weapon::Mace,
            'Polearm' => Type\Weapon::Polearm,
            'Rifle' => Type\Weapon::Rifle,
            'Staff' => Type\Weapon::Staff,
            'Sword' => Type\Weapon::Sword,
            'Wand' => Type\Weapon::Wand,
            'Whip' => Type\Weapon::Whip,
            'Floor Item' => Type\House::Floor,
            'House' => Type\House::House,
            'Wall Item' => Type\House::Wall,
            'ServerUse' => Type\Misc::Boost,
            'Enhancement' => Type\Misc::Enhancement,
            'Item', 'Note', 'Quest Item', 'Resource' => Type\Misc::Generic,
            default => throw new MalformedEventException(static::class, "unknown item type '{$type}'"),
        };
    }

    /**
     * @throws MalformedEventException
     *
     * @return Option\Option<EquipmentSlot>
     */
    private static function slot(string $code): Option\Option
    {
        return match ($code) {
            'None' => Option\none(),
            'Weapon' => Option\some(EquipmentSlot::Weapon),
            'ar', 'co' => Option\some(EquipmentSlot::Armor),
            'he' => Option\some(EquipmentSlot::Helm),
            'ba' => Option\some(EquipmentSlot::Cape),
            'pe' => Option\some(EquipmentSlot::Pet),
            'mi' => Option\some(EquipmentSlot::Ground),
            'am' => Option\some(EquipmentSlot::Necklace),
            default => throw new MalformedEventException(static::class, "unknown equipment slot '{$code}'"),
        };
    }
}
