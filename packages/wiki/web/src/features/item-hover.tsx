import { Crown } from 'lucide-react'
import type { ReactNode } from 'react'
import { Badge } from '@/components/ui/badge'
import { HoverCard, HoverCardContent, HoverCardTrigger } from '@/components/ui/hover-card'
import { Skeleton } from '@/components/ui/skeleton'
import { ItemVisual, visualOf } from '@/features/flash-preview'
import { type SubjectRef, useDetail, useListing } from '@/lib/api'
import { first, price } from '@/lib/facts'
import { formatNumber } from '@/lib/format'
import { page } from '@/lib/page'

function ItemCard({ item }: { item: SubjectRef }) {
  const { data, isLoading } = useDetail('item', item.id)
  const { data: items } = useListing('item')
  const row = items?.rows.find((candidate) => candidate.id === item.id)
  const visual = row ? visualOf((type) => first(row.facts, type)) : undefined

  if (isLoading || !data) {
    return (
      <div className="space-y-3">
        <Skeleton className="h-5 w-40" />
        <Skeleton className="h-4 w-24" />
        {visual && <Skeleton className="aspect-4/3 w-full" />}
      </div>
    )
  }

  const p = page(data)
  const type = p.value<string>('item.type')
  const cost = p.value('item.price')
  const description = p.value<string>('item.description')

  return (
    <div className="space-y-3">
      <div className="space-y-1.5">
        <div className="flex items-start justify-between gap-2">
          <p className="font-semibold leading-tight">{data.subject.name}</p>
          {p.value('access.member_only') !== undefined && <Crown className="mt-0.5 size-4 shrink-0 text-amber-500" />}
        </div>
        <div className="flex flex-wrap items-center gap-1.5">
          {type && <Badge variant="secondary">{type}</Badge>}
          {cost !== undefined && (
            <Badge variant="outline" className="tabular-nums">
              {formatNumber(price(cost).amount)} {price(cost).currency}
            </Badge>
          )}
        </div>
      </div>
      {visual && <ItemVisual detail={data} compact />}
      {description && <p className="line-clamp-3 text-xs leading-relaxed text-muted-foreground">{description}</p>}
    </div>
  )
}

export function ItemHover({ item, children }: { item: SubjectRef; children: ReactNode }) {
  return (
    <HoverCard openDelay={250} closeDelay={100}>
      <HoverCardTrigger asChild>{children}</HoverCardTrigger>
      <HoverCardContent className="w-80" side="right" align="start" collisionPadding={16}>
        <ItemCard item={item} />
      </HoverCardContent>
    </HoverCard>
  )
}
