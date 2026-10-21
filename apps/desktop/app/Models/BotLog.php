<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Lorekeeper\Apps\Desktop\Bots\LogLevel;

/**
 * @property int $id
 * @property int $account_id
 * @property string $message
 * @property LogLevel $level
 * @property Carbon $created_at
 */
#[Fillable(['message', 'level'])]
final class BotLog extends Model
{
    public const null UPDATED_AT = null;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['level' => LogLevel::class];
    }
}
