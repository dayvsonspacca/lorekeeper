<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Lorekeeper\Apps\Wiki\Bots\BotScript;
use Lorekeeper\Apps\Wiki\Bots\RunStatus;

/**
 * @property int $id
 * @property int $account_id
 * @property BotScript $script
 * @property array<string, string> $parameters
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

    public function log(string $message): void
    {
        $this->logs()->create(['message' => $message]);
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
