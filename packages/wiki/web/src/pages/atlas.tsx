import { Network } from 'lucide-react'
import { useEffect, useMemo, useRef, useState } from 'react'
import ForceGraph2D, { type ForceGraphMethods, type NodeObject } from 'react-force-graph-2d'
import { useNavigate } from 'react-router-dom'
import { PageHeader } from '@/components/page-header'
import { Card } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Skeleton } from '@/components/ui/skeleton'
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group'
import { type AtlasNode, useAtlas } from '@/lib/api'
import { useTheme } from '@/lib/theme'

type GraphNode = NodeObject & AtlasNode & { degree: number }

const palette = {
  visited: '#10b981',
  unvisited: '#71717a',
  member: '#f59e0b',
  unavailable: '#ef4444',
  beyond: '#8b5cf6',
}

const colorOf = (node: AtlasNode) =>
  node.unavailable ? palette.unavailable : node.memberOnly ? palette.member : node.visited ? palette.visited : node.mapId === null ? palette.beyond : palette.unvisited

export function AtlasPage() {
  const { data, isLoading } = useAtlas()
  const navigate = useNavigate()
  const { theme } = useTheme()
  const graph = useRef<ForceGraphMethods<GraphNode> | undefined>(undefined)
  const container = useRef<HTMLDivElement>(null)
  const [size, setSize] = useState({ width: 800, height: 600 })
  const [scope, setScope] = useState('connected')
  const [query, setQuery] = useState('')

  useEffect(() => {
    if (!container.current) return
    const observer = new ResizeObserver(([entry]) => setSize({ width: entry.contentRect.width, height: entry.contentRect.height }))
    observer.observe(container.current)
    return () => observer.disconnect()
  }, [])

  const graphData = useMemo(() => {
    if (!data) return { nodes: [] as GraphNode[], links: [] as { source: string; target: string }[] }
    const degree = new Map<string, number>()
    for (const link of data.links) {
      degree.set(link.source, (degree.get(link.source) ?? 0) + 1)
      degree.set(link.target, (degree.get(link.target) ?? 0) + 1)
    }
    const nodes = data.nodes
      .map((node): GraphNode => ({ ...node, degree: degree.get(node.id) ?? 0 }))
      .filter((node) => scope === 'all' || node.degree > 0)
    return { nodes, links: data.links.map((link) => ({ ...link })) }
  }, [data, scope])

  const match = query.trim().toLowerCase()

  useEffect(() => {
    if (!match || !graph.current) return
    const node = graphData.nodes.find((n) => n.id.toLowerCase() === match || (n.title ?? '').toLowerCase() === match)
    if (node && node.x !== undefined && node.y !== undefined) {
      graph.current.centerAt(node.x, node.y, 600)
      graph.current.zoom(4, 600)
    }
  }, [match, graphData.nodes])

  const text = theme === 'dark' ? '#e4e4e7' : '#18181b'

  return (
    <div className="flex h-[calc(100svh-7.5rem)] flex-col gap-4">
      <PageHeader
        icon={Network}
        title="World map"
        description={data ? `How ${data.nodes.length} maps connect to each other · click a map to open its page` : 'Loading the world…'}
        actions={
          <div className="flex flex-wrap items-center gap-2">
            <Input placeholder="Find a map…" value={query} onChange={(e) => setQuery(e.target.value)} className="w-44" />
            <ToggleGroup type="single" variant="outline" value={scope} onValueChange={(v) => v && setScope(v)}>
              <ToggleGroupItem value="connected">Connected</ToggleGroupItem>
              <ToggleGroupItem value="all">All maps</ToggleGroupItem>
            </ToggleGroup>
          </div>
        }
      />
      <div className="flex flex-wrap gap-4 text-xs text-muted-foreground">
        {[
          ['Explored', palette.visited],
          ['Not explored yet', palette.unvisited],
          ['Hidden (not on the travel map)', palette.beyond],
          ['Members only', palette.member],
          ['Unavailable', palette.unavailable],
        ].map(([label, color]) => (
          <span key={label} className="flex items-center gap-1.5">
            <span className="size-2.5 rounded-full" style={{ background: color }} /> {label}
          </span>
        ))}
      </div>
      <Card ref={container} className="relative flex-1 overflow-hidden p-0">
        {isLoading ? (
          <Skeleton className="size-full" />
        ) : (
          <ForceGraph2D<GraphNode>
            ref={graph}
            width={size.width}
            height={size.height}
            graphData={graphData}
            backgroundColor="transparent"
            linkColor={() => (theme === 'dark' ? 'rgba(255,255,255,0.15)' : 'rgba(0,0,0,0.15)')}
            linkDirectionalArrowLength={3}
            linkDirectionalArrowRelPos={1}
            cooldownTicks={120}
            onEngineStop={() => graph.current?.zoomToFit(500, 60)}
            nodeLabel={(node) => `${node.title ?? node.id}${node.regions.length ? ` · ${node.regions[0]}` : ''}`}
            onNodeClick={(node) => navigate(`/map/${encodeURIComponent(node.id)}`)}
            nodeCanvasObject={(node, ctx, scale) => {
              const radius = 3 + Math.sqrt(node.degree) * 1.6
              const highlighted = match !== '' && (node.id.toLowerCase().includes(match) || (node.title ?? '').toLowerCase().includes(match))
              ctx.beginPath()
              ctx.arc(node.x ?? 0, node.y ?? 0, radius, 0, 2 * Math.PI)
              ctx.fillStyle = colorOf(node)
              ctx.globalAlpha = match === '' || highlighted ? 1 : 0.2
              ctx.fill()
              if (highlighted) {
                ctx.lineWidth = 2 / scale
                ctx.strokeStyle = text
                ctx.stroke()
              }
              if (scale > 1.6 || highlighted || node.degree > 6) {
                ctx.font = `${11 / scale}px Inter Variable, sans-serif`
                ctx.textAlign = 'center'
                ctx.fillStyle = text
                ctx.fillText(node.title ?? node.id, node.x ?? 0, (node.y ?? 0) + radius + 10 / scale)
              }
              ctx.globalAlpha = 1
            }}
          />
        )}
      </Card>
    </div>
  )
}
