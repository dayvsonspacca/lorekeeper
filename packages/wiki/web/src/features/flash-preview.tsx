import { LoaderCircle } from 'lucide-react'
import { type ReactNode, useEffect, useId, useRef, useState } from 'react'
import { Button } from '@/components/ui/button'
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group'
import { Section } from '@/components/wiki-page'
import type { Detail } from '@/lib/api'
import { page } from '@/lib/page'
import { loadRuffle, type RufflePlayer } from '@/lib/ruffle'
import { useTheme } from '@/lib/theme'
import { cn } from '@/lib/utils'

type Callbacks = Record<string, ((payload: string) => void) | undefined>

interface FlashPreviewProps {
  swf: string
  parameters: Record<string, string>
  animations?: string[]
  description: string
  initial?: string
  compact?: boolean
  controls?: ReactNode
}

function FlashPreview({ swf, parameters, animations: fixed, description, initial, compact = false, controls }: FlashPreviewProps) {
  const container = useRef<HTMLDivElement>(null)
  const player = useRef<RufflePlayer | null>(null)
  const [animations, setAnimations] = useState<string[] | null>(null)
  const [current, setCurrent] = useState(initial ?? fixed?.[0] ?? null)
  const [failure, setFailure] = useState(false)
  const id = useId().replace(/[^a-zA-Z0-9]/g, '')
  const key = JSON.stringify(parameters)

  useEffect(() => {
    let cancelled = false
    const callbacks = window as unknown as Callbacks
    const loaded = `onPreviewLoaded${id}`
    const failed = `onPreviewFailed${id}`
    setAnimations(null)
    setFailure(false)
    setCurrent(initial ?? fixed?.[0] ?? null)
    callbacks[loaded] = (csv) => !cancelled && setAnimations(fixed ?? csv.split(',').filter(Boolean))
    callbacks[failed] = () => !cancelled && setFailure(true)

    loadRuffle()
      .then(() => {
        if (cancelled || !container.current || !window.RufflePlayer) return
        const instance = window.RufflePlayer.newest().createPlayer()
        instance.style.width = '100%'
        instance.style.height = '100%'
        container.current.replaceChildren(instance)
        player.current = instance
        return instance.load({ url: swf, parameters: { ...(JSON.parse(key) as Record<string, string>), sLoaded: loaded, sFailed: failed } })
      })
      .catch(() => !cancelled && setFailure(true))

    return () => {
      cancelled = true
      callbacks[loaded] = undefined
      callbacks[failed] = undefined
      player.current?.remove()
      player.current = null
    }
  }, [swf, key, fixed, id, initial])

  const play = (anim: string) => {
    setCurrent(anim)
    player.current?.playAnim?.(anim)
  }

  const stage = (
    <div className={cn('relative aspect-4/3 overflow-hidden rounded-lg border bg-[radial-gradient(circle_at_50%_50%,var(--muted),transparent_70%)]', compact && 'rounded-md')}>
      <div ref={container} className={cn('size-full transition-opacity duration-300', animations === null ? 'opacity-0' : 'opacity-100')} />
      {animations === null && !failure && (
        <div className="absolute inset-0 grid place-items-center">
          <div className="flex flex-col items-center gap-2 text-muted-foreground">
            <LoaderCircle className="size-6 animate-spin" />
            <span className="text-xs">Loading preview…</span>
          </div>
        </div>
      )}
      {failure && <p className="absolute inset-0 grid place-items-center text-sm text-muted-foreground">Preview unavailable</p>}
    </div>
  )

  if (compact) {
    if (failure) return null
    return controls ? (
      <div className="space-y-2">
        {controls}
        {stage}
      </div>
    ) : (
      stage
    )
  }

  return (
    <Section title="Preview" description={description}>
      <div className="space-y-3">
        {controls}
        {stage}
        {animations && animations.length > 0 && (
          <div className="flex flex-wrap gap-1.5">
            {animations.map((anim) => (
              <Button key={anim} size="sm" variant={anim === current ? 'default' : 'outline'} className="h-7 text-xs" onClick={() => play(anim)}>
                {anim}
              </Button>
            ))}
          </div>
        )}
      </div>
    </Section>
  )
}

const proxy = () => `${window.location.origin}/proxy/swf/item/`

