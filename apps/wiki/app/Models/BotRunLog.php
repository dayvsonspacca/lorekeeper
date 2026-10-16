<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Lorekeeper\Apps\Wiki\Bots\LogLevel;

/**
 * @property int $id
 * @property int $bot_run_id
 * @property string $message
 * @property LogLevel $level
 * @property Carbon $created_at
 */
#[Fillable(['message', 'level'])]
final class BotRunLog extends Model
{
    public const null UPDATED_AT = null;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['level' => LogLevel::class];
    }
}
