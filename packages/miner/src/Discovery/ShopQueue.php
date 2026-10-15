<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Discovery;

use Lorekeeper\Core\Access\MemberOnly;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Npc\NpcId;
use Lorekeeper\Core\World\Npc\NpcShop;
use Lorekeeper\Core\World\Shop\ShopId;
use Lorekeeper\Core\World\Shop\ShopName;
use Lorekeeper\Persistence\Store\FactStore;
use Lorekeeper\Persistence\Store\StoredFact;

final readonly class ShopQueue
{
    public function __construct(
        private FactStore $store,
    ) {}

    /** @return array<string, non-empty-list<ShopId>> */
    public function toLoad(): array
    {
        $skip = self::subjectIds($this->store->withValue(ShopName::class), ShopId::class);
        $memberOnly = self::subjectIds($this->store->withValue(MemberOnly::class), MapName::class);

        $maps = [];
        foreach ($this->store->withValue(NpcShop::class) as $stored) {
            $npc = $stored->fact->subject;
            $shop = $stored->fact->value;
            if (
                !$npc instanceof NpcId
                || !$shop instanceof NpcShop
                || array_key_exists($npc->map->value, $memberOnly)
                || array_key_exists((string) $shop->shopId, $skip)
            ) {
                continue;
            }

            $skip[(string) $shop->shopId] = true;
            $maps[$npc->map->value][] = $shop->shopId;
        }

        return $maps;
    }

    /**
     * @param list<StoredFact> $facts
     * @param class-string $subjectClass
     *
     * @return array<string, true>
     */
    private static function subjectIds(array $facts, string $subjectClass): array
    {
        $ids = [];
        foreach ($facts as $stored) {
            if (!$stored->fact->subject instanceof $subjectClass) {
                continue;
            }

            $ids[(string) $stored->fact->subject] = true;
        }

        return $ids;
    }
}
