<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Tests;

use Lorekeeper\Server\Message\JsonMessage;

trait GameMessages
{
    /** @param array<string, mixed> $data */
    private static function encoded(array $data): string
    {
        return (string) json_encode(['t' => 'xt', 'b' => ['r' => -1, 'o' => $data]]);
    }

    /** @param array<string, mixed> $data */
    private static function message(array $data): JsonMessage
    {
        return JsonMessage::from(self::encoded($data))->unwrap();
    }

    /**
     * @param list<array<string, mixed>> $items
     *
     * @return array<string, mixed>
     */
    private static function shop(array $items): array
    {
        return ['cmd' => 'loadShop', 'shopinfo' => ['ShopID' => 31, 'sName' => 'Battleon Armors', 'items' => $items]];
    }

    /** @return array<string, mixed> */
    private static function gladiator(): array
    {
        return [
            'ShopItemID' => 10_263,
            'ItemID' => 16_416,
            'sName' => 'Gladiator of Circumstance',
            'sDesc' => 'Ready your battle stance as the Gladiator of Circumstance!',
            'iLvl' => 1,
            'bTemp' => false,
            'bUpg' => false,
            'bCoins' => true,
            'sFile' => 'GladiatorOfCircumstance.swf',
            'sType' => 'Armor',
            'sES' => 'co',
        ];
    }

    /** @return array<string, mixed> */
    private static function pendant(): array
    {
        return [
            'ShopItemID' => 1630,
            'ItemID' => 783,
            'sName' => 'Ruby Pendant',
            'iLvl' => 1,
            'bTemp' => false,
            'bUpg' => false,
            'sType' => 'Necklace',
            'sES' => 'am',
        ];
    }
}
