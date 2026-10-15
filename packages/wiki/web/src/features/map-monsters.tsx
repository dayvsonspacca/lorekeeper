import { SubjectLink } from '@/components/subject-link'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { type Fact, useListing } from '@/lib/api'
import { first } from '@/lib/facts'
import { formatNumber } from '@/lib/format'

export function MapMonsters({ facts }: { facts: Fact[] }) {
  const { data } = useListing('monster')
  const byId = new Map((data?.rows ?? []).map((row) => [row.id, row]))

  return (
    <Card>
      <CardHeader><CardTitle className="text-base">Monsters</CardTitle></CardHeader>
      <CardContent className="px-0">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead className="pl-6">Monster</TableHead>
              <TableHead className="w-20">Level</TableHead>
              <TableHead className="w-24">Health</TableHead>
              <TableHead className="w-28 pr-6">Race</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {facts.map((fact, index) => {
              const row = fact.ref ? byId.get(fact.ref.id) : undefined
              const health = row ? first(row.facts, 'attribute.health') : undefined
              return (
                <TableRow key={index}>
                  <TableCell className="pl-6">{fact.ref ? <SubjectLink subject={fact.ref} /> : String(fact.value)}</TableCell>
                  <TableCell className="tabular-nums">{row ? String(first(row.facts, 'monster.level') ?? '—') : '—'}</TableCell>
                  <TableCell className="tabular-nums">{typeof health === 'number' ? formatNumber(health) : '—'}</TableCell>
                  <TableCell className="pr-6 text-muted-foreground">{row ? String(first(row.facts, 'monster.race') ?? '—') : '—'}</TableCell>
                </TableRow>
              )
            })}
          </TableBody>
        </Table>
      </CardContent>
    </Card>
  )
}
