<?php

declare(strict_types=1);

namespace Lorekeeper\Persistence\Tests\Codec;

use Lorekeeper\Core\Access\MemberOnly;
use Lorekeeper\Core\Asset\Gamefile;
use Lorekeeper\Core\Asset\Linkage;
use Lorekeeper\Core\Attribute\Health;
use Lorekeeper\Core\Attribute\Mana;
use Lorekeeper\Core\CharacterClass\ClassCategory;
use Lorekeeper\Core\CharacterClass\ClassDescription;
use Lorekeeper\Core\CharacterClass\ClassId;
use Lorekeeper\Core\CharacterClass\ClassName;
use Lorekeeper\Core\CharacterClass\ManaRegeneration;
use Lorekeeper\Core\Currency\AdventureCoins;
use Lorekeeper\Core\Currency\Gold;
use Lorekeeper\Core\Item\DropRate;
use Lorekeeper\Core\Item\EquipmentSlot;
use Lorekeeper\Core\Item\ItemDescription;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\ItemPrice;
use Lorekeeper\Core\Item\ItemType;
use Lorekeeper\Core\Knowledge\Subject;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\Progression\Experience;
use Lorekeeper\Core\Progression\LevelRange;
use Lorekeeper\Core\Progression\Rank;
use Lorekeeper\Core\World\Faction\FactionId;
use Lorekeeper\Core\World\Faction\FactionName;
use Lorekeeper\Core\World\Faction\Reputation;
use Lorekeeper\Core\World\Map\MapAnalyzed;
use Lorekeeper\Core\World\Map\MapDescription;
use Lorekeeper\Core\World\Map\MapId;
use Lorekeeper\Core\World\Map\MapLink;
use Lorekeeper\Core\World\Map\MapMonster;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Map\MapNpc;
use Lorekeeper\Core\World\Map\MapTitle;
use Lorekeeper\Core\World\Map\MapUnavailable;
use Lorekeeper\Core\World\Monster\MonsterId;
use Lorekeeper\Core\World\Monster\MonsterLevel;
use Lorekeeper\Core\World\Monster\MonsterName;
use Lorekeeper\Core\World\Monster\Race;
use Lorekeeper\Core\World\Npc\NpcId;
use Lorekeeper\Core\World\Npc\NpcName;
use Lorekeeper\Core\World\Npc\NpcQuest;
use Lorekeeper\Core\World\Npc\NpcShop;
use Lorekeeper\Core\World\Npc\NpcTitle;
use Lorekeeper\Core\World\Quest\QuestDescription;
use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Core\World\Quest\QuestName;
use Lorekeeper\Core\World\Quest\Requirement\ClassRankRequirement;
use Lorekeeper\Core\World\Quest\Requirement\FactionRankRequirement;
use Lorekeeper\Core\World\Quest\Requirement\ItemRequirement;
use Lorekeeper\Core\World\Quest\Requirement\MemberRequirement;
use Lorekeeper\Core\World\Quest\Requirement\PlayerLevelRequirement;
use Lorekeeper\Core\World\Quest\Requirement\PreviousQuestRequirement;
use Lorekeeper\Core\World\Quest\Reward\ExperienceReward;
use Lorekeeper\Core\World\Quest\Reward\GoldReward;
use Lorekeeper\Core\World\Quest\Reward\ItemReward;
use Lorekeeper\Core\World\Quest\Reward\ReputationReward;
use Lorekeeper\Core\World\Quest\TurnInItem;
use Lorekeeper\Core\World\Region\RegionId;
use Lorekeeper\Core\World\Region\RegionMap;
use Lorekeeper\Core\World\Region\RegionName;
use Lorekeeper\Core\World\Shop\MergeCost;
use Lorekeeper\Core\World\Shop\ShopId;
use Lorekeeper\Core\World\Shop\ShopItem;
use Lorekeeper\Core\World\Shop\ShopName;
use Lorekeeper\Core\World\Shop\ShopUnavailable;
use Lorekeeper\Persistence\Codec\DefaultCodecs;
use Lorekeeper\Persistence\Exception\UnknownTypeException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Option;
use stdClass;

final class DefaultCodecsTest extends TestCase
{
    /** @return iterable<string, array{Subject}> */
    public static function subjects(): iterable
    {
        yield 'item' => [new ItemId(447)];
        yield 'quest' => [new QuestId(14)];
        yield 'monster' => [new MonsterId(32)];
        yield 'faction' => [new FactionId(5)];
        yield 'class' => [new ClassId(17)];
        yield 'shop' => [new ShopId(3)];
        yield 'region' => [new RegionId(1)];
        yield 'map' => [new MapName('battleon')];
        yield 'class name' => [new ClassName('Blaze Binder')];
        yield 'npc' => [new NpcId(new MapName('battleon'), new Linkage('Twilly_APOP'))];
    }

