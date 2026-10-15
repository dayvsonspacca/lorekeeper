import type { LucideIcon } from 'lucide-react'
import type { ReactNode } from 'react'
import { cn } from '@/lib/utils'

export function Badge({ children, className }: { children: ReactNode; className?: string }) {
  return <span className={cn('inline-flex items-center rounded-md border px-2.5 py-1 text-sm', className)}>{children}</span>
}

export function Alert({ icon: Icon, title, children, tone = 'info' }: { icon: LucideIcon; title: string; children: ReactNode; tone?: 'info' | 'danger' }) {
  return (
    <div
      className={cn(
        'flex gap-3 rounded-lg border p-4 text-sm',
        tone === 'info' ? 'border-amber-500/30 bg-amber-500/5' : 'border-red-500/30 bg-red-500/5',
      )}
    >
      <Icon className={cn('mt-0.5 size-4 shrink-0', tone === 'info' ? 'text-amber-500' : 'text-red-500')} />
      <div>
        <div className="font-medium">{title}</div>
        <div className="text-muted-foreground">{children}</div>
      </div>
    </div>
  )
}
