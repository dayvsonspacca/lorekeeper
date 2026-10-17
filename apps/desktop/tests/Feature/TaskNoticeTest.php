<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Lorekeeper\Apps\Desktop\Bots\BotScript;
use Lorekeeper\Apps\Desktop\Bots\Notifier\TaskNotice;
use Lorekeeper\Apps\Desktop\Bots\TaskStatus;
use Lorekeeper\Apps\Desktop\Models\Account;
use Lorekeeper\Apps\Desktop\Models\BotTask;
use Lorekeeper\Apps\Desktop\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class TaskNoticeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_finished_task_tells_whose_task_it_was_and_how_it_ended(): void
    {
        $done = $this->task(TaskStatus::Done, 'Reached level 6');
        $failed = $this->task(TaskStatus::Failed, 'Could not join icestormarena');

        static::assertEquals(new TaskNotice('Hero · Level up', 'Reached level 6'), TaskNotice::of($done));
        static::assertEquals(
            new TaskNotice('Hero · Level up', 'Failed: Could not join icestormarena'),
            TaskNotice::of($failed),
        );
    }

    #[Test]
    public function a_cancelled_or_unfinished_task_is_not_announced(): void
    {
        static::assertNull(TaskNotice::of($this->task(TaskStatus::Cancelled, 'the run stopped')));
        static::assertNull(TaskNotice::of($this->task(TaskStatus::Running, null)));
    }

    private function task(TaskStatus $status, ?string $result): BotTask
    {
        return Account::query()
            ->firstOrCreate(['name' => 'Hero'], ['password' => bin2hex(random_bytes(8))])
            ->runs()
            ->create()
            ->tasks()
            ->create([
                'script' => BotScript::LevelUp,
                'parameters' => ['level' => 6],
                'status' => $status,
                'result' => $result,
            ]);
    }
}
