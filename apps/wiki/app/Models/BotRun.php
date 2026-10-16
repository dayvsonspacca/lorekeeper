<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Lorekeeper\Apps\Wiki\Bots\BotScript;
use Lorekeeper\Apps\Wiki\Bots\LogLevel;
use Lorekeeper\Apps\Wiki\Bots\RunStatus;

/**
 * @property int $id
 * @property int $account_id
 * @property BotScript $script
 * @property array<string, mixed> $parameters
 * @property RunStatus $status
 * @property null|int $pid
 * @property null|string $result
 * @property null|Carbon $started_at
 * @property null|Carbon $finished_at
 * @property Account $account
 */
#[Fillable(['account_id', 'script', 'parameters', 'status', 'pid', 'result', 'started_at', 'finished_at'])]
final class BotRun extends Model
{
    /** @var array<string, mixed> */
    protected $attributes = ['status' => 'pending', 'parameters' => '{}'];

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
        if ($this->started_at === null) {
            return null;
        }

        $seconds = (int) $this->started_at->diffInSeconds($this->finished_at ?? now());

        return match (true) {
            $seconds < 60 => "{$seconds}s",
            $seconds < 3600 => sprintf('%dm %02ds', intdiv($seconds, num2: 60), $seconds % 60),
            default => sprintf('%dh %02dm', intdiv($seconds, num2: 3600), intdiv($seconds % 3600, num2: 60)),
        };
    }

    /** @return HasMany<BotRunEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(BotRunEvent::class);
    }

    public function log(string $message, LogLevel $level = LogLevel::Info): void
    {
        $this->logs()->create(['message' => $message, 'level' => $level]);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'script' => BotScript::class,
            'parameters' => 'array',
            'status' => RunStatus::class,
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }
}
