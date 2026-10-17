<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Lorekeeper\Apps\Wiki\Bots\Elapsed;
use Lorekeeper\Apps\Wiki\Bots\RunStatus;
use Lorekeeper\Client\Bot\Log\LogLevel;

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
 */
#[Fillable(['account_id', 'status', 'pid', 'result', 'map', 'started_at', 'finished_at'])]
final class BotRun extends Model
{
    /** @var array<string, mixed> */
    protected $attributes = ['status' => 'pending'];

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
            'status' => RunStatus::class,
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }
}
