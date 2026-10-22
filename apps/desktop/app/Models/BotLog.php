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

    public const int KEEP = 500;

    public const int EVERY = 100;

    public static function trim(int $account): void
    {
        $oldest = self::query()
            ->where('account_id', $account)
            ->latest('id')
            ->skip(self::KEEP - 1)
            ->value('id');
        if ($oldest === null) {
            return;
        }

        self::query()
            ->where('account_id', $account)
            ->where('id', '<', $oldest)
            ->delete();
    }

    public static function trimAll(): void
    {
        foreach (self::query()->distinct()->pluck('account_id') as $account) {
            self::trim($account);
        }
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['level' => LogLevel::class];
    }
}