export function ItemPreview({ file, linkage, compact }: { file: string; linkage: string; compact?: boolean }) {
  return (
    <FlashPreview
      swf={`/swfs/item.swf?v=${__SWF_VERSION__}`}
      parameters={{ sBase: proxy(), sFile: file, sLink: linkage }}
      description="How this item looks in game"
      compact={compact}
    />
  )
}

export function MonsterPreview({ file, linkage, compact }: { file: string; linkage: string; compact?: boolean }) {
  return (
    <FlashPreview
      swf={`/swfs/monster.swf?v=${__SWF_VERSION__}`}
      parameters={{ sBase: `${window.location.origin}/proxy/swf/monster/`, sFile: file, sLink: linkage, sAnim: 'Idle' }}
      initial="Idle"
      description="Click an animation to play it"
      compact={compact}
    />
  )
}

export type AvatarSlot = 'weapon' | 'helm' | 'cape' | 'armor'

const avatarAnimations = ['Idle', 'Walk', 'Fight', 'Attack1', 'Attack2', 'Cast1', 'Jump', 'Dance', 'Salute', 'Cheer']
const weaponAnimations = avatarAnimations.filter((anim) => anim !== 'Dance')

type Gender = 'M' | 'F'

function storedGender(): Gender {
  try {
    return localStorage.getItem('lorekeeper-gender') === 'F' ? 'F' : 'M'
  } catch {
    return 'M'
  }
}

export function AvatarPreview({ slot, file, linkage, type, compact }: { slot: AvatarSlot; file: string; linkage: string; type?: string; compact?: boolean }) {
  const { theme } = useTheme()
  const [gender, setGender] = useState<Gender>(storedGender)
  const choose = (value: string) => {
    if (value !== 'M' && value !== 'F') return
    setGender(value)
    try {
      localStorage.setItem('lorekeeper-gender', value)
    } catch {}
  }
  const colors = theme === 'dark' ? { sColor: '0x52525B', sBackColor: '0x3F3F46' } : { sColor: '0xA1A1AA', sBackColor: '0x71717A' }

  return (
    <FlashPreview
      swf={`/swfs/avatar.swf?v=${__SWF_VERSION__}`}
      parameters={{
        sBase: proxy(),
        sSlot: slot,
        sFile: slot === 'armor' ? `classes/${gender}/${file}` : file,
        sLink: linkage,
        sType: type ?? '',
        sGender: gender,
        ...colors,
      }}
      animations={slot === 'weapon' ? weaponAnimations : avatarAnimations}
      description="Worn by a character — pick an animation"
      compact={compact}
      controls={
        slot === 'armor' && (
          <ToggleGroup type="single" variant="outline" size="sm" value={gender} onValueChange={choose} className={compact ? 'h-7' : undefined}>
            <ToggleGroupItem value="M" className={compact ? 'h-7 px-2.5 text-xs' : 'px-3 text-xs'}>Male</ToggleGroupItem>
            <ToggleGroupItem value="F" className={compact ? 'h-7 px-2.5 text-xs' : 'px-3 text-xs'}>Female</ToggleGroupItem>
          </ToggleGroup>
        )
      }
    />
  )
}

const avatarSlots: Record<string, AvatarSlot> = { Weapon: 'weapon', Helm: 'helm', Cape: 'cape', Armor: 'armor' }

export function visualOf(value: (type: string) => unknown) {
  const file = value('asset.gamefile')
  const linkage = value('asset.linkage')
  const slot = avatarSlots[String(value('item.slot'))]
  if (typeof file !== 'string' || !file.endsWith('.swf')) return undefined
  if (slot === 'armor' ? typeof linkage !== 'string' : !file.startsWith('items/')) return undefined
  if (!slot && typeof linkage !== 'string') return undefined
  return { file, linkage: typeof linkage === 'string' ? linkage : '', slot, type: typeof value('item.type') === 'string' ? String(value('item.type')) : undefined }
}

export function itemVisual(detail: Detail) {
  const p = page(detail)
  return visualOf((type) => p.value(type))
}

export function ItemVisual({ detail, compact }: { detail: Detail; compact?: boolean }) {
  const visual = itemVisual(detail)
  if (!visual) return null
  return visual.slot ? (
    <AvatarPreview slot={visual.slot} file={visual.file} linkage={visual.linkage} type={visual.type} compact={compact} />
  ) : (
    <ItemPreview file={visual.file} linkage={visual.linkage} compact={compact} />
  )
}
