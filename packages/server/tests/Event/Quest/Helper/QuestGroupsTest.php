<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Quest\Helper;

use Lorekeeper\Core\World\Quest\QuestGroup;
use Lorekeeper\Core\World\Quest\QuestGroupId;
use Lorekeeper\Core\World\Quest\QuestGroupNumber;
use Lorekeeper\Server\Event\Quest\Helper\QuestGroups;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class QuestGroupsTest extends TestCase
{
    #[Test]
    #[TestWith(['id2', 'Daily', 70])]
    #[TestWith(['iw0', 'Weekly', 6])]
    #[TestWith(['im0', 'Monthly', 6])]
    public function it_reads_the_group_and_number_of_a_quest_from_its_field_and_bit(
        string $field,
        string $group,
        int $number,
    ): void {
        static::assertEquals(
            new QuestGroupId(QuestGroup::{$group}, new QuestGroupNumber($number)),
            QuestGroups::of($field, 6)->unwrap(),
        );
    }

    #[Test]
    #[TestWith(['ia0', 6])]
    #[TestWith(['idx', 6])]
    #[TestWith(['id2', -1])]
    #[TestWith(['id2', 32])]
    public function it_reads_nothing_from_a_field_or_bit_that_is_no_quest(string $field, int $index): void
    {
        static::assertTrue(QuestGroups::of($field, $index)->isNone());
    }

    #[Test]
    public function it_reads_every_quest_done_across_days_weeks_and_months(): void
    {
        static::assertEquals(
            [
                new QuestGroupId(QuestGroup::Daily, new QuestGroupNumber(70)),
                new QuestGroupId(QuestGroup::Weekly, new QuestGroupNumber(0)),
                new QuestGroupId(QuestGroup::Monthly, new QuestGroupNumber(1)),
            ],
            QuestGroups::held(['id2' => 64, 'iw0' => 1, 'im0' => 2]),
        );
    }
}
