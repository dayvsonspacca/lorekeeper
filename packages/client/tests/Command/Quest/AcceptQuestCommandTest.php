<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Command\Quest;

use Lorekeeper\Client\Command\Quest\AcceptQuestCommand;
use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Server\AreaId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AcceptQuestCommandTest extends TestCase
{
    #[Test]
    public function it_serializes_the_quest_to_accept(): void
    {
        static::assertSame(
            '%xt%zm%acceptQuest%172760%6187%',
            new AcceptQuestCommand(new AreaId(172_760), new QuestId(6187))->toMessage(),
        );
    }
}
