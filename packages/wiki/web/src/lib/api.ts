import { useQuery } from '@tanstack/react-query'

export type KindKey = 'map' | 'region' | 'monster' | 'item' | 'quest' | 'npc' | 'faction' | 'class_name'

export interface SubjectRef {
  kind: string
  id: string
  name: string
}

export interface Fact {
  type: string
  value: unknown
  ref: SubjectRef | null
  source: string
  firstSeen: string
  lastSeen: string
  timesSeen: number
}

export interface FactWithSubject extends Fact {
  subject: SubjectRef
}

export interface Overview {
  counts: Record<KindKey, number>
  sources: Record<string, number>
  newPages: { subject: SubjectRef; added: string }[]
}

export interface ListingRow {
  id: string
  name: string
  facts: Record<string, unknown[]>
}

export interface Listing {
  kind: KindKey
  rows: ListingRow[]
}

export interface Detail {
  subject: SubjectRef
  facts: Fact[]
  references: FactWithSubject[]
}

export interface AtlasNode {
  id: string
  title: string | null
  description: string | null
  minLevel: number | null
  maxLevel: number | null
  mapId: number | null
  visited: boolean
  memberOnly: boolean
  unavailable: boolean
  monsters: number
  regions: string[]
}

export interface Atlas {
  nodes: AtlasNode[]
  links: { source: string; target: string }[]
}

export class NotFoundError extends Error {}

async function get<T>(path: string): Promise<T> {
  const response = await fetch(`/api${path}`)
  if (response.status === 404) throw new NotFoundError(path)
  if (!response.ok) throw new Error(`${response.status} ${path}`)
  return (await response.json()) as T
}

const forever = { staleTime: 60_000 }

export const useOverview = () => useQuery({ queryKey: ['overview'], queryFn: () => get<Overview>('/overview'), ...forever })
export const useSubjects = () => useQuery({ queryKey: ['subjects'], queryFn: () => get<SubjectRef[]>('/subjects'), ...forever })
export const useAtlas = () => useQuery({ queryKey: ['atlas'], queryFn: () => get<Atlas>('/atlas'), ...forever })
export const useListing = (kind: KindKey) =>
  useQuery({ queryKey: ['listing', kind], queryFn: () => get<Listing>(`/${kind}`), ...forever })
export const useDetail = (kind: string, id: string) =>
  useQuery({
    queryKey: ['detail', kind, id],
    queryFn: () => get<Detail>(`/${kind}/${encodeURIComponent(id)}`),
    retry: (count, error) => !(error instanceof NotFoundError) && count < 2,
    ...forever,
  })

export const subjectPath = (ref: { kind: string; id: string }) => `/${ref.kind}/${encodeURIComponent(ref.id)}`
