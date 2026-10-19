<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Command\Quest;

use Lorekeeper\Client\Command\Quest\TurnInQuestCommand;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Server\AreaId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TurnInQuestCommandTest extends TestCase
{
    #[Test]
    public function it_turns_in_the_quest_once_without_choosing_a_reward(): void
    {
        static::assertSame(
            '%xt%zm%tryQuestComplete%172760%6187%-1%false%1%wvz%',
            new TurnInQuestCommand(new AreaId(172_760), new QuestId(6187))->toMessage(),
        );
    }

    #[Test]
    public function it_names_the_reward_it_chose_and_how_many_times_it_turns_in(): void
    {
        $command = new TurnInQuestCommand(new AreaId(172_760), new QuestId(2857), new ItemId(19_282), new Quantity(5));

        static::assertSame('%xt%zm%tryQuestComplete%172760%2857%19282%false%5%wvz%', $command->toMessage());
    }
}
