import type { ReactNode } from 'react'
import { Breadcrumbs, type Crumb } from '@/components/breadcrumbs'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { timeAgo } from '@/lib/format'

export function WikiPage({
  crumbs,
  title,
  subtitle,
  actions,
  updated,
  children,
}: {
  crumbs: Crumb[]
  title: ReactNode
  subtitle?: ReactNode
  actions?: ReactNode
  updated?: string
  children: ReactNode
}) {
  return (
    <article className="space-y-6">
      <Breadcrumbs items={crumbs} />
      <header className="flex flex-wrap items-end justify-between gap-4 border-b pb-5">
        <div className="space-y-2">
          <h1 className="text-3xl font-semibold tracking-tight">{title}</h1>
          {subtitle && <div className="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">{subtitle}</div>}
        </div>
        {actions}
      </header>
      {children}
      {updated && <p className="pt-4 text-xs text-muted-foreground">Page updated {timeAgo(updated)}</p>}
    </article>
  )
}

export function Section({ title, description, children, className }: { title: string; description?: string; children: ReactNode; className?: string }) {
  return (
    <Card className={className}>
      <CardHeader>
        <CardTitle className="text-base">{title}</CardTitle>
        {description && <CardDescription>{description}</CardDescription>}
      </CardHeader>
      <CardContent>{children}</CardContent>
    </Card>
  )
}

export function StatTile({ label, value, icon: Icon }: { label: string; value: ReactNode; icon?: React.ComponentType<{ className?: string }> }) {
  return (
    <Card className="gap-1 py-4">
      <CardContent className="flex items-center gap-3 px-4">
        {Icon && (
          <div className="flex size-9 items-center justify-center rounded-md bg-muted">
            <Icon className="size-4 text-muted-foreground" />
          </div>
        )}
        <div className="min-w-0">
          <div className="text-xs text-muted-foreground">{label}</div>
          <div className="truncate text-lg font-semibold tabular-nums tracking-tight">{value}</div>
        </div>
      </CardContent>
    </Card>
  )
}

export function Empty({ children }: { children: ReactNode }) {
  return <p className="text-sm text-muted-foreground">{children}</p>
}
