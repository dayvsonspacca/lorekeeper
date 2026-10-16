<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $bot_run_id
 * @property string $name
 * @property string $summary
 * @property null|string $raw
 * @property Carbon $created_at
 */
#[Fillable(['name', 'summary', 'raw'])]
final class BotRunEvent extends Model
{
    public const string UNRECOGNIZED = 'Unrecognized';

    public const null UPDATED_AT = null;
}
