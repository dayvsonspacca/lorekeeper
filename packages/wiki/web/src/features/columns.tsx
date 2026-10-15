import { Crown } from 'lucide-react'
import type { Column } from '@/components/data-table'
import { JoinButton } from '@/components/join-button'
import { Badge } from '@/components/ui/badge'
import type { AtlasNode, KindKey, ListingRow } from '@/lib/api'
import { first, price } from '@/lib/facts'
import { formatNumber } from '@/lib/format'

export type Row = ListingRow & { kind: KindKey; map?: AtlasNode }

const value = (row: Row, type: string) => first(row.facts, type)
const count = (row: Row, type: string) => row.facts[type]?.length ?? 0
const numberOr = (v: unknown, fallback = -1) => (typeof v === 'number' ? v : fallback)
const dash = <span className="text-muted-foreground/50">—</span>
const n = (v: unknown) => (typeof v === 'number' ? <span className="tabular-nums">{formatNumber(v)}</span> : dash)
const t = (v: unknown) => (v === undefined || v === null ? dash : String(v))

export const displayName = (row: Row) => (row.kind === 'map' ? (row.map?.title ?? row.name) : row.name.startsWith('#') ? `Unknown item ${row.name}` : row.name)

const name: Column<Row> = {
  key: 'name',
  header: 'Name',
  cell: (row) => (
    <span className={row.name.startsWith('#') ? 'font-medium text-muted-foreground' : 'font-medium'}>{displayName(row)}</span>
  ),
  sort: (row) => displayName(row),
}

export const columns: Record<KindKey, Column<Row>[]> = {
  map: [
    {
      ...name,
      cell: (row) => (
        <span className="flex items-center gap-2 font-medium">
          {displayName(row)}
          {row.map?.memberOnly && <Crown className="size-3.5 text-amber-500" />}
        </span>
      ),
    },
    { key: 'join', header: 'Join', cell: (r) => <JoinButton map={r.id} />, className: 'w-44' },
    { key: 'region', header: 'Region', cell: (r) => t(r.map?.regions[0]), sort: (r) => r.map?.regions[0] ?? '~', className: 'w-56' },
    {
      key: 'level',
      header: 'Level',
      cell: (r) => (r.map?.minLevel === null || r.map?.minLevel === undefined ? dash : r.map.minLevel === r.map.maxLevel ? r.map.minLevel : `${r.map.minLevel}–${r.map.maxLevel}`),
      sort: (r) => r.map?.minLevel ?? -1,
      className: 'w-24',
    },
    { key: 'monsters', header: 'Monsters', cell: (r) => n(count(r, 'map.monster') || undefined), sort: (r) => count(r, 'map.monster'), className: 'w-28' },
  ],
  monster: [
    name,
    { key: 'level', header: 'Level', cell: (r) => n(value(r, 'monster.level')), sort: (r) => numberOr(value(r, 'monster.level')), className: 'w-24' },
    { key: 'hp', header: 'Health', cell: (r) => n(value(r, 'attribute.health')), sort: (r) => numberOr(value(r, 'attribute.health')), className: 'w-28' },
    { key: 'race', header: 'Race', cell: (r) => t(value(r, 'monster.race')), sort: (r) => String(value(r, 'monster.race') ?? '~'), className: 'w-32' },
  ],
  item: [
    name,
    { key: 'type', header: 'Type', cell: (r) => t(value(r, 'item.type')), sort: (r) => String(value(r, 'item.type') ?? '~'), className: 'w-36' },
    {
      key: 'price',
      header: 'Price',
      cell: (r) => {
        const p = value(r, 'item.price')
        if (p === undefined) return dash
        const { amount, currency } = price(p)
        return (
          <span className="tabular-nums">
            {formatNumber(amount)} <span className="text-muted-foreground">{currency}</span>
          </span>
        )
      },
      sort: (r) => (value(r, 'item.price') === undefined ? -1 : price(value(r, 'item.price')).amount),
      className: 'w-36',
    },
    { key: 'member', header: '', cell: (r) => (value(r, 'access.member_only') !== undefined ? <Crown className="size-3.5 text-amber-500" /> : null), className: 'w-10' },
  ],
  quest: [
    name,
    { key: 'gold', header: 'Gold', cell: (r) => n(value(r, 'quest.reward.gold')), sort: (r) => numberOr(value(r, 'quest.reward.gold')), className: 'w-24' },
    { key: 'xp', header: 'Experience', cell: (r) => n(value(r, 'quest.reward.experience')), sort: (r) => numberOr(value(r, 'quest.reward.experience')), className: 'w-28' },
    { key: 'items', header: 'Item rewards', cell: (r) => n(count(r, 'quest.reward.item') || undefined), sort: (r) => count(r, 'quest.reward.item'), className: 'w-32' },
  ],
  npc: [
    name,
    {
      key: 'map',
      header: 'Map',
      cell: (r) => <JoinButton map={r.id.split('/')[0]} />,
      sort: (r) => r.id,
      className: 'w-52',
    },
    { key: 'quests', header: 'Quests', cell: (r) => n(count(r, 'npc.quest') || undefined), sort: (r) => count(r, 'npc.quest'), className: 'w-24' },
    { key: 'shops', header: 'Shops', cell: (r) => n(count(r, 'npc.shop') || undefined), sort: (r) => count(r, 'npc.shop'), className: 'w-24' },
  ],
  class_name: [
    name,
    {
      key: 'category',
      header: 'Category',
      cell: (r) => {
        const c = value(r, 'class.category')
        return c === undefined ? dash : <Badge variant="secondary">{String(c)}</Badge>
      },
      sort: (r) => String(value(r, 'class.category') ?? '~'),
      className: 'w-32',
    },
  ],
  region: [name, { key: 'maps', header: 'Maps', cell: (r) => n(count(r, 'region.map')), sort: (r) => count(r, 'region.map'), className: 'w-24' }],
  faction: [name],
}

export const facets: Partial<Record<KindKey, { type: string; label: string }[]>> = {
  item: [{ type: 'item.type', label: 'Type' }],
  monster: [{ type: 'monster.race', label: 'Race' }],
  class_name: [{ type: 'class.category', label: 'Category' }],
}
