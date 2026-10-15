import { Section, WikiPage } from '@/components/wiki-page'
import { Badge } from '@/components/ui/badge'
import type { Detail } from '@/lib/api'
import { page } from '@/lib/page'

export function ClassPage({ detail }: { detail: Detail }) {
  const p = page(detail)
  const category = p.value<string>('class.category')
  const description = p.value<string>('class.description')
  const mana = p.values<string>('class.mana_regeneration')

  return (
    <WikiPage
      crumbs={[{ label: 'Home', to: '/' }, { label: 'Classes', to: '/class_name' }, { label: detail.subject.name }]}
      title={detail.subject.name}
      subtitle={category && <Badge variant="secondary">{category}</Badge>}
      updated={p.updated}
    >
      {description && (
        <Section title="Description">
          <p className="leading-relaxed">{description}</p>
        </Section>
      )}
      {mana.length > 0 && (
        <Section title="Mana regeneration" description="How this class recovers mana">
          <ul className="list-disc space-y-1.5 pl-5">{mana.map((m, i) => <li key={i}>{m}</li>)}</ul>
        </Section>
      )}
    </WikiPage>
  )
}
