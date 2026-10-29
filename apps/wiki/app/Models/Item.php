<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $aqw_id
 * @property null|string $name
 * @property null|Armor $armor
 * @property null|Weapon $weapon
 * @property null|Helm $helm
 * @property null|Cape $cape
 * @property null|Pet $pet
 * @property null|GroundItem $groundItem
 * @property null|ClassItem $classItem
 * @property null|HouseItem $houseItem
 * @property null|Boost $boost
 * @property null|Resource $resource
 *
 * @mago-expect lint:too-many-methods
 */
#[Fillable(['aqw_id', 'name'])]
final class Item extends Model
{
    /** @return HasMany<ItemTag, $this> */
    public function tags(): HasMany
    {
        return $this->hasMany(ItemTag::class);
    }

    /** @return HasOne<Armor, $this> */
    public function armor(): HasOne
    {
        return $this->hasOne(Armor::class);
    }

    /** @return HasOne<Weapon, $this> */
    public function weapon(): HasOne
    {
        return $this->hasOne(Weapon::class);
    }

    /** @return HasOne<Helm, $this> */
    public function helm(): HasOne
    {
        return $this->hasOne(Helm::class);
    }

    /** @return HasOne<Cape, $this> */
    public function cape(): HasOne
    {
        return $this->hasOne(Cape::class);
    }

    /** @return HasOne<Pet, $this> */
    public function pet(): HasOne
    {
        return $this->hasOne(Pet::class);
    }

    /** @return HasOne<GroundItem, $this> */
    public function groundItem(): HasOne
    {
        return $this->hasOne(GroundItem::class);
    }

    /** @return HasOne<ClassItem, $this> */
    public function classItem(): HasOne
    {
        return $this->hasOne(ClassItem::class);
    }

    /** @return HasOne<HouseItem, $this> */
    public function houseItem(): HasOne
    {
        return $this->hasOne(HouseItem::class);
    }

    /** @return HasOne<Boost, $this> */
    public function boost(): HasOne
    {
        return $this->hasOne(Boost::class);
    }

    /** @return HasOne<Resource, $this> */
    public function resource(): HasOne
    {
        return $this->hasOne(Resource::class);
    }
}
