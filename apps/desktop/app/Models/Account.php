<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Lorekeeper\Apps\Desktop\Bots\Launcher\Launcher;
use Lorekeeper\Apps\Desktop\Bots\RunStatus;
use Lorekeeper\Apps\Desktop\Bots\Scene\Scene;
use Lorekeeper\Apps\Desktop\Bots\TaskStatus;

/**
 * @property int $id
 * @property string $name
 * @property string $password
 * @property null|string $server
 * @property null|int $level
 */
#[Fillable(['name', 'password', 'server', 'level'])]
#[Hidden(['password'])]
final class Account extends Model
{
    /** @return HasMany<BotRun, $this> */
    public function runs(): HasMany
    {
        return $this->hasMany(BotRun::class);
    }

    /** @return HasOne<BotRun, $this> */
    public function latestRun(): HasOne
    {
        return $this->hasOne(BotRun::class)->latestOfMany();
    }

    public function activeRun(): ?BotRun
    {
        $run = $this
            ->runs()
            ->whereIn('status', [RunStatus::Pending, RunStatus::Running, RunStatus::Stopping])
            ->latest('id')
            ->first();

        return $run instanceof BotRun ? $run : null;
    }

    public function scene(): ?Scene
    {
        $run = $this
            ->runs()
            ->whereNotNull('scene')
            ->latest('id')
            ->first();

        return $run instanceof BotRun ? $run->scene() : null;
    }

    public function start(): BotRun
    {
        return $this->activeRun() ?? $this->runs()->create();
    }

    public function resume(): void
    {
        $unfinished = $this->unfinishedTasks();
        if ($unfinished === [] || $this->activeRun() !== null) {
            return;
        }

        $run = BotRun::withoutEvents(fn(): BotRun => $this->runs()->create());
        foreach ($unfinished as $task) {
            $run->tasks()->create(['script' => $task->script, 'parameters' => $task->parameters]);
        }

        app(Launcher::class)->launch($run);
    }

    /** @return list<BotTask> */
    public function unfinishedTasks(): array
    {
        $run = $this->runs()->latest('id')->first();
        if (!$run instanceof BotRun || $run->status->isActive()) {
            return [];
        }

        return $run
            ->tasks()
            ->whereIn('status', [TaskStatus::Failed, TaskStatus::Cancelled])
            ->orderBy('id')
            ->get()
            ->all();
    }

    /**
     * @return array<string, string>
     *
     * @mago-expect lint:no-literal-password
     */
    protected function casts(): array
    {
        return ['password' => 'encrypted'];
    }
}
