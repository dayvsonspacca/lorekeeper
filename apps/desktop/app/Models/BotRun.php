<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Lorekeeper\Apps\Desktop\Bots\Elapsed;
use Lorekeeper\Apps\Desktop\Bots\Launcher\Launcher;
use Lorekeeper\Apps\Desktop\Bots\LogLevel;
use Lorekeeper\Apps\Desktop\Bots\RunStatus;
use Lorekeeper\Apps\Desktop\Bots\TaskStatus;
use Override;

/**
 * @property int $id
 * @property int $account_id
 * @property RunStatus $status
 * @property null|int $pid
 * @property null|string $result
 * @property null|string $map
 * @property null|Carbon $started_at
 * @property null|Carbon $finished_at
 * @property Account $account
 *
 * @mago-expect lint:too-many-methods
 */
#[Fillable(['account_id', 'status', 'pid', 'result', 'map', 'started_at', 'finished_at'])]
final class BotRun extends Model
{
    /** @var array<string, mixed> */
    protected $attributes = ['status' => 'pending'];

    public static function interrupted(): void
    {
        foreach (self::query()
            ->whereIn('status', [RunStatus::Pending, RunStatus::Running, RunStatus::Stopping])
            ->get() as $run) {
            $run->closeTasks('interrupted');
            $run->update([
                'status' => RunStatus::Failed,
                'result' => 'interrupted',
                'pid' => null,
                'finished_at' => now(),
            ]);
            $run->log('Failed: interrupted', LogLevel::Error);
        }
    }

    public function stop(Launcher $launcher): void
    {
        if ($this->status === RunStatus::Running && !$launcher->alive($this)) {
            $this->closeTasks('the bot was gone');
            $this->update(['status' => RunStatus::Stopped, 'pid' => null, 'finished_at' => now()]);
            $this->log('Stopped: the bot process was already gone');

            return;
        }

        $this->update(['status' => RunStatus::Stopping]);
    }

    public function currentTask(): ?BotTask
    {
        $task = $this
            ->tasks()
            ->whereIn('status', [TaskStatus::Running, TaskStatus::Cancelling])
            ->latest('id')
            ->first();

        return $task instanceof BotTask ? $task : null;
    }

    #[Override]
    protected static function booted(): void
    {
        static::created(static function (self $run): void {
            app(Launcher::class)->launch($run);
        });
    }

    /** @return BelongsTo<Account, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /** @return HasMany<BotRunLog, $this> */
    public function logs(): HasMany
    {
        return $this->hasMany(BotRunLog::class);
    }

    public function duration(): ?string
    {
        return Elapsed::between($this->started_at, $this->finished_at);
    }

    /** @return HasMany<BotTask, $this> */
    public function tasks(): HasMany
    {
        return $this->hasMany(BotTask::class);
    }

    public function closeTasks(string $reason): void
    {
        $this
            ->tasks()
            ->whereIn('status', [TaskStatus::Running, TaskStatus::Cancelling])
            ->update([
                'status' => TaskStatus::Failed,
                'result' => $reason,
                'finished_at' => now(),
            ]);
        $this
            ->tasks()
            ->where('status', TaskStatus::Pending)
            ->update([
                'status' => TaskStatus::Cancelled,
                'result' => $reason,
                'finished_at' => now(),
            ]);
    }

    public function nextTask(): ?BotTask
    {
        $task = $this
            ->tasks()
            ->where('status', TaskStatus::Pending)
            ->oldest('id')
            ->first();

        return $task instanceof BotTask ? $task : null;
    }

    public function log(string $message, LogLevel $level = LogLevel::Info, ?BotTask $task = null): void
    {
        $this->logs()->create(['message' => $message, 'level' => $level, 'bot_task_id' => $task?->id]);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => RunStatus::class,
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }
}
