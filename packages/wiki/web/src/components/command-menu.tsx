import { useEffect, useMemo, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import {
  CommandDialog,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList,
  CommandSeparator,
} from '@/components/ui/command'
import { subjectPath, useSubjects } from '@/lib/api'
import { kinds } from '@/lib/kinds'
import { Network } from 'lucide-react'

export function CommandMenu({ open, onOpenChange }: { open: boolean; onOpenChange: (open: boolean) => void }) {
  const navigate = useNavigate()
  const { data: subjects = [] } = useSubjects()
  const [query, setQuery] = useState('')

  useEffect(() => {
    const onKey = (event: KeyboardEvent) => {
      if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault()
        onOpenChange(!open)
      }
    }
    window.addEventListener('keydown', onKey)
    return () => window.removeEventListener('keydown', onKey)
  }, [open, onOpenChange])

  const groups = useMemo(() => {
    const needle = query.trim().toLowerCase()
    return kinds
      .map((kind) => ({
        kind,
        items: subjects
          .filter((s) => s.kind === kind.key)
          .filter((s) => needle === '' || s.name.toLowerCase().includes(needle) || s.id.toLowerCase().includes(needle))
          .slice(0, needle === '' ? 5 : 30),
      }))
      .filter((group) => group.items.length > 0)
  }, [subjects, query])

  const go = (path: string) => {
    onOpenChange(false)
    setQuery('')
    navigate(path)
  }

  return (
    <CommandDialog open={open} onOpenChange={onOpenChange} title="Search" description="Search everything Lorekeeper knows">
      <CommandInput placeholder={`Search ${subjects.length.toLocaleString()} maps, monsters, items, quests…`} value={query} onValueChange={setQuery} />
      <CommandList className="max-h-[420px]">
        <CommandEmpty>Nothing found.</CommandEmpty>
        {query === '' && (
          <>
            <CommandGroup heading="Go to">
              {kinds.map((kind) => (
                <CommandItem key={kind.key} value={`go ${kind.plural}`} onSelect={() => go(`/${kind.key}`)}>
                  <kind.icon /> {kind.plural}
                </CommandItem>
              ))}
              <CommandItem value="go world map atlas" onSelect={() => go('/atlas')}>
                <Network /> World map
              </CommandItem>
            </CommandGroup>
            <CommandSeparator />
          </>
        )}
        {groups.map(({ kind, items }) => (
          <CommandGroup key={kind.key} heading={kind.plural}>
            {items.map((s) => (
              <CommandItem key={`${s.kind}/${s.id}`} value={`${s.kind} ${s.id} ${s.name}`} onSelect={() => go(subjectPath(s))}>
                <kind.icon />
                <span className="truncate">{s.name}</span>
                {s.name !== s.id && <span className="ml-auto font-mono text-xs text-muted-foreground">{s.id}</span>}
              </CommandItem>
            ))}
          </CommandGroup>
        ))}
      </CommandList>
    </CommandDialog>
  )
}
