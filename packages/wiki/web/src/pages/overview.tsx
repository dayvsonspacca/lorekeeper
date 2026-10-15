import { ArrowRight, Map as MapIcon, Search } from 'lucide-react'
import { Link } from 'react-router-dom'
import { SubjectLink } from '@/components/subject-link'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Progress } from '@/components/ui/progress'
import { type KindKey, useAtlas, useListing, useOverview } from '@/lib/api'
import { itemCategories } from '@/lib/categories'
import { first } from '@/lib/facts'
import { formatNumber, timeAgo } from '@/lib/format'
import { kindByKey } from '@/lib/kinds'

const world: KindKey[] = ['region', 'map', 'monster', 'npc', 'shop', 'quest', 'faction', 'class_name']

export function OverviewPage() {
  const { data: overview } = useOverview()
  const { data: atlas } = useAtlas()
  const { data: items } = useListing('item')

  const known = atlas?.nodes.length ?? 0
  const visited = atlas?.nodes.filter((n) => n.visited).length ?? 0
  const fresh = (overview?.newPages ?? []).slice(0, 10)
  const openSearch = () => window.dispatchEvent(new KeyboardEvent('keydown', { key: 'k', ctrlKey: true }))

  return (
    <div className="space-y-10">
      <section className="relative overflow-hidden rounded-2xl border bg-gradient-to-br from-muted/60 via-background to-background px-6 py-12 md:px-12">
        <div className="max-w-2xl space-y-4">
          <p className="text-sm font-medium text-muted-foreground">AdventureQuest Worlds</p>
          <h1 className="text-4xl font-semibold tracking-tight md:text-5xl">The living wiki of Lore</h1>
          <p className="text-lg text-muted-foreground">Maps, monsters, items and quests — documented straight from the game and kept up to date automatically.</p>
          <button
            onClick={openSearch}
            className="mt-2 flex h-12 w-full max-w-lg items-center gap-3 rounded-xl border bg-background px-4 text-left text-muted-foreground shadow-sm transition-colors hover:border-foreground/20"
          >
            <Search className="size-5" />
            Search {overview ? formatNumber(Object.values(overview.counts).reduce((a, b) => a + b, 0)) : ''} pages…
            <kbd className="ml-auto rounded border px-1.5 font-mono text-xs">Ctrl K</kbd>
          </button>
        </div>
        <MapIcon className="pointer-events-none absolute -right-10 -bottom-16 size-72 text-foreground/[0.04]" />
      </section>

      <section className="space-y-4">
        <h2 className="text-xl font-semibold tracking-tight">Explore the world</h2>
        <div className="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-6">
          {world.map((key) => {
            const kind = kindByKey(key)!
            return (
              <Link key={key} to={`/${key}`} className="group">
                <Card className="h-full gap-2 py-5 transition-all group-hover:-translate-y-0.5 group-hover:shadow-md">
                  <CardContent className="space-y-3 px-5">
                    <kind.icon className="size-5 text-muted-foreground" />
                    <div>
                      <div className="font-medium">{kind.plural}</div>
                      <div className="text-sm text-muted-foreground">{formatNumber(overview?.counts[key] ?? 0)} pages</div>
                    </div>
                  </CardContent>
                </Card>
              </Link>
            )
          })}
        </div>
      </section>

      <section className="space-y-4">
        <h2 className="text-xl font-semibold tracking-tight">Browse items</h2>
        <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
          {itemCategories.map((category) => {
            const total = items?.rows.filter((row) => category.types.includes(String(first(row.facts, 'item.type')))).length ?? 0
            return (
              <Link
                key={category.key}
                to={`/item?category=${category.key}`}
                className="flex items-center justify-between rounded-lg border px-4 py-3 text-sm transition-colors hover:bg-muted/50"
              >
                <span className="font-medium">{category.label}</span>
                <span className="tabular-nums text-muted-foreground">{total}</span>
              </Link>
            )
          })}
        </div>
      </section>

      <div className="grid gap-4 lg:grid-cols-3">
        <Card className="lg:col-span-2">
          <CardHeader>
            <CardTitle>New in the wiki</CardTitle>
            <CardDescription>Pages added most recently</CardDescription>
          </CardHeader>
          <CardContent>
            <ul className="divide-y">
              {fresh.map(({ subject, added }) => (
                <li key={`${subject.kind}/${subject.id}`} className="flex items-center justify-between gap-3 py-2.5">
                  <SubjectLink subject={subject} icon />
                  <span className="flex items-center gap-3 text-xs text-muted-foreground">
                    {kindByKey(subject.kind)?.singular}
                    <span>{timeAgo(added)}</span>
                  </span>
                </li>
              ))}
            </ul>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>World map</CardTitle>
            <CardDescription>How much of Lore has been explored</CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div>
              <div className="text-3xl font-semibold tabular-nums">{known === 0 ? 0 : Math.round((visited / known) * 100)}%</div>
              <div className="text-sm text-muted-foreground">{visited} of {known} maps explored</div>
            </div>
            <Progress value={known === 0 ? 0 : (visited / known) * 100} />
            <p className="text-sm text-muted-foreground">
              {atlas?.nodes.filter((n) => n.mapId === null).length ?? 0} hidden maps were found that are not on the in-game travel map.
            </p>
            <Link to="/atlas" className="inline-flex items-center gap-1 text-sm font-medium hover:underline">
              Open the world map <ArrowRight className="size-3.5" />
            </Link>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}

