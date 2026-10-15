<?php

declare(strict_types=1);

namespace Lorekeeper\Persistence\Codec;

use Lorekeeper\Core\Access\MemberOnly;
use Lorekeeper\Core\Asset\Gamefile;
use Lorekeeper\Core\Currency\AdventureCoins;
use Lorekeeper\Core\Currency\Gold;
use Lorekeeper\Core\Item\EquipmentSlot;
use Lorekeeper\Core\Item\ItemPrice;
use Lorekeeper\Core\Item\ItemType;
use Lorekeeper\Persistence\Exception\UnknownTypeException;
use Psl\Type;

final readonly class ItemCodecs
{
    /** @return list<ValueCodec> */
    public static function all(): array
    {
        return [
            new StringCodec('item.type', ItemType::class),
            new CustomCodec(
                'item.slot',
                EquipmentSlot::class,
                static fn(object $v): string => Type\instance_of(EquipmentSlot::class)->assert($v)->name,
                self::decodeSlot(...),
            ),
            new CustomCodec(
                'asset.gamefile',
                Gamefile::class,
                static fn(object $v): string => Type\instance_of(Gamefile::class)->assert($v)->value,
                static fn(mixed $p): Gamefile => new Gamefile(Type\string()->assert($p)),
            ),
            new CustomCodec(
                'access.member_only',
                MemberOnly::class,
                static fn(object $v): bool => true,
                static fn(mixed $p): MemberOnly => new MemberOnly(),
            ),
            new CustomCodec(
                'item.price',
                ItemPrice::class,
                static function (object $v): array {
                    $amount = Type\instance_of(ItemPrice::class)->assert($v)->amount;

                    return ['currency' => $amount instanceof Gold ? 'gold' : 'ac', 'amount' => $amount->value];
                },
                static function (mixed $p): ItemPrice {
                    $p = Type\shape(['currency' => Type\string(), 'amount' => Type\uint()])->assert($p);

                    return new ItemPrice(
                        $p['currency'] === 'gold' ? new Gold($p['amount']) : new AdventureCoins($p['amount']),
                    );
                },
            ),
        ];
    }

    private static function decodeSlot(mixed $payload): EquipmentSlot
    {
        $name = Type\non_empty_string()->assert($payload);

        foreach (EquipmentSlot::cases() as $slot) {
            if ($slot->name === $name) {
                return $slot;
            }
        }

        throw new UnknownTypeException("Unknown equipment slot '{$name}'");
    }
}
