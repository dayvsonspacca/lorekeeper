import { Coins, Sparkles, Star } from 'lucide-react'
import { SubjectLink } from '@/components/subject-link'
import { WhereToGet } from '@/features/npcs'
import { Empty, Section, StatTile, WikiPage } from '@/components/wiki-page'
import { Badge } from '@/components/ui/badge'
import type { Detail } from '@/lib/api'
import { factType } from '@/lib/facts'
import { formatNumber } from '@/lib/format'
import { page } from '@/lib/page'

const requirementTypes = [
  'quest.requirement.player_level',
  'quest.requirement.member',
  'quest.requirement.previous_quest',
  'quest.requirement.item',
  'quest.requirement.class_rank',
  'quest.requirement.faction_rank',
]

export function QuestPage({ detail }: { detail: Detail }) {
  const p = page(detail)
  const description = p.value<{ text: string; endText: string }>('quest.description')
  const requirements = requirementTypes.flatMap((type) => p.facts(type))
  const turnIns = p.facts('quest.turn_in')
  const items = p.facts('quest.reward.item')
  const gold = p.value<number>('quest.reward.gold')
  const experience = p.value<number>('quest.reward.experience')
  const reputation = p.facts('quest.reward.reputation')[0]
  const unlocks = p.pointing('quest.requirement.previous_quest')
  const givers = p.pointing('npc.quest').map((r) => r.subject)

  return (
    <WikiPage
      crumbs={[{ label: 'Home', to: '/' }, { label: 'World' }, { label: 'Quests', to: '/quest' }, { label: detail.subject.name }]}
      title={detail.subject.name}
      subtitle={<span>Quest</span>}
      updated={p.updated}
    >
      {givers.length > 0 && (
        <Section title="Where to get it">
          <WhereToGet npcs={givers} />
        </Section>
      )}

      {description && (
        <Section title="Story">
          <p className="leading-relaxed">{description.text}</p>
          {description.endText && <p className="mt-4 border-l-2 pl-4 italic text-muted-foreground">“{description.endText}”</p>}
        </Section>
      )}

      <div className="grid gap-4 md:grid-cols-2">
        <Section title="Requirements" description="What you need before accepting">
          {requirements.length === 0 ? (
            <Empty>Anyone can take this quest.</Empty>
          ) : (
            <ul className="list-disc space-y-1.5 pl-5">
              {requirements.map((r, i) => <li key={i}>{factType(r.type).render(r.value, r.ref)}</li>)}
            </ul>
          )}
        </Section>
        <Section title="Objectives" description="What to bring back">
          {turnIns.length === 0 ? (
            <Empty>No items to turn in.</Empty>
          ) : (
            <ul className="space-y-2">
              {turnIns.map((t, i) => (
                <li key={i} className="flex items-center gap-2">
                  <Badge variant="secondary" className="tabular-nums">{String((t.value as { quantity?: number }).quantity ?? 1)}×</Badge>
                  {t.ref ? <SubjectLink subject={t.ref} /> : String(t.value)}
                </li>
              ))}
            </ul>
          )}
        </Section>
      </div>

      <Section title="Rewards">
        <div className="space-y-4">
          <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <StatTile icon={Coins} label="Gold" value={gold === undefined ? '—' : formatNumber(gold)} />
            <StatTile icon={Sparkles} label="Experience" value={experience === undefined ? '—' : formatNumber(experience)} />
            <StatTile
              icon={Star}
              label="Reputation"
              value={reputation ? <span>{formatNumber(Number((reputation.value as { amount?: number }).amount ?? 0))} {reputation.ref && <span className="text-sm font-normal text-muted-foreground">· {reputation.ref.name}</span>}</span> : '—'}
            />
          </div>
          {items.length > 0 && (
            <ul className="divide-y rounded-lg border">
              {items.map((item, i) => {
                const reward = item.value as { quantity?: number; dropRate?: number }
                return (
                  <li key={i} className="flex items-center justify-between gap-3 px-4 py-2.5">
                    <span className="flex items-center gap-2">
                      <span className="tabular-nums text-muted-foreground">{reward.quantity ?? 1}×</span>
                      {item.ref ? <SubjectLink subject={item.ref} icon /> : null}
                    </span>
                    <Badge variant={Number(reward.dropRate ?? 100) < 100 ? 'outline' : 'secondary'}>
                      {Number(reward.dropRate ?? 100) < 100 ? `${reward.dropRate}% chance` : 'Guaranteed'}
                    </Badge>
                  </li>
                )
              })}
            </ul>
          )}
        </div>
      </Section>

      {unlocks.length > 0 && (
        <Section title="Unlocks" description="Quests available after completing this one">
          <div className="flex flex-wrap gap-2">
            {unlocks.map((u) => <SubjectLink key={u.subject.id} subject={u.subject} icon />)}
          </div>
        </Section>
      )}
    </WikiPage>
  )
}
