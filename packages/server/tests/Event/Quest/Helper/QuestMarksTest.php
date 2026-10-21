<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Quest\Helper;

use Lorekeeper\Core\World\Quest\QuestMark;
use Lorekeeper\Core\World\Quest\QuestPeriod;
use Lorekeeper\Server\Event\Quest\Helper\QuestMarks;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class QuestMarksTest extends TestCase
{
    #[Test]
    #[TestWith(['id2', QuestPeriod::Day, 2])]
    #[TestWith(['iw0', QuestPeriod::Week, 0])]
    #[TestWith(['im0', QuestPeriod::Month, 0])]
    public function it_reads_the_period_and_group_of_a_mark(string $field, QuestPeriod $period, int $group): void
    {
        static::assertEquals(new QuestMark($period, $group, 6), QuestMarks::of($field, 6)->unwrap());
    }

    #[Test]
    #[TestWith(['ia0', 6])]
    #[TestWith(['idx', 6])]
    #[TestWith(['id2', -1])]
    public function it_reads_nothing_from_a_field_that_is_no_mark(string $field, int $index): void
    {
        static::assertTrue(QuestMarks::of($field, $index)->isNone());
    }

    #[Test]
    public function it_reads_every_mark_held_across_days_weeks_and_months(): void
    {
        static::assertEquals(
            [
                new QuestMark(QuestPeriod::Day, 2, 6),
                new QuestMark(QuestPeriod::Week, 0, 0),
                new QuestMark(QuestPeriod::Month, 0, 1),
            ],
            QuestMarks::held(['id2' => 64, 'iw0' => 1, 'im0' => 2]),
        );
    }
}
