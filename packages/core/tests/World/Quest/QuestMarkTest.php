<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\World\Quest;

use InvalidArgumentException;
use Lorekeeper\Core\Player\Hero\HeroQuestMarks;
use Lorekeeper\Core\World\Quest\QuestMark;
use Lorekeeper\Core\World\Quest\QuestPeriod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class QuestMarkTest extends TestCase
{
    #[Test]
    public function a_mark_is_the_same_only_in_the_same_period_group_and_index(): void
    {
        $cryptoToken = new QuestMark(QuestPeriod::Day, 2, 6);

        static::assertTrue($cryptoToken->is(new QuestMark(QuestPeriod::Day, 2, 6)));
        static::assertFalse($cryptoToken->is(new QuestMark(QuestPeriod::Week, 2, 6)));
        static::assertFalse($cryptoToken->is(new QuestMark(QuestPeriod::Day, 2, 7)));
    }

    #[Test]
    public function the_player_holds_the_marks_of_the_quests_done_in_their_period(): void
    {
        $marks = new HeroQuestMarks([new QuestMark(QuestPeriod::Day, 2, 6)]);

        static::assertTrue($marks->has(new QuestMark(QuestPeriod::Day, 2, 6)));
        static::assertFalse($marks->has(new QuestMark(QuestPeriod::Day, 0, 9)));
        static::assertFalse(new HeroQuestMarks()->has(new QuestMark(QuestPeriod::Day, 2, 6)));
    }

    #[Test]
    public function a_mark_has_no_negative_place(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new QuestMark(QuestPeriod::Day, -1, 6);
    }
}