    /** @return iterable<string, array{object}> */
    public static function values(): iterable
    {
        $forest = new MapName('forest');

        yield 'class category melee' => [ClassCategory::Melee];
        yield 'class category caster' => [ClassCategory::Caster];
        yield 'class description' => [new ClassDescription('Blaze Binders control the power of fire and darkness.')];
        yield 'class mana regeneration' => [new ManaRegeneration(
            'Blaze Binder gain mana from all hits landed in combat.',
        )];
        yield 'item name' => [new ItemName('Sweeping Broom')];
        yield 'item description' => [new ItemDescription("If you /use this broom you'll be sweeping the floor!")];
        yield 'item type' => [new ItemType('Axe')];
        yield 'item slot' => [EquipmentSlot::Ground];
        yield 'item gold price' => [new ItemPrice(new Gold(26_000))];
        yield 'item ac price' => [new ItemPrice(new AdventureCoins(5000))];
        yield 'gamefile' => [new Gamefile('items/staves/staff10a.swf')];
        yield 'linkage' => [new Linkage('MiltonPoolgun01')];
        yield 'member only' => [new MemberOnly()];
        yield 'health' => [new Health(610)];
        yield 'mana' => [new Mana(100)];
        yield 'monster name' => [new MonsterName('Zardman Grunt')];
        yield 'monster level' => [new MonsterLevel(3)];
        yield 'race' => [Race::Undead];
        yield 'map id' => [new MapId(2)];
        yield 'map monster' => [new MapMonster(new MonsterId(1))];
        yield 'map npc' => [new MapNpc(new NpcId(new MapName('battleon'), new Linkage('Twilly_APOP')))];
        yield 'npc name' => [new NpcName('Twilly')];
        yield 'npc title' => [new NpcTitle('Helpful Healing Moglin')];
        yield 'map analyzed' => [new MapAnalyzed(new Gamefile('Battleon/town-Battleon-9Oct26.swf'))];
        yield 'npc quest' => [new NpcQuest(new QuestId(407))];
        yield 'npc shop' => [new NpcShop(new ShopId(299))];
        yield 'shop name' => [new ShopName('Battleon Assorted shop')];
        yield 'shop unavailable' => [new ShopUnavailable()];
        yield 'shop item' => [new ShopItem(new ItemId(16_416))];
        yield 'merge shop item' => [new ShopItem(new ItemId(66_472), [new MergeCost(new ItemId(66_486), 2)])];
        yield 'map link' => [new MapLink(new MapName('fableforest'))];
        yield 'map unavailable' => [new MapUnavailable()];
        yield 'faction name' => [new FactionName('Good')];
        yield 'region name' => [new RegionName('Greenguard')];
        yield 'region map' => [new RegionMap(
            $forest,
            new MapTitle('Forest'),
            Option\some(new MapDescription('Home, sweet home to the Zardman Tribe.')),
            new LevelRange(3, 3),
        )];
        yield 'region map without description' => [new RegionMap(
            $forest,
            new MapTitle('Forest'),
            Option\none(),
            new LevelRange(0, 0),
        )];
        yield 'quest name' => [new QuestName('Specialty Pizza')];
        yield 'quest description' => [new QuestDescription('Bring me 5 Yummy Slime Sauces!', 'Ooh, thanks!')];
        yield 'quest description without end' => [new QuestDescription('Bring me 5 Yummy Slime Sauces!')];
        yield 'turn in' => [new TurnInItem(new ItemId(98), 5)];
        yield 'player level' => [new PlayerLevelRequirement(new PlayerLevel(20))];
        yield 'previous quest' => [new PreviousQuestRequirement(new QuestId(2025))];
        yield 'item requirement' => [new ItemRequirement(new ItemId(33))];
        yield 'member requirement' => [new MemberRequirement()];
        yield 'class rank' => [new ClassRankRequirement(new ClassId(17), new Rank(3))];
        yield 'faction rank' => [new FactionRankRequirement(new FactionId(6), new Rank(10))];
        yield 'gold reward' => [new GoldReward(new Gold(1500))];
        yield 'experience reward' => [new ExperienceReward(new Experience(1500))];
        yield 'reputation reward' => [new ReputationReward(new FactionId(5), new Reputation(500))];
        yield 'item reward' => [new ItemReward(new ItemId(100), new DropRate(12.5), 1)];
    }

    #[Test]
    #[DataProvider('subjects')]
    public function it_round_trips_subjects(Subject $subject): void
    {
        $codecs = DefaultCodecs::create();
        $codec = $codecs->subjectFor($subject);

        static::assertEquals($subject, $codecs->subject($codec->key)->decode($codec->encode($subject)));
    }

    #[Test]
    #[DataProvider('values')]
    public function it_round_trips_values_through_json(object $value): void
    {
        $codecs = DefaultCodecs::create();
        $codec = $codecs->valueFor($value);
        $json = json_encode($codec->encode($value), JSON_THROW_ON_ERROR);

        static::assertEquals(
            $value,
            $codecs->value($codec->key())->decode(json_decode($json, associative: true, flags: JSON_THROW_ON_ERROR)),
        );
    }

    #[Test]
    public function it_rejects_unknown_values(): void
    {
        $this->expectException(UnknownTypeException::class);

        DefaultCodecs::create()->valueFor(new stdClass());
    }

    #[Test]
    public function it_rejects_unknown_keys(): void
    {
        $this->expectException(UnknownTypeException::class);

        DefaultCodecs::create()->value('item.colour');
    }
}
