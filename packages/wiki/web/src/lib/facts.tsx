import type { ReactNode } from 'react'
import { SubjectLink } from '@/components/subject-link'
import { Badge } from '@/components/ui/badge'
import type { SubjectRef } from './api'
import { formatNumber } from './format'

export type Section = 'details' | 'monsters' | 'npcs' | 'quests' | 'links' | 'maps' | 'turn-in' | 'requirements' | 'rewards' | 'status'

interface FactType {
  label: string
  section: Section
  render: (value: unknown, ref: SubjectRef | null) => ReactNode
}

type Obj = Record<string, unknown>
const obj = (value: unknown): Obj => (typeof value === 'object' && value !== null ? (value as Obj) : {})
const num = (value: unknown): number => (typeof value === 'number' ? value : Number(value) || 0)
const str = (value: unknown): string => (value === null || value === undefined ? '' : String(value))
const link = (ref: SubjectRef | null, fallback: string): ReactNode => (ref ? <SubjectLink subject={ref} /> : fallback)

const text = (label: string, section: Section = 'details'): FactType => ({ label, section, render: (v) => str(v) })
const numeric = (label: string, suffix = ''): FactType => ({ label, section: 'details', render: (v) => `${formatNumber(num(v))}${suffix}` })
const code = (label: string): FactType => ({
  label,
  section: 'details',
  render: (v) => <code className="rounded bg-muted px-1.5 py-0.5 font-mono text-xs">{str(v)}</code>,
})

export const price = (value: unknown) => {
  const { currency, amount } = obj(value)
  return { amount: num(amount), currency: currency === 'gold' ? 'gold' : 'AC' }
}

