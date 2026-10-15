import { useEffect, useRef, useState } from 'react'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { Section } from '@/components/wiki-page'
import { loadRuffle, type RufflePlayer } from '@/lib/ruffle'

export function MonsterPreview({ file, linkage }: { file: string; linkage: string }) {
  const container = useRef<HTMLDivElement>(null)
  const player = useRef<RufflePlayer | null>(null)
  const [animations, setAnimations] = useState<string[] | null>(null)
  const [current, setCurrent] = useState('Idle')
  const [failed, setFailed] = useState(false)

  useEffect(() => {
    let cancelled = false
    setAnimations(null)
    window.onMonsterAnimationsLoaded = (csv) => !cancelled && setAnimations(csv.split(',').filter(Boolean))

    loadRuffle()
      .then(() => {
        if (cancelled || !container.current || !window.RufflePlayer) return
        const instance = window.RufflePlayer.newest().createPlayer()
        instance.style.width = '100%'
        instance.style.height = '100%'
        container.current.replaceChildren(instance)
        player.current = instance
        return instance.load({ url: '/swfs/monster.swf', parameters: { sFile: file, sSymbol: linkage, sAnim: 'Idle' } })
      })
      .catch(() => !cancelled && setFailed(true))

    return () => {
      cancelled = true
      player.current?.remove()
      player.current = null
    }
  }, [file, linkage])

  const play = (anim: string) => {
    setCurrent(anim)
    player.current?.playAnim?.(anim)
  }

  return (
    <Section title="Preview" description="Click an animation to play it">
      <div className="space-y-3">
        <div className="relative aspect-4/3 overflow-hidden rounded-lg border bg-[radial-gradient(circle_at_50%_70%,var(--muted),transparent_70%)]">
          <div ref={container} className="size-full" />
          {failed && <p className="absolute inset-0 grid place-items-center text-sm text-muted-foreground">Preview unavailable</p>}
        </div>
        <div className="flex flex-wrap gap-1.5">
          {animations === null && !failed && <Skeleton className="h-7 w-full" />}
          {animations?.map((anim) => (
            <Button key={anim} size="sm" variant={anim === current ? 'default' : 'outline'} className="h-7 text-xs" onClick={() => play(anim)}>
              {anim}
            </Button>
          ))}
        </div>
      </div>
    </Section>
  )
}
