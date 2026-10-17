<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests\Unit;

use Lorekeeper\Apps\Desktop\Bots\BotScript;
use Lorekeeper\Client\Bot\Task\LevelUpTask;
use Lorekeeper\Core\Player\PlayerLevel;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class BotScriptTest extends TestCase
{
    #[Test]
    public function level_up_becomes_the_client_task_with_a_level_the_game_has(): void
    {
        static::assertEquals(new LevelUpTask(new PlayerLevel(40)), BotScript::LevelUp->task(['level' => '40']));
        static::assertEquals(new LevelUpTask(new PlayerLevel(100)), BotScript::LevelUp->task(['level' => 250]));
        static::assertEquals(new LevelUpTask(new PlayerLevel(100)), BotScript::LevelUp->task([]));
    }

    #[Test]
    public function level_up_summarizes_its_target_and_has_a_report(): void
    {
        static::assertSame('to level 40', BotScript::LevelUp->summary(['level' => '40']));
        static::assertSame('to level 100', BotScript::LevelUp->summary([]));
        static::assertSame('reports.level-up', BotScript::LevelUp->report());
        static::assertSame(['level' => null], BotScript::blankParameters());
    }
}
