<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lorekeeper\Core\Item\Equipment\Weapon\WeaponType;

/**
 * @property int $item_id
 * @property null|WeaponType $type
 * @property null|int $damage_variance
 * @property Item $item
 */
#[Fillable(['item_id', 'type', 'damage_variance'])]
final class Weapon extends Model
{
    public $incrementing = false;

    protected $primaryKey = 'item_id';

    /** @return BelongsTo<Item, $this> */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['type' => WeaponType::class];
    }
}
