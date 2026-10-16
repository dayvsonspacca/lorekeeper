<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Lorekeeper\Core\Player\PlayerName;

/**
 * @property int $id
 * @property string $name
 * @property null|int $aqw_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Fillable(['name', 'aqw_id'])]
final class Player extends Model
{
    public static function saw(PlayerName $name): self
    {
        $player = self::query()->createOrFirst(['name' => $name->value]);

        if ($player->name !== $name->value && $name->value !== mb_strtolower($name->value)) {
            $player->update(['name' => $name->value]);
        }

        return $player;
    }
}