export const factTypes: Record<string, FactType> = {
  'item.name': text('Name'),
  'item.description': text('Description'),
  'item.type': text('Type'),
  'item.slot': text('Slot'),
  'item.price': {
    label: 'Price',
    section: 'details',
    render: (v) => {
      const p = price(v)
      return (
        <span className="inline-flex items-center gap-2">
          {formatNumber(p.amount)} {p.currency}
          <Badge variant="outline" className="font-normal text-muted-foreground">sells for {formatNumber(Math.floor(p.amount / 4))}</Badge>
        </span>
      )
    },
  },
  'asset.gamefile': code('File'),
  'asset.linkage': code('Linkage'),
  'access.member_only': { label: 'Member only', section: 'status', render: () => <Badge>Members only</Badge> },
  'attribute.health': numeric('Health'),
  'attribute.mana': numeric('Mana'),
  'monster.name': text('Name'),
  'monster.level': text('Level'),
  'monster.race': text('Race'),
  'map.id': text('Map ID'),
  'map.monster': { label: 'Monsters', section: 'monsters', render: (v, ref) => link(ref, `#${str(v)}`) },
  'map.link': { label: 'Leads to', section: 'links', render: (v, ref) => link(ref, str(v)) },
  'map.npc': { label: 'NPCs', section: 'npcs', render: (v, ref) => link(ref, str(v)) },
  'npc.quest': { label: 'Quests', section: 'quests', render: (v, ref) => link(ref, `quest #${str(v)}`) },
  'npc.shop': { label: 'Shops', section: 'details', render: (v, ref) => link(ref, `Shop #${str(v)}`) },
  'shop.name': text('Name'),
  'shop.item': { label: 'Items', section: 'details', render: (v, ref) => link(ref, `item #${str(v)}`) },
  'map.unavailable': {
    label: 'Unavailable',
    section: 'status',
    render: () => <Badge variant="destructive">The server said this map is not available</Badge>,
  },
  'faction.name': text('Name'),
  'region.name': text('Name'),
  'region.map': {
    label: 'Maps',
    section: 'maps',
    render: (v, ref) => {
      const m = obj(v)
      const levels = m.minLevel === m.maxLevel ? `Lv ${str(m.minLevel)}` : `Lv ${str(m.minLevel)}–${str(m.maxLevel)}`
      return (
        <div className="flex flex-col gap-0.5">
          <span className="flex items-center gap-2">
            {link(ref, str(m.map))}
            <span className="text-muted-foreground">{str(m.title)}</span>
            <Badge variant="secondary" className="font-normal">{levels}</Badge>
          </span>
          {m.description ? <span className="text-sm text-muted-foreground">{str(m.description)}</span> : null}
        </div>
      )
    },
  },
  'quest.name': text('Name'),
  'quest.description': {
    label: 'Description',
    section: 'details',
    render: (v) => {
      const d = obj(v)
      return (
        <div className="space-y-2">
          <p>{str(d.text)}</p>
          {d.endText ? <p className="border-l-2 pl-3 text-sm italic text-muted-foreground">{str(d.endText)}</p> : null}
        </div>
      )
    },
  },
  'quest.turn_in': {
    label: 'Turn in',
    section: 'turn-in',
    render: (v, ref) => (
      <span>
        <span className="tabular-nums text-muted-foreground">{str(obj(v).quantity)}×</span> {link(ref, `#${str(obj(v).item)}`)}
      </span>
    ),
  },
  'quest.requirement.player_level': { label: 'Requirements', section: 'requirements', render: (v) => `Level ${str(v)}` },
  'quest.requirement.member': { label: 'Requirements', section: 'requirements', render: () => 'Membership' },
  'quest.requirement.previous_quest': {
    label: 'Requirements',
    section: 'requirements',
    render: (v, ref) => <span>Complete {link(ref, `quest #${str(v)}`)}</span>,
  },
  'quest.requirement.item': { label: 'Requirements', section: 'requirements', render: (v, ref) => <span>Have {link(ref, `item #${str(v)}`)}</span> },
  'quest.requirement.class_rank': {
    label: 'Requirements',
    section: 'requirements',
    render: (v) => `Rank ${str(obj(v).rank)} in class #${str(obj(v).class)}`,
  },
  'quest.requirement.faction_rank': {
    label: 'Requirements',
    section: 'requirements',
    render: (v, ref) => <span>Rank {str(obj(v).rank)} with {link(ref, `faction #${str(obj(v).faction)}`)}</span>,
  },
  'quest.reward.gold': { label: 'Rewards', section: 'rewards', render: (v) => `${formatNumber(num(v))} gold` },
  'quest.reward.experience': { label: 'Rewards', section: 'rewards', render: (v) => `${formatNumber(num(v))} experience` },
  'quest.reward.reputation': {
    label: 'Rewards',
    section: 'rewards',
    render: (v, ref) => <span>{formatNumber(num(obj(v).amount))} reputation with {link(ref, `#${str(obj(v).faction)}`)}</span>,
  },
  'quest.reward.item': {
    label: 'Rewards',
    section: 'rewards',
    render: (v, ref) => {
      const r = obj(v)
      const rate = num(r.dropRate)
      return (
        <span className="inline-flex items-center gap-2">
          <span className="tabular-nums text-muted-foreground">{str(r.quantity)}×</span>
          {link(ref, `#${str(r.item)}`)}
          {rate < 100 && <Badge variant="outline" className="font-normal">{rate}% drop</Badge>}
        </span>
      )
    },
  },
  'class.category': text('Category'),
  'class.description': text('Description'),
  'class.mana_regeneration': text('Mana regeneration'),
}

export const factType = (type: string): FactType => factTypes[type] ?? { label: type, section: 'details', render: (v) => str(v) }

export const referenceLabels: Record<string, string> = {
  'map.monster': 'Found in maps',
  'map.link': 'Reached from',
  'map.npc': 'Found in maps',
  'npc.quest': 'Offered by',
  'npc.shop': 'Run by',
  'shop.item': 'Sold at',
  'region.map': 'Listed in regions',
  'quest.turn_in': 'Turned in for',
  'quest.reward.item': 'Rewarded by',
  'quest.requirement.item': 'Required by',
  'quest.requirement.previous_quest': 'Unlocks',
  'quest.reward.reputation': 'Reputation from',
  'quest.requirement.faction_rank': 'Rank required by',
}


export const first = (facts: Record<string, unknown[]>, type: string): unknown => facts[type]?.[0]
