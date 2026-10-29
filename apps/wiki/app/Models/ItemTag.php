<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lorekeeper\Core\Item\ItemTag as Tag;

/**
 * @property int $id
 * @property int $item_id
 * @property Tag $tag
 * @property Item $item
 */
#[Fillable(['item_id', 'tag'])]
final class ItemTag extends Model
{
    public const null UPDATED_AT = null;

    /** @return BelongsTo<Item, $this> */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['tag' => Tag::class];
    }
}
