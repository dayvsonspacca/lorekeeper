<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $bot_run_id
 * @property string $message
 * @property Carbon $created_at
 */
#[Fillable(['message'])]
final class BotRunLog extends Model
{
    public const null UPDATED_AT = null;
}
