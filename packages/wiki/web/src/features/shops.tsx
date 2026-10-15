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

export function ShopItemsTable({ items }: { items: SubjectRef[] }) {
  const { data } = useListing('item')
  const rows = new Map((data?.rows ?? []).map((row) => [row.id, row]))

  return (
    <div className="-mx-6">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead className="pl-6">Item</TableHead>
            <TableHead className="w-36">Type</TableHead>
            <TableHead className="w-40 pr-6 text-right">Price</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {items.map((item) => {
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
                  {cost === undefined ? (
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

