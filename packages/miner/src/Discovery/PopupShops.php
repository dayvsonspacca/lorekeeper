<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Discovery;

use Lorekeeper\Core\World\Shop\ShopId;

final readonly class PopupShops
{
    /** @return list<ShopId> */
    public static function in(string $code): array
    {
        $shops = [];
        $blocks = preg_split('/internal function __setProp_/', $code);
        foreach ($blocks === false ? [] : $blocks as $block) {
            $shop = self::shopIn($block);
            if ($shop === null) {
                continue;
            }

            $shops[$shop->value] = $shop;
        }

        $requests = [];
        preg_match_all('/sendLoadShopRequest\((\d+)\)/', $code, $requests);
        foreach ($requests[1] as $id) {
            if ((int) $id < 1) {
                continue;
            }

            $shops[(int) $id] = new ShopId((int) $id);
        }

        return array_values($shops);
    }

    private static function shopIn(string $block): ?ShopId
    {
        if (!str_contains($block, 'strAction = "Item Shop"')) {
            return null;
        }

        $match = [];
        preg_match('/intID\s*=\s*(\d+)/', $block, $match);
        $id = (int) ($match[1] ?? 0);

        return $id > 0 ? new ShopId($id) : null;
    }
}
