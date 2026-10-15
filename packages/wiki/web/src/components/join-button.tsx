import { Check, Copy } from 'lucide-react'
import { useState } from 'react'
import { Button } from '@/components/ui/button'
import { cn } from '@/lib/utils'

export function JoinButton({ map, className }: { map: string; className?: string }) {
  const [copied, setCopied] = useState(false)
  const command = `/join ${map}`

  return (
    <Button
      variant="outline"
      size="sm"
      className={cn('h-7 gap-1.5 px-2 font-mono text-xs', className)}
      onClick={(event) => {
        event.preventDefault()
        event.stopPropagation()
        void navigator.clipboard?.writeText(command)
        setCopied(true)
        setTimeout(() => setCopied(false), 1500)
      }}
    >
      {copied ? <Check className="size-3.5 text-emerald-500" /> : <Copy className="size-3.5" />}
      {command}
    </Button>
  )
}
