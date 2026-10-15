import { useParams } from 'react-router-dom'
import { Skeleton } from '@/components/ui/skeleton'
import { type Detail, NotFoundError, useDetail } from '@/lib/api'
import { NotFound } from '@/pages/not-found'
import { ClassPage } from './class'
import { FactionPage } from './faction'
import { ItemPage } from './item'
import { MapPage } from './map'
import { MonsterPage } from './monster'
import { NpcPage } from './npc'
import { QuestPage } from './quest'
import { RegionPage } from './region'
import { ShopPage } from './shop'

const pages: Record<string, (props: { detail: Detail }) => React.ReactNode> = {
  map: MapPage,
  monster: MonsterPage,
  item: ItemPage,
  quest: QuestPage,
  npc: NpcPage,
  shop: ShopPage,
  region: RegionPage,
  class_name: ClassPage,
  faction: FactionPage,
}

export function SubjectPage() {
  const { kind = '', id = '' } = useParams()
  const { data, isLoading, error } = useDetail(kind, id)
  const Page = pages[kind]

  if (!Page || error instanceof NotFoundError) return <NotFound />
  if (isLoading || !data) {
    return (
      <div className="space-y-6">
        <Skeleton className="h-4 w-48" />
        <Skeleton className="h-10 w-72" />
        <div className="grid gap-4 lg:grid-cols-3">
          <Skeleton className="h-64 lg:col-span-2" />
          <Skeleton className="h-40" />
        </div>
      </div>
    )
  }

  return <Page detail={data} />
}
