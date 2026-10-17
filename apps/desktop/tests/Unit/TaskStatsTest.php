<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests\Unit;

use Illuminate\Support\Carbon;
use Lorekeeper\Apps\Desktop\Bots\BotScript;
use Lorekeeper\Apps\Desktop\Bots\TaskStats;
use Lorekeeper\Apps\Desktop\Models\BotTask;
use Lorekeeper\Apps\Desktop\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class TaskStatsTest extends TestCase
{
    #[Test]
    public function it_tells_the_pace_of_a_level_up_and_how_long_is_left(): void
    {
        $task = new BotTask();
        $task->script = BotScript::LevelUp;
        $task->parameters = ['level' => 10];
        $task->level_from = 4;
        $task->level_to = 6;
        $task->kills = 60;
        $task->started_at = Carbon::parse('2026-10-17 10:00:00');

        $stats = new TaskStats($task, now: Carbon::parse('2026-10-17 10:20:00'));

        static::assertSame(6, $stats->level());
        static::assertSame(10, $stats->target());
        static::assertSame(2, $stats->levelsGained());
        static::assertSame(4, $stats->levelsLeft());
        static::assertSame(3.0, $stats->killsPerMinute());
        static::assertSame(10.0, $stats->minutesPerLevel());
        static::assertSame(40.0, $stats->minutesToTarget());
    }

    #[Test]
    public function it_does_not_guess_before_there_is_something_to_measure(): void
    {
        $task = new BotTask();
        $task->script = BotScript::LevelUp;
        $task->parameters = [];
        $task->started_at = Carbon::parse('2026-10-17 10:00:00');

        $stats = new TaskStats($task, now: Carbon::parse('2026-10-17 10:00:10'));

        static::assertSame(100, $stats->target());
        static::assertNull($stats->level());
        static::assertNull($stats->levelsLeft());
        static::assertNull($stats->killsPerMinute());
        static::assertNull($stats->minutesPerLevel());
        static::assertNull($stats->minutesToTarget());
        static::assertSame(0, $stats->levelsGained());
    }
}
