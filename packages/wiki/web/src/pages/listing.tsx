import { useMemo, useState } from 'react'
import { useParams, useSearchParams } from 'react-router-dom'
import { Breadcrumbs } from '@/components/breadcrumbs'
import { DataTable } from '@/components/data-table'
import { Input } from '@/components/ui/input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Skeleton } from '@/components/ui/skeleton'
import { columns, displayName, facets, type Row } from '@/features/columns'
import { type KindKey, subjectPath, useAtlas, useListing } from '@/lib/api'
import { itemCategories } from '@/lib/categories'
import { first } from '@/lib/facts'
import { kindByKey } from '@/lib/kinds'
import { NotFound } from './not-found'

export function ListingPage() {
  const { kind: key = '' } = useParams()
  const kind = kindByKey(key)
  return kind ? <Listing kind={kind.key} key={kind.key} /> : <NotFound />
}

const ALL = '__all__'
const world: KindKey[] = ['map', 'monster', 'npc', 'shop', 'quest', 'region', 'faction']

function Listing({ kind }: { kind: KindKey }) {
  const meta = kindByKey(kind)!
  const [params] = useSearchParams()
  const category = itemCategories.find((c) => c.key === params.get('category'))
  const { data, isLoading } = useListing(kind)
  const { data: atlas } = useAtlas()
  const [query, setQuery] = useState('')
  const [filters, setFilters] = useState<Record<string, string>>({})

  const rows = useMemo<Row[]>(() => {
    const maps = new Map((atlas?.nodes ?? []).map((n) => [n.id, n]))
    return (data?.rows ?? [])
      .map((row) => ({ ...row, kind, map: kind === 'map' ? maps.get(row.id) : undefined }))
      .filter((row) => !category || category.types.includes(String(first(row.facts, 'item.type'))))
  }, [data, atlas, kind, category])

  const options = useMemo(() => {
    const result: Record<string, string[]> = {}
    for (const facet of facets[kind] ?? []) {
      result[facet.type] = [...new Set(rows.map((r) => first(r.facts, facet.type)).filter((v) => v !== undefined).map(String))].sort()
    }
    return result
  }, [rows, kind])

  const filtered = useMemo(() => {
    const needle = query.trim().toLowerCase()
    return rows.filter(
      (row) =>
        (needle === '' || displayName(row).toLowerCase().includes(needle) || row.id.toLowerCase().includes(needle)) &&
        Object.entries(filters).every(([type, wanted]) => wanted === ALL || String(first(row.facts, type)) === wanted),
    )
  }, [rows, query, filters])

  const title = category?.label ?? meta.plural
  const section = kind === 'item' ? { label: 'Items', to: '/item' } : world.includes(kind) ? { label: 'World' } : { label: 'Characters' }

  return (
    <div className="space-y-6">
      <Breadcrumbs items={[{ label: 'Home', to: '/' }, section, { label: title }]} />
      <div className="flex flex-wrap items-end justify-between gap-4 border-b pb-5">
        <div>
          <h1 className="text-3xl font-semibold tracking-tight">{title}</h1>
          <p className="text-sm text-muted-foreground">{meta.description} · {rows.length.toLocaleString()} pages</p>
        </div>
        <div className="flex flex-wrap items-center gap-2">
          <Input placeholder="Search…" value={query} onChange={(e) => setQuery(e.target.value)} className="w-56" />
          {(facets[kind] ?? []).map((facet) => (
            <Select key={facet.type} value={filters[facet.type] ?? ALL} onValueChange={(v) => setFilters((f) => ({ ...f, [facet.type]: v }))}>
              <SelectTrigger className="w-40">
                <SelectValue placeholder={facet.label} />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value={ALL}>Any {facet.label.toLowerCase()}</SelectItem>
                {options[facet.type]?.map((option) => (
                  <SelectItem key={option} value={option}>{option}</SelectItem>
                ))}
              </SelectContent>
            </Select>
          ))}
        </div>
      </div>
      {isLoading ? (
        <div className="space-y-2">{Array.from({ length: 8 }, (_, i) => <Skeleton key={i} className="h-10 w-full" />)}</div>
      ) : (
        <DataTable rows={filtered} columns={columns[kind]} empty="Nothing matches your search." href={(row) => subjectPath({ kind: row.kind, id: row.id })} />
      )}
    </div>
  )
}
