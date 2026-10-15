import { Crown, Store } from 'lucide-react'
import { Link } from 'react-router-dom'
import { SubjectLink } from '@/components/subject-link'
import { Badge } from '@/components/ui/badge'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { type SubjectRef, subjectPath, useListing } from '@/lib/api'
import { first, price } from '@/lib/facts'
import { formatNumber } from '@/lib/format'


export function useShopKeepers() {
  const { data: npcs } = useListing('npc')
  const byShop = new Map<string, SubjectRef[]>()
  for (const row of npcs?.rows ?? []) {
    for (const shop of row.facts['npc.shop'] ?? []) {
      const list = byShop.get(String(shop)) ?? []
      list.push({ kind: 'npc', id: row.id, name: row.name })
      byShop.set(String(shop), list)
    }
  }
  return (shop: string) => byShop.get(shop) ?? []
}

export interface Offer {
  item: string
  costs: { item: string; quantity: number }[]
}

export function offerOf(value: unknown): Offer {
  if (typeof value === 'number' || typeof value === 'string') return { item: String(value), costs: [] }
  const offer = value as { item: number; costs?: { item: number; quantity: number }[] }
  return { item: String(offer.item), costs: (offer.costs ?? []).map((cost) => ({ item: String(cost.item), quantity: cost.quantity })) }
}

export function useItemNames() {
  const { data } = useListing('item')
  const names = new Map((data?.rows ?? []).map((row) => [row.id, row.name]))
  return (id: string) => names.get(id) ?? `#${id}`
}

export function Cost({ costs }: { costs: Offer['costs'] }) {
  const nameOf = useItemNames()
  return (
    <span className="inline-flex flex-wrap items-center justify-end gap-x-3 gap-y-1">
      {costs.map((cost) => (
        <span key={cost.item} className="inline-flex items-center gap-1">
          <span className="tabular-nums text-muted-foreground">{cost.quantity}×</span>
          <SubjectLink subject={{ kind: 'item', id: cost.item, name: nameOf(cost.item) }} className="font-normal" />
        </span>
      ))}
    </span>
  )
}

export function ShopItemsTable({ items }: { items: { ref: SubjectRef; offer: Offer }[] }) {
  const { data } = useListing('item')
  const rows = new Map((data?.rows ?? []).map((row) => [row.id, row]))
  const merge = items.some(({ offer }) => offer.costs.length > 0)

  return (
    <div className="-mx-6">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead className="pl-6">Item</TableHead>
            <TableHead className="w-36">Type</TableHead>
            <TableHead className={merge ? 'pr-6 text-right' : 'w-40 pr-6 text-right'}>{merge ? 'Trade for' : 'Price'}</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {items.map(({ ref: item, offer }) => {
            const facts = rows.get(item.id)?.facts ?? {}
            const cost = first(facts, 'item.price')
            return (
              <TableRow key={item.id}>
                <TableCell className="pl-6">
                  <span className="flex items-center gap-2">
                    <SubjectLink subject={item} />
                    {first(facts, 'access.member_only') !== undefined && <Crown className="size-3.5 text-amber-500" />}
                  </span>
                </TableCell>
                <TableCell className="text-muted-foreground">{String(first(facts, 'item.type') ?? '—')}</TableCell>
                <TableCell className="pr-6 text-right tabular-nums">
                  {offer.costs.length > 0 ? (
                    <Cost costs={offer.costs} />
                  ) : cost === undefined ? (
                    <Badge variant="secondary" className="font-normal">Free</Badge>
                  ) : (
                    <span>
                      {formatNumber(price(cost).amount)} <span className="text-muted-foreground">{price(cost).currency}</span>
                    </span>
                  )}
                </TableCell>
              </TableRow>
            )
          })}
        </TableBody>
      </Table>
    </div>
  )
}

export function ShopCards({ shops }: { shops: { id: string; ref: SubjectRef | null }[] }) {
  const { data } = useListing('shop')
  const rows = new Map((data?.rows ?? []).map((row) => [row.id, row]))

  return (
    <div className="grid gap-2 sm:grid-cols-2">
      {shops.map((shop) => {
        const row = rows.get(shop.id)
        const items = row?.facts['shop.item']?.length ?? 0
        const known = shop.ref && !shop.ref.name.startsWith('#')
        return known && shop.ref ? (
          <Link key={shop.id} to={subjectPath(shop.ref)} className="flex items-center justify-between gap-3 rounded-lg border px-3 py-2.5 transition-colors hover:bg-accent">
            <span className="flex min-w-0 items-center gap-2 font-medium">
              <Store className="size-4 shrink-0 text-muted-foreground" />
              <span className="truncate">{shop.ref.name}</span>
              {row?.facts['access.member_only'] && <Crown className="size-3.5 shrink-0 text-amber-500" />}
            </span>
            <Badge variant="secondary" className="shrink-0 font-normal">{items === 1 ? '1 item' : `${items} items`}</Badge>
          </Link>
        ) : (
          <div key={shop.id} className="flex items-center gap-2 rounded-lg border border-dashed px-3 py-2.5 text-sm text-muted-foreground">
            <Store className="size-4" /> Shop not documented yet
          </div>
        )
      })}
    </div>
  )
}

