<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Command\Quest;

use Lorekeeper\Client\Command\Quest\LoadQuestCommand;
use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Server\AreaId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LoadQuestCommandTest extends TestCase
{
    #[Test]
    public function it_serializes_one_quest(): void
    {
        static::assertSame(
            '%xt%zm%getQuests%1%275%',
            new LoadQuestCommand(new AreaId(1), new QuestId(275))->toMessage(),
        );
    }

    #[Test]
    public function it_serializes_the_quests_of_an_npc(): void
    {
        $command = new LoadQuestCommand(new AreaId(172_760), new QuestId(11), new QuestId(407), new QuestId(408));

        static::assertSame('%xt%zm%getQuests%172760%11%407%408%', $command->toMessage());
    }
}
