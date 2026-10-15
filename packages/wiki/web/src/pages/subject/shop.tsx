import { Crown, Package, UserRound } from 'lucide-react'
import { JoinButton } from '@/components/join-button'
import { SubjectLink } from '@/components/subject-link'
import { Alert } from '@/components/wiki-bits'
import { Empty, Section, StatTile, WikiPage } from '@/components/wiki-page'
import { useMapTitles } from '@/features/npcs'
import { offerOf, ShopItemsTable } from '@/features/shops'
import type { Detail } from '@/lib/api'
import { page } from '@/lib/page'

export function ShopPage({ detail }: { detail: Detail }) {
  const p = page(detail)
  const items = p.facts('shop.item').flatMap((fact) => (fact.ref ? [{ ref: fact.ref, offer: offerOf(fact.value) }] : []))
  const keepers = p.pointing('npc.shop').map((r) => r.subject)
  const titleOf = useMapTitles()

  return (
    <WikiPage
      crumbs={[{ label: 'Home', to: '/' }, { label: 'World' }, { label: 'Shops', to: '/shop' }, { label: detail.subject.name }]}
      title={detail.subject.name}
      subtitle={<span>Shop</span>}
      actions={keepers[0] && <JoinButton map={keepers[0].id.split('/')[0]} className="h-9 px-3 text-sm" />}
      updated={p.updated}
    >
      {p.value('access.member_only') !== undefined && <Alert icon={Crown} title="Members only">Only members can buy from this shop.</Alert>}

      <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <StatTile icon={Package} label="Items" value={items.length} />
        <StatTile icon={UserRound} label="Run by" value={keepers.length === 0 ? '—' : keepers.length === 1 ? keepers[0].name : `${keepers.length} NPCs`} />
      </div>

      {keepers.length > 0 && (
        <Section title="Where to find it" description="Talk to these NPCs to open the shop">
          <ul className="divide-y rounded-lg border">
            {keepers.map((npc) => (
              <li key={npc.id} className="flex flex-wrap items-center justify-between gap-3 px-4 py-2.5">
                <span className="flex items-center gap-1.5">
                  Talk to <SubjectLink subject={npc} icon /> in{' '}
                  <SubjectLink subject={{ kind: 'map', id: npc.id.split('/')[0], name: titleOf(npc.id.split('/')[0]) }} icon />
                </span>
                <JoinButton map={npc.id.split('/')[0]} />
              </li>
            ))}
          </ul>
        </Section>
      )}

      <Section title="Stock" description="Everything this shop sells">
        {items.length === 0 ? <Empty>This shop sells nothing right now.</Empty> : <ShopItemsTable items={items} />}
      </Section>
    </WikiPage>
  )
}
