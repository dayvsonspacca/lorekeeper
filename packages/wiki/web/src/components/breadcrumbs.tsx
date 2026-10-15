import { ChevronRight } from 'lucide-react'
import { Fragment } from 'react'
import { Link } from 'react-router-dom'

export interface Crumb {
  label: string
  to?: string
}

export function Breadcrumbs({ items }: { items: Crumb[] }) {
  return (
    <nav className="flex flex-wrap items-center gap-1 text-sm text-muted-foreground">
      {items.map((item, index) => (
        <Fragment key={index}>
          {index > 0 && <ChevronRight className="size-3.5" />}
          {item.to ? (
            <Link to={item.to} className="hover:text-foreground">{item.label}</Link>
          ) : (
            <span className={index === items.length - 1 ? 'text-foreground' : ''}>{item.label}</span>
          )}
        </Fragment>
      ))}
    </nav>
  )
}
