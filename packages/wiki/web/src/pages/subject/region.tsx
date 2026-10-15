import { Crown } from 'lucide-react'
import { Link } from 'react-router-dom'
import { JoinButton } from '@/components/join-button'
import { Empty, Section, WikiPage } from '@/components/wiki-page'
import { Badge } from '@/components/ui/badge'
import { type Detail, subjectPath, useAtlas } from '@/lib/api'
import { page } from '@/lib/page'

interface Entry {
  map: string
  title: string
  description: string | null
  minLevel: number
  maxLevel: number
}

export function RegionPage({ detail }: { detail: Detail }) {
  const p = page(detail)
  const { data: atlas } = useAtlas()
  const maps = p.facts('region.map').map((f) => ({ entry: f.value as Entry, ref: f.ref }))

  return (
    <WikiPage
      crumbs={[{ label: 'Home', to: '/' }, { label: 'World' }, { label: 'Regions', to: '/region' }, { label: detail.subject.name }]}
      title={detail.subject.name}
      subtitle={<span>Region · {maps.length} maps</span>}
      updated={p.updated}
    >
      <Section title="Maps" description="Places listed in this region of the travel map">
        {maps.length === 0 ? (
          <Empty>No maps listed.</Empty>
        ) : (
          <div className="grid gap-3 sm:grid-cols-2">
            {maps.map(({ entry, ref }) => {
              const node = atlas?.nodes.find((n) => n.id === entry.map)
              return (
                <Link key={entry.map} to={ref ? subjectPath(ref) : '#'} className="group rounded-lg border p-4 transition-colors hover:border-foreground/25 hover:bg-muted/30">
                  <div className="flex items-start justify-between gap-2">
                    <div>
                      <div className="font-medium group-hover:underline">{entry.title}</div>
                      <div className="font-mono text-xs text-muted-foreground">{entry.map}</div>
                    </div>
                    <Badge variant="secondary" className="shrink-0">
                      {entry.minLevel === entry.maxLevel ? `Lv ${entry.minLevel}` : `Lv ${entry.minLevel}–${entry.maxLevel}`}
                    </Badge>
                  </div>
                  {entry.description && <p className="mt-2 line-clamp-2 text-sm text-muted-foreground">{entry.description}</p>}
                  <div className="mt-3 flex items-center gap-2">
                    <JoinButton map={entry.map} />
                    {node?.memberOnly && <Badge variant="outline" className="gap-1"><Crown className="size-3" /> Members</Badge>}
                  </div>
                </Link>
              )
            })}
          </div>
        )}
      </Section>
    </WikiPage>
  )
}
