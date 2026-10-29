<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Facts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Lorekeeper\Apps\Wiki\Models\Boost;
use Lorekeeper\Apps\Wiki\Models\Item;
use Lorekeeper\Apps\Wiki\Models\ItemTag;
use Lorekeeper\Apps\Wiki\Models\Resource;
use Lorekeeper\Core\Item\ItemTag as Tag;

final class Ledger
{
    private const array STACKABLE = [Boost::class, Resource::class];

    /** @var array<int, array<string, mixed>> */
    private array $items = [];

    /** @var array<int, array<string, Tag>> */
    private array $tags = [];

    /** @var array<class-string<Model>, array<int, array<string, mixed>>> */
    private array $kinds = [];

    /** @var array<int, int> */
    private array $stacks = [];

    public function item(int $aqwId): void
    {
        $this->items[$aqwId] ??= [];
    }

    public function name(int $aqwId, string $name): void
    {
        $this->items[$aqwId] = [...($this->items[$aqwId] ?? []), 'name' => $name];
    }

    public function tag(int $aqwId, Tag $tag): void
    {
        $this->item($aqwId);
        $this->tags[$aqwId][$tag->name] = $tag;
    }

    /**
     * @param class-string<Model> $model
     * @param array<string, mixed> $attributes
     */
    public function kind(string $model, int $aqwId, array $attributes = []): void
    {
        $this->item($aqwId);
        $this->kinds[$model][$aqwId] = [...($this->kinds[$model][$aqwId] ?? []), ...$attributes];
    }

    public function stack(int $aqwId, int $stack): void
    {
        $this->item($aqwId);
        $this->stacks[$aqwId] = $stack;
    }

    public function write(): void
    {
        DB::transaction(function (): void {
            $this->writeItems();

            $ids = Item::query()->whereIn('aqw_id', array_keys($this->items))->pluck('id', 'aqw_id');

            ItemTag::query()->insertOrIgnore(array_merge(...array_map(
                static fn(int $aqwId, array $tags): array => array_map(static fn(Tag $tag): array => [
                    'item_id' => $ids[$aqwId],
                    'tag' => $tag->name,
                    'created_at' => now(),
                ], array_values($tags)),
                array_keys($this->tags),
                $this->tags,
            )));

            foreach ($this->kinds as $model => $rows) {
                foreach ($rows as $aqwId => $attributes) {
                    $stack = in_array($model, self::STACKABLE, strict: true) && array_key_exists($aqwId, $this->stacks)
                        ? ['stack' => $this->stacks[$aqwId]]
                        : [];
                    $this->writeKind($model, $ids[$aqwId], [...$attributes, ...$stack]);
                }
            }
        });
    }

    private function writeItems(): void
    {
        foreach ($this->items as $aqwId => $attributes) {
            if ($attributes === []) {
                Item::query()->insertOrIgnore(['aqw_id' => $aqwId, 'created_at' => now(), 'updated_at' => now()]);

                continue;
            }

            Item::query()->upsert([['aqw_id' => $aqwId, ...$attributes]], ['aqw_id'], array_keys($attributes));
        }
    }

    /**
     * @param class-string<Model> $model
     * @param array<string, mixed> $attributes
     */
    private function writeKind(string $model, int $itemId, array $attributes): void
    {
        if ($attributes === []) {
            $model::query()->insertOrIgnore(['item_id' => $itemId, 'created_at' => now(), 'updated_at' => now()]);

            return;
        }

        $model::query()->upsert([['item_id' => $itemId, ...$attributes]], ['item_id'], array_keys($attributes));
    }
}
