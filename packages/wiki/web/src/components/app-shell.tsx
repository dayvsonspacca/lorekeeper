import { BookOpen, ChevronDown, Home, Map as MapIcon, Moon, Search, Sun } from 'lucide-react'
import { type ReactNode, useState } from 'react'
import { NavLink, Outlet, useLocation } from 'react-router-dom'
import { CommandMenu } from '@/components/command-menu'
import { Button } from '@/components/ui/button'
import { type KindKey, useListing, useOverview } from '@/lib/api'
import { itemCategories } from '@/lib/categories'
import { first } from '@/lib/facts'
import { kindByKey } from '@/lib/kinds'
import { useTheme } from '@/lib/theme'
import { cn } from '@/lib/utils'

const linkClass = (active: boolean) =>
  cn(
    'flex items-center gap-2.5 rounded-md px-2.5 py-1.5 text-sm text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground',
    active && 'bg-accent font-medium text-accent-foreground',
  )

function Group({ title, children }: { title: string; children: ReactNode }) {
  const [open, setOpen] = useState(true)
  return (
    <div className="mt-4">
      <button onClick={() => setOpen(!open)} className="flex w-full items-center justify-between px-2.5 py-1 text-xs font-medium text-muted-foreground/80 hover:text-foreground">
        {title}
        <ChevronDown className={cn('size-3.5 transition-transform', !open && '-rotate-90')} />
      </button>
      {open && <div className="mt-1 flex flex-col gap-0.5">{children}</div>}
    </div>
  )
}

function Count({ value }: { value?: number }) {
  return value === undefined ? null : <span className="ml-auto font-mono text-xs tabular-nums text-muted-foreground/60">{value}</span>
}

export function AppShell() {
  const [searching, setSearching] = useState(false)
  const { theme, toggle } = useTheme()
  const { data: overview } = useOverview()
  const { data: items } = useListing('item')
  const location = useLocation()
  const category = new URLSearchParams(location.search).get('category')

  const countFor = (types: string[]) => items?.rows.filter((row) => types.includes(String(first(row.facts, 'item.type')))).length

  const kindLink = (key: KindKey) => {
    const kind = kindByKey(key)!
    return (
      <NavLink to={`/${key}`} className={({ isActive }) => linkClass(isActive)}>
        <kind.icon className="size-4" /> {kind.plural}
        <Count value={overview?.counts[key]} />
      </NavLink>
    )
  }

  return (
    <div className="flex min-h-svh">
      <aside className="sticky top-0 hidden h-svh w-64 shrink-0 flex-col border-r bg-sidebar md:flex">
        <NavLink to="/" className="flex h-14 items-center gap-2 border-b px-4">
          <div className="flex size-7 items-center justify-center rounded-md bg-primary text-primary-foreground">
            <BookOpen className="size-4" />
          </div>
          <span className="font-semibold tracking-tight">Lorekeeper</span>
          <span className="ml-auto rounded-md border px-1.5 py-0.5 text-[10px] text-muted-foreground">AQW wiki</span>
        </NavLink>
        <nav className="flex flex-1 flex-col overflow-y-auto p-3">
          <NavLink to="/" end className={({ isActive }) => linkClass(isActive)}>
            <Home className="size-4" /> Home
          </NavLink>
          <Group title="World">
            {kindLink('region')}
            {kindLink('map')}
            {kindLink('monster')}
            {kindLink('npc')}
            {kindLink('quest')}
            {kindLink('faction')}
            <NavLink to="/atlas" className={({ isActive }) => linkClass(isActive)}>
              <MapIcon className="size-4" /> World map
            </NavLink>
          </Group>
          <Group title="Items">
            <NavLink to="/item" end className={() => linkClass(location.pathname === '/item' && category === null)}>
              All items <Count value={items?.rows.length} />
            </NavLink>
            {itemCategories.map((c) => (
              <NavLink key={c.key} to={`/item?category=${c.key}`} className={() => linkClass(location.pathname === '/item' && category === c.key)}>
                {c.label} <Count value={countFor(c.types)} />
              </NavLink>
            ))}
          </Group>
          <Group title="Characters">{kindLink('class_name')}</Group>
        </nav>
      </aside>

      <div className="flex min-w-0 flex-1 flex-col">
        <header className="sticky top-0 z-10 flex h-14 items-center gap-3 border-b bg-background/80 px-4 backdrop-blur md:px-8">
          <button
            onClick={() => setSearching(true)}
            className="flex h-9 w-full max-w-md items-center gap-2 rounded-md border bg-muted/40 px-3 text-sm text-muted-foreground transition-colors hover:bg-muted"
          >
            <Search className="size-4" />
            Search maps, monsters, items, quests…
            <kbd className="ml-auto rounded border bg-background px-1.5 font-mono text-[10px]">Ctrl K</kbd>
          </button>
          <Button variant="ghost" size="icon" className="ml-auto" onClick={toggle} aria-label="Toggle theme">
            {theme === 'dark' ? <Sun /> : <Moon />}
          </Button>
        </header>
        <main className="mx-auto w-full max-w-6xl flex-1 px-4 py-8 md:px-8">
          <Outlet />
        </main>
      </div>

      <CommandMenu open={searching} onOpenChange={setSearching} />
    </div>
  )
}
