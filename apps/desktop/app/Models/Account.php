<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Lorekeeper\Apps\Desktop\Bots\BotStatus;
use Lorekeeper\Apps\Desktop\Bots\Elapsed;
use Lorekeeper\Apps\Desktop\Bots\Launcher\Launcher;
use Lorekeeper\Apps\Desktop\Bots\LogLevel;
use Lorekeeper\Apps\Desktop\Bots\Scene\Scene;

/**
 * @property int $id
 * @property string $name
 * @property string $password
 * @property null|string $server
 * @property null|int $level
 * @property BotStatus $status
 * @property null|int $pid
 * @property null|string $result
 * @property null|string $map
 * @property null|array<string, mixed> $scene
 * @property null|Carbon $started_at
 * @property null|Carbon $stopped_at
 * @property null|int $script_id
 * @property null|string $step
 * @property null|Script $script
 *
 * @mago-expect lint:too-many-methods
 */
#[Fillable([
    'name',
    'password',
    'server',
    'level',
    'status',
    'pid',
    'result',
    'map',
    'scene',
    'started_at',
    'stopped_at',
    'script_id',
    'step',
])]
#[Hidden(['password'])]
final class Account extends Model
{
    private const array ACTIVE = [BotStatus::Starting, BotStatus::Online, BotStatus::Stopping];

    /** @var array<string, mixed> */
    protected $attributes = ['status' => 'offline'];

    public static function interrupted(): void
    {
        foreach (self::query()->whereIn('status', self::ACTIVE)->get() as $account) {
            if (!$account->settle(BotStatus::Failed, 'interrupted')) {
                continue;
            }

            $account->log('Failed: interrupted', LogLevel::Error);
        }
    }

    /** @return HasMany<BotLog, $this> */
    public function logs(): HasMany
    {
        return $this->hasMany(BotLog::class);
    }

    /** @return BelongsTo<Script, $this> */
    public function script(): BelongsTo
    {
        return $this->belongsTo(Script::class);
    }

    public function play(Script $script): void
    {
        self::query()
            ->whereKey($this->id)
            ->where('status', '!=', BotStatus::Stopping)
            ->update(['script_id' => $script->id, 'step' => null]);
        $this->refresh();

        if ($this->script_id === $script->id) {
            $this->start();
        }
    }

    public function stopScript(): void
    {
        $this->update(['script_id' => null, 'step' => null]);
    }

    public function stepping(string $step): void
    {
        self::query()
            ->whereKey($this->id)
            ->whereNotNull('script_id')
            ->update(['step' => $step]);
    }

    public function scriptEnded(Script $script): void
    {
        self::query()
            ->whereKey($this->id)
            ->where('script_id', $script->id)
            ->update(['script_id' => null, 'step' => null]);
        $this->refresh();
    }

    public function playing(): bool
    {
        return $this->status->isActive();
    }

    public function start(): void
    {
        $claimed = self::query()
            ->whereKey($this->id)
            ->whereNotIn('status', self::ACTIVE)
            ->update(['status' => BotStatus::Starting, 'result' => null, 'pid' => null]);
        $this->refresh();

        if ($claimed === 1) {
            app(Launcher::class)->launch($this);
        }
    }

    public function stop(Launcher $launcher): void
    {
        if ($this->playing() && !$launcher->alive($this)) {
            if ($this->settle(BotStatus::Offline, null)) {
                $this->log('Stopped: the bot process was already gone');
            }

            return;
        }

        self::query()
            ->whereKey($this->id)
            ->whereIn('status', [BotStatus::Starting, BotStatus::Online])
            ->update(['status' => BotStatus::Stopping]);
        $this->refresh();
    }

    public function settle(BotStatus $status, ?string $result): bool
    {
        $settled = self::query()
            ->whereKey($this->id)
            ->whereIn('status', self::ACTIVE)
            ->update([
                'status' => $status,
                'result' => $result,
                'pid' => null,
                'stopped_at' => now(),
                'script_id' => null,
                'step' => null,
            ]);
        $this->refresh();

        return $settled === 1;
    }

    public function scene(): ?Scene
    {
        return $this->scene === null ? null : Scene::fromArray($this->scene);
    }

    public function duration(): ?string
    {
        return Elapsed::between($this->started_at, $this->playing() ? null : $this->stopped_at);
    }

    public function log(string $message, LogLevel $level = LogLevel::Info): void
    {
        $this->logs()->create(['message' => $message, 'level' => $level]);
    }

    /**
     * @return array<string, string>
     *
     * @mago-expect lint:no-literal-password
     */
    protected function casts(): array
    {
        return [
            'password' => 'encrypted',
            'status' => BotStatus::class,
            'scene' => 'array',
            'started_at' => 'datetime',
            'stopped_at' => 'datetime',
        ];
    }
}
