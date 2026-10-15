import { Castle, Gem, Map, type LucideIcon, ScrollText, Shield, Skull, Swords, UserRound } from 'lucide-react'
import type { KindKey } from './api'

export interface KindMeta {
  key: KindKey
  singular: string
  plural: string
  icon: LucideIcon
  description: string
}

export const kinds: KindMeta[] = [
  { key: 'map', singular: 'Map', plural: 'Maps', icon: Map, description: 'Places you can /join' },
  { key: 'monster', singular: 'Monster', plural: 'Monsters', icon: Skull, description: 'Creatures seen in maps' },
  { key: 'item', singular: 'Item', plural: 'Items', icon: Gem, description: 'Gear, resources and quest items' },
  { key: 'quest', singular: 'Quest', plural: 'Quests', icon: ScrollText, description: 'Tasks with requirements and rewards' },
  { key: 'npc', singular: 'NPC', plural: 'NPCs', icon: UserRound, description: 'Characters who give quests and run shops' },
  { key: 'class_name', singular: 'Class', plural: 'Classes', icon: Swords, description: 'Melee and caster classes' },
  { key: 'region', singular: 'Region', plural: 'Regions', icon: Castle, description: 'Travel map regions' },
  { key: 'faction', singular: 'Faction', plural: 'Factions', icon: Shield, description: 'Reputation factions' },
]

export const kindByKey = (key: string): KindMeta | undefined => kinds.find((kind) => kind.key === key)
