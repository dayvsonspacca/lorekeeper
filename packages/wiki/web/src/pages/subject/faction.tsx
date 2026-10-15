import { SubjectLink } from '@/components/subject-link'
import { Empty, Section, WikiPage } from '@/components/wiki-page'
import { Badge } from '@/components/ui/badge'
import type { Detail } from '@/lib/api'
import { formatNumber } from '@/lib/format'
import { page } from '@/lib/page'

export function FactionPage({ detail }: { detail: Detail }) {
  const p = page(detail)
  const reputation = p.pointing('quest.reward.reputation')
  const ranked = p.pointing('quest.requirement.faction_rank')

  return (
    <WikiPage
      crumbs={[{ label: 'Home', to: '/' }, { label: 'World' }, { label: 'Factions', to: '/faction' }, { label: detail.subject.name }]}
      title={detail.subject.name}
      subtitle={<span>Faction</span>}
      updated={p.updated}
    >
      <div className="grid gap-4 md:grid-cols-2">
        <Section title="Gain reputation" description="Quests that reward reputation">
          {reputation.length === 0 ? (
            <Empty>No known quests.</Empty>
          ) : (
            <ul className="space-y-2">
              {reputation.map((r) => (
                <li key={r.subject.id} className="flex items-center justify-between gap-2">
                  <SubjectLink subject={r.subject} icon />
                  <Badge variant="outline">+{formatNumber(Number((r.value as { amount?: number }).amount ?? 0))}</Badge>
                </li>
              ))}
            </ul>
          )}
        </Section>
        <Section title="Requires rank" description="Quests locked behind a rank">
          {ranked.length === 0 ? (
            <Empty>No known quests.</Empty>
          ) : (
            <ul className="space-y-2">
              {ranked.map((r) => (
                <li key={r.subject.id} className="flex items-center justify-between gap-2">
                  <SubjectLink subject={r.subject} icon />
                  <Badge variant="outline">Rank {String((r.value as { rank?: number }).rank ?? '?')}</Badge>
                </li>
              ))}
            </ul>
          )}
        </Section>
      </div>
    </WikiPage>
  )
}
