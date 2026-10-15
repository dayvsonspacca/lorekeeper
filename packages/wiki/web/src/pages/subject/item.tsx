import { Coins, Crown, Layers, Tag } from 'lucide-react'
import { SubjectLink } from '@/components/subject-link'
import { Alert } from '@/components/wiki-bits'
import { Empty, Section, StatTile, WikiPage } from '@/components/wiki-page'
import { Badge } from '@/components/ui/badge'
import { AvatarPreview, type AvatarSlot, ItemPreview } from '@/features/flash-preview'
import type { Detail } from '@/lib/api'
import { categoryOf } from '@/lib/categories'
import { Cost, offerOf, useItemNames, useShopKeepers } from '@/features/shops'
import { price } from '@/lib/facts'
import { formatNumber } from '@/lib/format'
import { page } from '@/lib/page'

const avatarSlots: Record<string, AvatarSlot> = { Weapon: 'weapon', Helm: 'helm', Cape: 'cape', Armor: 'armor', Costume: 'armor' }

export function ItemPage({ detail }: { detail: Detail }) {
  const p = page(detail)
  const type = p.value<string>('item.type')
  const category = categoryOf(type)
  const cost = p.value('item.price')
  const rewardedBy = p.pointing('quest.reward.item')
  const offers = detail.references.filter((r) => r.type === 'shop.item').map((r) => ({ shop: r.subject, offer: offerOf(r.value) }))
  const soldAt = offers.filter(({ offer }) => offer.item === detail.subject.id)
  const tradedFor = offers.filter(({ offer }) => offer.item !== detail.subject.id)
  const nameOf = useItemNames()
  const keepersOf = useShopKeepers()
  const turnedIn = p.pointing('quest.turn_in')
  const requiredBy = p.pointing('quest.requirement.item')
  const unnamed = detail.subject.name.startsWith('#')
  const file = p.value<string>('asset.gamefile')
  const linkage = p.value<string>('asset.linkage')
  const slot = avatarSlots[String(p.value('item.slot'))]
  const preview = file && file.endsWith('.swf') && (linkage || (slot && slot !== 'armor')) ? { file, linkage: linkage ?? '' } : undefined
  const description = p.value<string>('item.description')

  return (
    <WikiPage
      crumbs={[
        { label: 'Home', to: '/' },
        { label: 'Items', to: '/item' },
        ...(category ? [{ label: category.label, to: `/item?category=${category.key}` }] : []),
        { label: detail.subject.name },
      ]}
      title={unnamed ? `Unknown item ${detail.subject.name}` : detail.subject.name}
      subtitle={
        <>
          {type && <Badge variant="secondary">{type}</Badge>}
          {p.value('item.slot') !== undefined && <Badge variant="outline">{String(p.value('item.slot'))} slot</Badge>}
        </>
      }
      updated={p.updated}
    >
      {p.value('access.member_only') !== undefined && <Alert icon={Crown} title="Members only">Only members can use this item.</Alert>}
      {unnamed && (
        <Alert icon={Tag} title="Not identified yet">We have seen this item equipped, but never in an inventory, so its name is still unknown.</Alert>
      )}

      {(preview || description) && (
        <div className="grid gap-4 lg:grid-cols-2">
          {preview && (slot ? <AvatarPreview slot={slot} file={preview.file} linkage={preview.linkage} type={type} /> : <ItemPreview file={preview.file} linkage={preview.linkage} />)}
          {description && (
            <Section title="Description" className={preview ? '' : 'lg:col-span-2'}>
              <p className="leading-relaxed">{description}</p>
            </Section>
          )}
        </div>
      )}

      <div className="grid grid-cols-2 gap-3 md:grid-cols-4">
        <StatTile icon={Tag} label="Type" value={type ?? '—'} />
        <StatTile icon={Layers} label="Slot" value={String(p.value('item.slot') ?? '—')} />
        <StatTile icon={Coins} label="Buy" value={cost === undefined ? '—' : `${formatNumber(price(cost).amount)} ${price(cost).currency}`} />
        <StatTile icon={Coins} label="Sell" value={cost === undefined ? '—' : `${formatNumber(Math.floor(price(cost).amount / 4))} ${price(cost).currency}`} />
      </div>

      <div className="grid gap-4 md:grid-cols-2">
        <Section title="How to get" description="Shops that sell it and quests that give it">
          {rewardedBy.length + soldAt.length === 0 ? (
            <Empty>No known source yet.</Empty>
          ) : (
            <ul className="space-y-2">
              {soldAt.map(({ shop, offer }) => (
                <li key={`s${shop.id}`} className="space-y-1">
                  <div className="flex flex-wrap items-center justify-between gap-2">
                    <SubjectLink subject={shop} icon />
                    <span className="flex flex-wrap items-center gap-1.5 text-sm text-muted-foreground">
                      {keepersOf(shop.id).map((npc) => (
                        <SubjectLink key={npc.id} subject={npc} className="font-normal text-muted-foreground" />
                      ))}
                      <Badge variant="outline">{offer.costs.length > 0 ? 'trade' : 'buy'}</Badge>
                    </span>
                  </div>
                  {offer.costs.length > 0 && (
                    <div className="text-sm">
                      <Cost costs={offer.costs} />
                    </div>
                  )}
                </li>
              ))}
              {rewardedBy.map((r) => {
                const rate = Number((r.value as { dropRate?: number }).dropRate ?? 100)
                return (
                  <li key={r.subject.id} className="flex items-center justify-between gap-2">
                    <SubjectLink subject={r.subject} icon />
                    {rate < 100 && <Badge variant="outline">{rate}% chance</Badge>}
                  </li>
                )
              })}
            </ul>
          )}
        </Section>
        <Section title="Used in" description="Quests that ask for it and what it can be traded for">
          {turnedIn.length + requiredBy.length + tradedFor.length === 0 ? (
            <Empty>Not used anywhere we know of.</Empty>
          ) : (
            <ul className="space-y-2">
              {tradedFor.map(({ shop, offer }) => (
                <li key={`m${shop.id}-${offer.item}`} className="flex flex-wrap items-center justify-between gap-2">
                  <SubjectLink subject={{ kind: 'item', id: offer.item, name: nameOf(offer.item) }} icon />
                  <span className="flex items-center gap-1.5 text-sm text-muted-foreground">
                    <Badge variant="outline">
                      trade {offer.costs.find((cost) => cost.item === detail.subject.id)?.quantity ?? 1}
                    </Badge>
                    at <SubjectLink subject={shop} className="font-normal text-muted-foreground" />
                  </span>
                </li>
              ))}
              {turnedIn.map((r) => (
                <li key={`t${r.subject.id}`} className="flex items-center justify-between gap-2">
                  <SubjectLink subject={r.subject} icon />
                  <Badge variant="outline">bring {String((r.value as { quantity?: number }).quantity ?? 1)}</Badge>
                </li>
              ))}
              {requiredBy.map((r) => (
                <li key={`r${r.subject.id}`} className="flex items-center justify-between gap-2">
                  <SubjectLink subject={r.subject} icon />
                  <Badge variant="outline">must have</Badge>
                </li>
              ))}
            </ul>
          )}
        </Section>
      </div>
    </WikiPage>
  )
}
