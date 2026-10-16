<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Lorekeeper\Apps\Wiki\Bots\BotScript;
use Lorekeeper\Apps\Wiki\Bots\Elapsed;
use Lorekeeper\Apps\Wiki\Bots\TaskStatus;

/**
 * @property int $id
 * @property int $bot_run_id
 * @property BotScript $script
 * @property array<string, mixed> $parameters
 * @property TaskStatus $status
 * @property null|string $result
 * @property null|Carbon $started_at
 * @property null|Carbon $finished_at
 * @property BotRun $run
 */
#[Fillable(['script', 'parameters', 'status', 'result', 'started_at', 'finished_at'])]
final class BotTask extends Model
{
    /** @var array<string, mixed> */
    protected $attributes = ['status' => 'pending', 'parameters' => '{}'];

    /** @return BelongsTo<BotRun, $this> */
    public function run(): BelongsTo
    {
        return $this->belongsTo(BotRun::class, 'bot_run_id');
    }

    public function title(): string
    {
        return "{$this->script->getLabel()} · {$this->script->summary($this->parameters)}";
    }

    public function duration(): ?string
    {
        return Elapsed::between($this->started_at, $this->finished_at);
    }

    public function cancellable(): bool
    {
        return $this->status === TaskStatus::Pending || $this->status === TaskStatus::Running;
    }

    public function finish(TaskStatus $status, ?string $result = null): void
    {
        $this->update(['status' => $status, 'result' => $result, 'finished_at' => now()]);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'script' => BotScript::class,
            'parameters' => 'array',
            'status' => TaskStatus::class,
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }
}
