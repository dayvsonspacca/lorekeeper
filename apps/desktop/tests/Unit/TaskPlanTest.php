<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests\Unit;

use Lorekeeper\Apps\Desktop\Bots\BotScript;
use Lorekeeper\Apps\Desktop\Bots\RoutineSteps;
use Lorekeeper\Apps\Desktop\Bots\TaskPlan;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TaskPlanTest extends TestCase
{
    #[Test]
    public function a_character_already_past_some_stops_skips_them_and_enhances_once_before_going_on(): void
    {
        $planned = TaskPlan::trimmed(TaskPlan::listed(RoutineSteps::levelUp(5, 100, 5, 'Lucky')), 24);

        static::assertSame(
            [
                [BotScript::Enhance, ['type' => 'Lucky']],
                [BotScript::LevelUp, ['level' => 25]],
                [BotScript::Enhance, ['type' => 'Lucky']],
                [BotScript::LevelUp, ['level' => 30]],
            ],
            array_map(
                static fn(array $task): array => [$task['script'], $task['parameters']],
                array_slice($planned, offset: 0, length: 4),
            ),
        );
        static::assertCount(33, $planned);
    }

    #[Test]
    public function nothing_is_skipped_when_the_level_is_not_known(): void
    {
        static::assertCount(40, TaskPlan::trimmed(TaskPlan::listed(RoutineSteps::levelUp(5, 100, 5, 'Lucky')), null));
    }

    #[Test]
    public function the_same_task_twice_in_a_row_is_queued_once(): void
    {
        $planned = TaskPlan::trimmed(TaskPlan::listed([
            ['script' => 'crypto-token', 'parameters' => []],
            ['script' => 'crypto-token', 'parameters' => []],
            ['script' => 'enhance', 'parameters' => ['type' => 'Lucky']],
            ['script' => 'enhance', 'parameters' => ['type' => 'Wizard']],
            ['script' => 'nothing-known', 'parameters' => []],
        ]), 50);

        static::assertSame(
            [
                [BotScript::CryptoToken, []],
                [BotScript::Enhance, ['type' => 'Lucky']],
                [BotScript::Enhance, ['type' => 'Wizard']],
            ],
            array_map(static fn(array $task): array => [$task['script'], $task['parameters']], $planned),
        );
    }
}
