import { MapPin, ScrollText, Store } from 'lucide-react'
import { Link } from 'react-router-dom'
import { JoinButton } from '@/components/join-button'
import { SubjectLink } from '@/components/subject-link'
import { Badge } from '@/components/ui/badge'
import { type SubjectRef, subjectPath, useAtlas, useListing } from '@/lib/api'

export function useMapTitles() {
  const { data: atlas } = useAtlas()
  return (id: string) => atlas?.nodes.find((node) => node.id === id)?.title ?? id
}

export function useNpcMaps() {
  const { data: maps } = useListing('map')
  const byNpc = new Map<string, SubjectRef[]>()
  for (const row of maps?.rows ?? []) {
    for (const npc of row.facts['map.npc'] ?? []) {
      const list = byNpc.get(String(npc)) ?? []
      list.push({ kind: 'map', id: row.id, name: row.name })
      byNpc.set(String(npc), list)
    }
  }
  return (npc: string) => byNpc.get(npc) ?? []
}

export function MapLinks({ maps }: { maps: SubjectRef[] }) {
  const titleOf = useMapTitles()
  return (
    <>
      {maps.map((map) => (
        <Link key={map.id} to={subjectPath(map)} className="inline-flex items-center gap-1 hover:text-foreground">
          <MapPin className="size-3.5" /> {titleOf(map.id)}
        </Link>
      ))}
    </>
  )
}

export function NpcCards({ npcs }: { npcs: SubjectRef[] }) {
  const { data } = useListing('npc')
  const rows = new Map((data?.rows ?? []).map((row) => [row.id, row]))

  return (
    <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
      {npcs.map((npc) => {
        const facts = rows.get(npc.id)?.facts ?? {}
        const title = facts['npc.title']?.[0]
        const quests = facts['npc.quest']?.length ?? 0
        const shops = facts['npc.shop']?.length ?? 0
        return (
          <Link key={npc.id} to={subjectPath(npc)} className="flex items-center justify-between gap-3 rounded-lg border px-3 py-2.5 transition-colors hover:bg-accent">
            <span className="min-w-0">
              <span className="block truncate font-medium">{npc.name}</span>
              {title !== undefined && <span className="block truncate text-xs text-muted-foreground">{String(title)}</span>}
            </span>
            <span className="flex shrink-0 gap-1.5">
              {quests > 0 && <Badge variant="secondary" className="gap-1 font-normal"><ScrollText className="size-3" />{quests}</Badge>}
              {shops > 0 && <Badge variant="outline" className="gap-1 font-normal"><Store className="size-3" />{shops}</Badge>}
            </span>
          </Link>
        )
      })}
    </div>
  )
}

export function WhereToGet({ npcs }: { npcs: SubjectRef[] }) {
  const mapsOf = useNpcMaps()
  const titleOf = useMapTitles()

  return (
    <ul className="divide-y rounded-lg border">
      {npcs.flatMap((npc) => {
        const maps = mapsOf(npc.id)
        return (maps.length === 0 ? [undefined] : maps).map((map) => (
          <li key={`${npc.id}/${map?.id ?? ''}`} className="flex flex-wrap items-center justify-between gap-3 px-4 py-2.5">
            <span className="flex flex-wrap items-center gap-1.5">
              Talk to <SubjectLink subject={npc} icon />
              {map && (
                <>
                  in <SubjectLink subject={{ ...map, name: titleOf(map.id) }} icon />
                </>
              )}
            </span>
            {map && <JoinButton map={map.id} />}
          </li>
        ))
      })}
    </ul>
  )
}
