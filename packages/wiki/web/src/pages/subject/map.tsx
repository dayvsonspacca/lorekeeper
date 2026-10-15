import { AlertTriangle, Crown, MapPin, Skull, Swords, UserRound } from 'lucide-react'
import { Link } from 'react-router-dom'
import { JoinButton } from '@/components/join-button'
import { NpcCards } from '@/features/npcs'
import { SubjectLink } from '@/components/subject-link'
import { Alert, Badge } from '@/components/wiki-bits'
import { Empty, Section, StatTile, WikiPage } from '@/components/wiki-page'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { type Detail, subjectPath, useAtlas, useListing } from '@/lib/api'
import { first } from '@/lib/facts'
import { formatNumber } from '@/lib/format'
import { page } from '@/lib/page'

export function MapPage({ detail }: { detail: Detail }) {
  const p = page(detail)
  const { data: atlas } = useAtlas()
  const { data: monsters } = useListing('monster')
  const node = atlas?.nodes.find((n) => n.id === detail.subject.id)
  const titleOf = (id: string) => atlas?.nodes.find((n) => n.id === id)?.title ?? id
  const regions = p.pointing('region.map')
  const levels = node?.minLevel !== null && node?.minLevel !== undefined
    ? node.minLevel === node.maxLevel ? `${node.minLevel}` : `${node.minLevel}–${node.maxLevel}`
    : undefined
  const mapMonsters = p.linked('map.monster')
  const npcs = p.linked('map.npc')
  const prompts = new Map(p.facts('map.exit').flatMap((fact) => (fact.ref ? [[fact.ref.id, (fact.value as { prompt?: string | null }).prompt ?? undefined]] : [])))
  const leadsTo = p.linked('map.exit')
  const reachedFrom = p.pointing('map.exit').map((r) => r.subject)
  const { data: npcRows } = useListing('npc')
  const travelsOut = (npcRows?.rows ?? [])
    .filter((row) => row.id.startsWith(`${detail.subject.id}/`))
    .flatMap((row) => (row.facts['npc.travel'] ?? []).map((target) => ({ npc: { kind: 'npc', id: row.id, name: row.name }, map: String(target) })))
  const travelsIn = p.pointing('npc.travel').map((r) => r.subject)
  const byId = new Map((monsters?.rows ?? []).map((row) => [row.id, row]))

  return (
    <WikiPage
      crumbs={[{ label: 'Home', to: '/' }, { label: 'World' }, { label: 'Maps', to: '/map' }, { label: node?.title ?? detail.subject.name }]}
      title={node?.title ?? detail.subject.name}
      subtitle={
        <>
          {regions.map((r) => (
            <Link key={r.subject.id} to={subjectPath(r.subject)} className="inline-flex items-center gap-1 hover:text-foreground">
              <MapPin className="size-3.5" /> {r.subject.name}
            </Link>
          ))}
        </>
      }
      actions={<JoinButton map={detail.subject.id} className="h-9 px-3 text-sm" />}
      updated={p.updated}
    >
      {p.value('access.member_only') !== undefined && (
        <Alert icon={Crown} title="Members only">This map can only be joined by members (upgraded accounts).</Alert>
      )}
      {p.value('map.unavailable') !== undefined && (
        <Alert icon={AlertTriangle} title="Not available" tone="danger">The server reported this map as not available the last time we tried to join it.</Alert>
      )}

      <div className="grid grid-cols-2 gap-3 md:grid-cols-4">
        <StatTile icon={Swords} label="Recommended level" value={levels ?? '—'} />
        <StatTile icon={Skull} label="Monsters" value={mapMonsters.length} />
        <StatTile icon={UserRound} label="NPCs" value={npcs.length} />
        <StatTile label="Map ID" value={p.value<number>('map.id') ?? '—'} />
      </div>

      {node?.description && (
        <Section title="About">
          <p className="leading-relaxed">{node.description}</p>
        </Section>
      )}

      <Section title="Monsters" description="These are the monsters you can find here">
        {mapMonsters.length === 0 ? (
          <Empty>No monsters have been spotted here yet.</Empty>
        ) : (
          <div className="-mx-6">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead className="pl-6">Monster</TableHead>
                  <TableHead className="w-20">Level</TableHead>
                  <TableHead className="w-24">Health</TableHead>
                  <TableHead className="w-28 pr-6">Race</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {mapMonsters.map((monster) => {
                  const row = byId.get(monster.id)
                  const health = row ? first(row.facts, 'attribute.health') : undefined
                  return (
                    <TableRow key={monster.id}>
                      <TableCell className="pl-6"><SubjectLink subject={monster} /></TableCell>
                      <TableCell className="tabular-nums">{row ? String(first(row.facts, 'monster.level') ?? '—') : '—'}</TableCell>
                      <TableCell className="tabular-nums">{typeof health === 'number' ? formatNumber(health) : '—'}</TableCell>
                      <TableCell className="pr-6 text-muted-foreground">{row ? String(first(row.facts, 'monster.race') ?? '—') : '—'}</TableCell>
                    </TableRow>
                  )
                })}
              </TableBody>
            </Table>
          </div>
        )}
      </Section>

      {npcs.length > 0 && (
        <Section title="NPCs" description="People you can talk to here for quests and shops">
          <NpcCards npcs={npcs} />
        </Section>
      )}

      {(leadsTo.length + reachedFrom.length + travelsOut.length + travelsIn.length > 0) && (
        <div className="grid gap-4 md:grid-cols-2">
          <Section title="Leads to" description="Walk through an arrow to get there">
            <div className="space-y-4">
              {leadsTo.length === 0 ? <Empty>No arrows lead out of this map.</Empty> : <MapChips maps={leadsTo.map((m) => ({ ...m, name: titleOf(m.id), hint: prompts.get(m.id) }))} />}
              {travelsOut.length > 0 && (
                <div className="space-y-2">
                  <p className="text-xs font-medium text-muted-foreground">Or ask an NPC here</p>
                  <ul className="space-y-1.5 text-sm">
                    {travelsOut.map(({ npc, map }) => (
                      <li key={`${npc.id}-${map}`} className="flex flex-wrap items-center gap-1.5">
                        <SubjectLink subject={npc} icon /> takes you to <SubjectLink subject={{ kind: 'map', id: map, name: titleOf(map) }} icon />
                      </li>
                    ))}
                  </ul>
                </div>
              )}
            </div>
          </Section>
          <Section title="Reached from" description="Maps with an arrow into this one">
            <div className="space-y-4">
              {reachedFrom.length === 0 ? <Empty>No arrows lead here.</Empty> : <MapChips maps={reachedFrom.map((m) => ({ ...m, name: titleOf(m.id) }))} />}
              {travelsIn.length > 0 && (
                <div className="space-y-2">
                  <p className="text-xs font-medium text-muted-foreground">Or ask an NPC</p>
                  <ul className="space-y-1.5 text-sm">
                    {travelsIn.map((npc) => (
                      <li key={npc.id} className="flex flex-wrap items-center gap-1.5">
                        <SubjectLink subject={npc} icon /> in <SubjectLink subject={{ kind: 'map', id: npc.id.split('/')[0], name: titleOf(npc.id.split('/')[0]) }} icon />
                      </li>
                    ))}
                  </ul>
                </div>
              )}
            </div>
          </Section>
        </div>
      )}
    </WikiPage>
  )
}

function MapChips({ maps }: { maps: { kind: string; id: string; name: string; hint?: string }[] }) {
  return (
    <div className="flex flex-wrap gap-2">
      {maps.map((map) => (
        <span key={map.id} title={map.hint}><Badge><SubjectLink subject={map} /></Badge></span>
      ))}
    </div>
  )
}
