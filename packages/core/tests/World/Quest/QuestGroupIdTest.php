<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\World\Quest;

use Lorekeeper\Core\World\Quest\QuestGroup;
use Lorekeeper\Core\World\Quest\QuestGroupId;
use Lorekeeper\Core\World\Quest\QuestGroupNumber;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class QuestGroupIdTest extends TestCase
{
    #[Test]
    public function a_quest_is_the_same_only_in_the_same_group_and_number(): void
    {
        $cryptoToken = new QuestGroupId(QuestGroup::Daily, new QuestGroupNumber(70));

        static::assertTrue($cryptoToken->equals(new QuestGroupId(QuestGroup::Daily, new QuestGroupNumber(70))));
        static::assertFalse($cryptoToken->equals(new QuestGroupId(QuestGroup::Weekly, new QuestGroupNumber(70))));
        static::assertFalse($cryptoToken->equals(new QuestGroupId(QuestGroup::Daily, new QuestGroupNumber(71))));
    }
}
