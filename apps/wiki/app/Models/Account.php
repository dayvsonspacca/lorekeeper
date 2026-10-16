<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $password
 * @property null|string $server
 */
#[Fillable(['name', 'password', 'server'])]
#[Hidden(['password'])]
final class Account extends Model
{
    /** @return HasMany<BotRun, $this> */
    public function runs(): HasMany
    {
        return $this->hasMany(BotRun::class);
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
