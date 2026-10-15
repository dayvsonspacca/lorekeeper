<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Tests\Command;

use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Protocol\AreaId;
use Lorekeeper\Protocol\Command\LoadQuestCommand;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LoadQuestCommandTest extends TestCase
{
    #[Test]
    public function it_serializes_to_message(): void
    {
        $command = new LoadQuestCommand(new AreaId(1), new QuestId(275));

        static::assertSame('%xt%zm%getQuests%1%275%', $command->toMessage());
    }
}
