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
  const leadsTo = p.linked('map.link')
  const reachedFrom = p.pointing('map.link').map((r) => r.subject)
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

      {(leadsTo.length > 0 || reachedFrom.length > 0) && (
        <div className="grid gap-4 md:grid-cols-2">
          <Section title="Leads to" description="Maps you can reach from here">
            {leadsTo.length === 0 ? <Empty>No known exits.</Empty> : <MapChips maps={leadsTo.map((m) => ({ ...m, name: titleOf(m.id) }))} />}
          </Section>
          <Section title="Reached from" description="Maps that lead here">
            {reachedFrom.length === 0 ? <Empty>No known entrances.</Empty> : <MapChips maps={reachedFrom.map((m) => ({ ...m, name: titleOf(m.id) }))} />}
          </Section>
        </div>
      )}
    </WikiPage>
  )
}

function MapChips({ maps }: { maps: { kind: string; id: string; name: string }[] }) {
  return (
    <div className="flex flex-wrap gap-2">
      {maps.map((map) => (
        <Badge key={map.id}><SubjectLink subject={map} /></Badge>
      ))}
    </div>
  )
}
