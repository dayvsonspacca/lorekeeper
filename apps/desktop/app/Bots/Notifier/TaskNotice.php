<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Bots\Notifier;

use Lorekeeper\Apps\Desktop\Bots\TaskStatus;
use Lorekeeper\Apps\Desktop\Models\BotTask;

final readonly class TaskNotice
{
    public function __construct(
        public string $title,
        public string $message,
    ) {}

    public static function of(BotTask $task): ?self
    {
        $title = "{$task->run->account->name} · {$task->script->getLabel()}";

        return match ($task->status) {
            TaskStatus::Done => new self($title, $task->result ?? 'Done'),
            TaskStatus::Failed => new self($title, "Failed: {$task->result}"),
            default => null,
        };
    }
}
