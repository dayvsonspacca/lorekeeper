import { useEffect, useRef, useState } from 'react'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { Section } from '@/components/wiki-page'
import { loadRuffle, type RufflePlayer } from '@/lib/ruffle'
import { useTheme } from '@/lib/theme'

type Callback = 'onItemPreviewLoaded' | 'onItemPreviewFailed' | 'onAvatarLoaded' | 'onAvatarFailed'

interface FlashPreviewProps {
  swf: string
  parameters: Record<string, string>
  loaded: Callback
  failed: Callback
  animations?: string[]
  description: string
}

function FlashPreview({ swf, parameters, loaded, failed, animations: fixed, description }: FlashPreviewProps) {
  const container = useRef<HTMLDivElement>(null)
  const player = useRef<RufflePlayer | null>(null)
  const [animations, setAnimations] = useState<string[] | null>(null)
  const [current, setCurrent] = useState(fixed?.[0] ?? null)
  const [failure, setFailure] = useState(false)
  const key = JSON.stringify(parameters)

  useEffect(() => {
    let cancelled = false
    setAnimations(null)
    setFailure(false)
    setCurrent(fixed?.[0] ?? null)
    window[loaded] = (csv: string) => !cancelled && setAnimations(fixed ?? csv.split(',').filter(Boolean))
    window[failed] = () => !cancelled && setFailure(true)

    loadRuffle()
      .then(() => {
        if (cancelled || !container.current || !window.RufflePlayer) return
        const instance = window.RufflePlayer.newest().createPlayer()
        instance.style.width = '100%'
        instance.style.height = '100%'
        container.current.replaceChildren(instance)
        player.current = instance
        return instance.load({ url: swf, parameters: JSON.parse(key) as Record<string, string> })
      })
      .catch(() => !cancelled && setFailure(true))

    return () => {
      cancelled = true
      player.current?.remove()
      player.current = null
    }
  }, [swf, key, loaded, failed, fixed])

  const play = (anim: string) => {
    setCurrent(anim)
    player.current?.playAnim?.(anim)
  }

  return (
    <Section title="Preview" description={description}>
      <div className="space-y-3">
        <div className="relative aspect-4/3 overflow-hidden rounded-lg border bg-[radial-gradient(circle_at_50%_50%,var(--muted),transparent_70%)]">
          <div ref={container} className="size-full" />
          {animations === null && !failure && <Skeleton className="absolute inset-0 rounded-none opacity-40" />}
          {failure && <p className="absolute inset-0 grid place-items-center text-sm text-muted-foreground">Preview unavailable</p>}
        </div>
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

export function ItemPreview({ file, linkage }: { file: string; linkage: string }) {
  return (
    <FlashPreview
      swf="/swfs/item.swf"
      parameters={{ sBase: proxy(), sFile: file, sLink: linkage }}
      loaded="onItemPreviewLoaded"
      failed="onItemPreviewFailed"
      description="How this item looks in game"
    />
  )
}

export type AvatarSlot = 'weapon' | 'helm' | 'cape' | 'armor'

const avatarAnimations = ['Idle', 'Walk', 'Fight', 'Attack1', 'Attack2', 'Cast1', 'Jump', 'Dance', 'Salute', 'Cheer']

export function AvatarPreview({ slot, file, linkage, type }: { slot: AvatarSlot; file: string; linkage: string; type?: string }) {
  const { theme } = useTheme()
  const colors = theme === 'dark' ? { sColor: '0x52525B', sBackColor: '0x3F3F46' } : { sColor: '0xA1A1AA', sBackColor: '0x71717A' }

  return (
    <FlashPreview
      swf="/swfs/avatar.swf"
      parameters={{
        sBase: proxy(),
        sSlot: slot,
        sFile: slot === 'armor' ? `classes/M/${file}` : file,
        sLink: linkage,
        sType: type ?? '',
        sGender: 'M',
        ...colors,
      }}
      loaded="onAvatarLoaded"
      failed="onAvatarFailed"
      animations={slot === 'weapon' ? avatarAnimations.filter((anim) => anim !== 'Dance') : avatarAnimations}
      description="Worn by a character — pick an animation"
    />
  )
}
