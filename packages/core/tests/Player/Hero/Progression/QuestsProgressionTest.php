<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\Player\Hero\Progression;

use Lorekeeper\Core\Player\Hero\Progression\QuestsProgression;
use Lorekeeper\Core\World\Quest\Quest;
use Lorekeeper\Core\World\Quest\QuestGroup;
use Lorekeeper\Core\World\Quest\QuestGroupId;
use Lorekeeper\Core\World\Quest\QuestGroupNumber;
use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Core\World\Quest\QuestName;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Option;

final class QuestsProgressionTest extends TestCase
{
    #[Test]
    public function it_knows_which_grouped_quests_the_hero_completed(): void
    {
        $quests = new QuestsProgression([self::daily(70)]);

        static::assertTrue($quests->has(self::daily(70)));
        static::assertFalse($quests->has(self::daily(9)));
        static::assertFalse(new QuestsProgression()->has(self::daily(70)));
    }

    #[Test]
    public function a_quest_counts_as_completed_only_through_its_group(): void
    {
        $quests = new QuestsProgression([self::daily(70)]);

        static::assertTrue($quests->completed(self::quest(Option\some(self::daily(70)))));
        static::assertFalse($quests->completed(self::quest(Option\some(self::daily(9)))));
        static::assertFalse($quests->completed(self::quest(Option\none())));
    }

    #[Test]
    public function it_completes_a_grouped_quest_only_once(): void
    {
        $quests = new QuestsProgression();

        $quests->complete(self::daily(70));
        $quests->complete(self::daily(70));

        static::assertTrue($quests->has(self::daily(70)));
        static::assertCount(1, $quests->completed);
    }

    #[Test]
    public function it_forgets_a_single_grouped_quest(): void
    {
        $quests = new QuestsProgression([self::daily(70), self::daily(9)]);

        $quests->forget(self::daily(70));

        static::assertFalse($quests->has(self::daily(70)));
        static::assertTrue($quests->has(self::daily(9)));
    }

    #[Test]
    public function a_new_period_clears_only_the_quests_of_its_group(): void
    {
        $weekly = new QuestGroupId(QuestGroup::Weekly, new QuestGroupNumber(3));
        $quests = new QuestsProgression([self::daily(70), $weekly]);

        $quests->reset(QuestGroup::Daily);

        static::assertFalse($quests->has(self::daily(70)));
        static::assertTrue($quests->has($weekly));
    }

    private static function daily(int $number): QuestGroupId
    {
        return new QuestGroupId(QuestGroup::Daily, new QuestGroupNumber($number));
    }

    /** @param Option\Option<QuestGroupId> $groupId */
    private static function quest(Option\Option $groupId): Quest
    {
        return new Quest(new QuestId(4_195), new QuestName('Crypto Token'), [], [], [], Option\none(), $groupId);
    }
}
