export interface ItemCategory {
  key: string
  label: string
  types: string[]
}

export const itemCategories: ItemCategory[] = [
  { key: 'weapons', label: 'Weapons', types: ['Axe', 'Bow', 'Dagger', 'Gauntlet', 'Gun', 'HandGun', 'Mace', 'Polearm', 'Rifle', 'Staff', 'Sword', 'Wand', 'Whip'] },
  { key: 'armors', label: 'Armors', types: ['Armor'] },
  { key: 'classes', label: 'Class items', types: ['Class'] },
  { key: 'helms', label: 'Helms', types: ['Helm'] },
  { key: 'capes', label: 'Capes', types: ['Cape'] },
  { key: 'necklaces', label: 'Necklaces', types: ['Necklace'] },
  { key: 'pets', label: 'Pets', types: ['Pet'] },
  { key: 'houses', label: 'Houses', types: ['House', 'Floor Item', 'Wall Item'] },
  { key: 'grounds', label: 'Grounds', types: ['Ground'] },
  { key: 'misc', label: 'Misc. items', types: ['Item', 'Resource', 'Quest Item', 'Misc'] },
]

export const categoryOf = (type: string | undefined): ItemCategory | undefined =>
  type === undefined ? undefined : itemCategories.find((category) => category.types.includes(type))
