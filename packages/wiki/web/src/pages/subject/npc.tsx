import { MapPin, ScrollText, Store } from 'lucide-react'
import { JoinButton } from '@/components/join-button'
import { Empty, Section, StatTile, WikiPage } from '@/components/wiki-page'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { SubjectLink } from '@/components/subject-link'
import { MapLinks } from '@/features/npcs'
import { ShopCards } from '@/features/shops'
import { type Detail, useListing } from '@/lib/api'
import { first } from '@/lib/facts'
import { formatNumber } from '@/lib/format'
import { page } from '@/lib/page'

const dash = <span className="text-muted-foreground/50">—</span>
const n = (v: unknown) => (typeof v === 'number' ? formatNumber(v) : dash)

export function NpcPage({ detail }: { detail: Detail }) {
  const p = page(detail)
  const { data: questListing } = useListing('quest')
  const quests = p.linked('npc.quest')
  const shops = p.facts('npc.shop')
  const maps = p.pointing('map.npc').map((r) => r.subject)
  const rows = new Map((questListing?.rows ?? []).map((row) => [row.id, row]))
  const known = quests.filter((q) => !q.name.startsWith('#'))
  const unknown = quests.length - known.length

  return (
    <WikiPage
      crumbs={[{ label: 'Home', to: '/' }, { label: 'World' }, { label: 'NPCs', to: '/npc' }, { label: detail.subject.name }]}
      title={detail.subject.name}
      subtitle={<><span>{p.value<string>('npc.title') ?? 'NPC'}</span><MapLinks maps={maps} /></>}
      actions={maps[0] && <JoinButton map={maps[0].id} className="h-9 px-3 text-sm" />}
      updated={p.updated}
    >
      <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <StatTile icon={ScrollText} label="Quests" value={quests.length} />
        <StatTile icon={Store} label="Shops" value={shops.length} />
        <StatTile icon={MapPin} label="Found in" value={maps.length === 1 ? '1 map' : `${maps.length} maps`} />
      </div>

      <Section title="Quests" description={`Quests ${detail.subject.name} gives out`}>
        {quests.length === 0 ? (
          <Empty>{detail.subject.name} has no quests.</Empty>
        ) : (
          <div className="-mx-6">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead className="pl-6">Quest</TableHead>
                  <TableHead className="w-24">Level</TableHead>
                  <TableHead className="w-24">Gold</TableHead>
                  <TableHead className="w-28">Experience</TableHead>
                  <TableHead className="w-28 pr-6">Item rewards</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {known.map((quest) => {
                  const facts = rows.get(quest.id)?.facts ?? {}
                  return (
                    <TableRow key={quest.id}>
                      <TableCell className="pl-6"><SubjectLink subject={quest} /></TableCell>
                      <TableCell className="tabular-nums">{n(first(facts, 'quest.requirement.player_level'))}</TableCell>
                      <TableCell className="tabular-nums">{n(first(facts, 'quest.reward.gold'))}</TableCell>
                      <TableCell className="tabular-nums">{n(first(facts, 'quest.reward.experience'))}</TableCell>
                      <TableCell className="pr-6 tabular-nums">{n(facts['quest.reward.item']?.length || undefined)}</TableCell>
                    </TableRow>
                  )
                })}
              </TableBody>
            </Table>
            {unknown > 0 && (
              <p className="px-6 pt-3 text-sm text-muted-foreground">
                {unknown === 1 ? '1 more quest has' : `${unknown} more quests have`} not been documented yet.
              </p>
            )}
          </div>
        )}
      </Section>

      {shops.length > 0 && (
        <Section title="Shops" description="What this NPC sells">
          <ShopCards shops={shops.map((shop) => ({ id: String(shop.value), ref: shop.ref }))} />
        </Section>
      )}
    </WikiPage>
  )
}
