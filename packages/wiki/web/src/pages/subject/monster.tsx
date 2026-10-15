import { Droplet, Heart, Skull, Zap } from 'lucide-react'
import { JoinButton } from '@/components/join-button'
import { SubjectLink } from '@/components/subject-link'
import { Empty, Section, StatTile, WikiPage } from '@/components/wiki-page'
import { type Detail, useAtlas } from '@/lib/api'
import { formatNumber } from '@/lib/format'
import { page } from '@/lib/page'
import { MonsterPreview } from '@/features/flash-preview'

export function MonsterPage({ detail }: { detail: Detail }) {
  const p = page(detail)
  const { data: atlas } = useAtlas()
  const maps = p.pointing('map.monster').map((r) => r.subject)
  const health = p.value<number>('attribute.health')
  const mana = p.value<number>('attribute.mana')
  const file = p.value<string>('asset.gamefile')
  const linkage = p.value<string>('asset.linkage')

  return (
    <WikiPage
      crumbs={[{ label: 'Home', to: '/' }, { label: 'World' }, { label: 'Monsters', to: '/monster' }, { label: detail.subject.name }]}
      title={detail.subject.name}
      subtitle={<span>Monster</span>}
      updated={p.updated}
    >
      <div className="grid grid-cols-2 gap-3 md:grid-cols-4">
        <StatTile icon={Zap} label="Level" value={p.value('monster.level') ?? '—'} />
        <StatTile icon={Heart} label="Health" value={health === undefined ? '—' : formatNumber(health)} />
        <StatTile icon={Droplet} label="Mana" value={mana === undefined ? '—' : formatNumber(mana)} />
        <StatTile icon={Skull} label="Race" value={p.value('monster.race') ?? '—'} />
      </div>

      <div className="grid gap-4 lg:grid-cols-2">
        {file && linkage && <MonsterPreview file={file} linkage={linkage} />}
        <Section title="Where to find" description="Maps where this monster appears" className={file && linkage ? '' : 'lg:col-span-2'}>
          {maps.length === 0 ? (
            <Empty>Not spotted in any map yet.</Empty>
          ) : (
            <ul className="divide-y">
              {maps.map((map) => (
                <li key={map.id} className="flex items-center justify-between gap-3 py-2.5">
                  <SubjectLink subject={{ ...map, name: atlas?.nodes.find((n) => n.id === map.id)?.title ?? map.name }} />
                  <JoinButton map={map.id} />
                </li>
              ))}
            </ul>
          )}
        </Section>
      </div>
    </WikiPage>
  )
}
