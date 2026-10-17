<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Lorekeeper\Apps\Desktop\Bots\BotScript;
use Lorekeeper\Apps\Desktop\Bots\Elapsed;
use Lorekeeper\Apps\Desktop\Bots\TaskStatus;

/**
 * @property int $id
 * @property int $bot_run_id
 * @property BotScript $script
 * @property array<string, mixed> $parameters
 * @property TaskStatus $status
 * @property null|string $result
 * @property null|int $progress_done
 * @property null|int $progress_total
 * @property int $kills
 * @property int $deaths
 * @property int $experience
 * @property int $gold
 * @property null|int $level_from
 * @property null|int $level_to
 * @property null|Carbon $started_at
 * @property null|Carbon $finished_at
 * @property BotRun $run
 */
#[Fillable([
    'script',
    'parameters',
    'status',
    'result',
    'progress_done',
    'progress_total',
    'kills',
    'deaths',
    'experience',
    'gold',
    'level_from',
    'level_to',
    'started_at',
    'finished_at',
])]
final class BotTask extends Model
{
    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'pending',
        'parameters' => '{}',
        'kills' => 0,
        'deaths' => 0,
        'experience' => 0,
        'gold' => 0,
    ];

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

    public function percent(): ?int
    {
        if ($this->progress_total === null || $this->progress_total < 1) {
            return null;
        }

        return (int) round((min($this->progress_done ?? 0, $this->progress_total) * 100) / $this->progress_total);
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
