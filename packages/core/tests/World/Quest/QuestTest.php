<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\World\Quest;

use Lorekeeper\Core\World\Quest\Quest;
use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Core\World\Quest\QuestName;
use Lorekeeper\Core\World\Quest\QuestTag;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Option;

final class QuestTest extends TestCase
{
    #[Test]
    public function a_quest_holds_only_the_tags_given_to_it(): void
    {
        $quest = new Quest(new QuestId(4_195), new QuestName('Crypto Token'), [], [], [], Option\none(), Option\none());

        static::assertFalse($quest->tags->has(QuestTag::Daily));

        $quest->tags->add(QuestTag::Daily)->add(QuestTag::MemberOnly);

        static::assertTrue($quest->tags->has(QuestTag::Daily));
        static::assertTrue($quest->tags->has(QuestTag::MemberOnly));
        static::assertFalse($quest->tags->has(QuestTag::Weekly));
    }
}
