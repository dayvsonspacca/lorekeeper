<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Lorekeeper\Apps\Desktop\Bots\RunStatus;

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

    public function start(): void
    {
        if ($this->activeRun() !== null) {
            return;
        }

        $this->runs()->create();
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
