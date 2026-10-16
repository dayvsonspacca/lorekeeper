<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\World\Quest\Requirement;

use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Core\World\Quest\Requirement\PreviousQuestRequirement;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PreviousQuestRequirementTest extends TestCase
{
    #[Test]
    public function it_holds_the_quest(): void
    {
        static::assertSame(39, new PreviousQuestRequirement(new QuestId(39))->questId->value);
    }
}
