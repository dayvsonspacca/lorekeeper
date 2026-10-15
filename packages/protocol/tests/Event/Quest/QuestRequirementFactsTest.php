<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Tests\Event\Quest;

use Lorekeeper\Core\CharacterClass\ClassId;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\Progression\Rank;
use Lorekeeper\Core\World\Faction\FactionId;
use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Core\World\Quest\Requirement\ClassRankRequirement;
use Lorekeeper\Core\World\Quest\Requirement\FactionRankRequirement;
use Lorekeeper\Core\World\Quest\Requirement\ItemRequirement;
use Lorekeeper\Core\World\Quest\Requirement\MemberRequirement;
use Lorekeeper\Core\World\Quest\Requirement\PlayerLevelRequirement;
use Lorekeeper\Core\World\Quest\Requirement\PreviousQuestRequirement;
use Lorekeeper\Protocol\Event\Quest\QuestFacts;
use Lorekeeper\Protocol\Event\Quest\QuestRequirementFacts;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class QuestRequirementFactsTest extends TestCase
{
    /** @return iterable<string, array{array<string, mixed>, list<object>}> */
    public static function quests(): iterable
    {
        yield 'no requirements' => [[], []];
        yield 'level 0 means none' => [['iLvl' => 0], []];
        yield 'level' => [['iLvl' => 20], [new PlayerLevelRequirement(new PlayerLevel(20))]];
        yield 'member' => [['bUpg' => true], [new MemberRequirement()]];
        yield 'class rank' => [
            ['iClass' => 17, 'iReqCP' => 3600],
            [new ClassRankRequirement(new ClassId(17), new Rank(3))],
        ];
        yield 'faction rank' => [
            ['FactionID' => 6, 'sFaction' => 'Yokai', 'iReqRep' => 302_500],
            [new FactionRankRequirement(new FactionId(6), new Rank(10))],
        ];
        yield 'previous quest' => [
            ['bQuest' => true, 'strPrevious' => '2025'],
            [new PreviousQuestRequirement(new QuestId(2025))],
        ];
        yield 'previous quest without flag' => [['strPrevious' => '2025'], []];
        yield 'required items' => [
            ['reqd' => [['ItemID' => 33], ['ItemID' => 559]]],
            [new ItemRequirement(new ItemId(33)), new ItemRequirement(new ItemId(559))],
        ];
    }

    /**
     * @param array<string, mixed> $quest
     * @param list<object> $requirements
     */
    #[Test]
    #[DataProvider('quests')]
    public function it_extracts_requirements(array $quest, array $requirements): void
    {
        $facts = QuestRequirementFacts::of(new QuestId(3), QuestFacts::type()->coerce(QuestStub::quest($quest)));

        static::assertEquals($requirements, array_map(static fn($fact): object => $fact->value, $facts));
    }
}
