# Lorekeeper Core

The game of AdventureQuest Worlds as plain PHP values: what you meet in it, named and checked.

## What it describes

- **Players**: their name, level, health and mana, the class they play with its points, rank and the skills they
  have unlocked, and their inventory.
- **Classes**: name, category and description, and the skills they give, each with its slot, what it aims at, its
  mana cost, its cooldown and how many targets it hits.
- **Items**, as what they are: weapons of every type, classes, armors, helms, capes, pets, necklaces and ground
  items, house items, resources, boosts and enhancements, with the slot each piece of equipment goes in.
- **Inventories**: what a player holds and how many of each, found by item.
- **Maps**: their name, file and the monsters spawned in them, each in its cell with its health, mana and whether it
  is idle, fighting or dead; the cells, pads and spots of a map, and a player as they stand in one.
- **Monsters**: their name, level, race and how they look.
- **Quests**: what it takes to accept one, from a level, a class or faction rank, membership, items or an earlier
  quest; what it asks you to bring; and what it gives, from gold, experience and reputation to items, guaranteed or
  by chance.
- **Shops**: shops that sell for gold or adventure coins, and merge shops that trade items for items.
- **Progression and currencies**: levels, experience, class points, reputation, ranks, gold and adventure coins.
- **Tags** for facts worth asking about: an item or a shop being for members only, a shop selling house items.

Every value checks what it is given: a level stays in its range, a quantity is never zero, health never goes above
its maximum.

## Example

```php
$healer = new ClassItem(new ItemId(15_651), new ItemName('Healer'));
$voucher = new Resource(new ItemId(4861), new ItemName('Voucher of Nulgath'));
$voucher->tags->add(ItemTag::MemberOnly);

$inventory = new PlayerInventory([new PlayerItem($healer), new PlayerItem($voucher)]);

$inventory->find(new ItemId(15_651))->unwrap()->item->slot();                        // EquipmentSlot::CharacterClass
$inventory->find(new ItemId(4861))->unwrap()->item->tags->has(ItemTag::MemberOnly);   // true

$vitals = new Vitals(
    new Gauge(new Health(189), new Health(390)),
    new Gauge(new Mana(87), new Mana(100)),
);
$vitals->alive();   // true
```
