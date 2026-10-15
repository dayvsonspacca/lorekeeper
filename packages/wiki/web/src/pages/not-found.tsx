import { Compass } from 'lucide-react'
import { Link } from 'react-router-dom'
import { Button } from '@/components/ui/button'

export function NotFound() {
  return (
    <div className="flex flex-col items-center justify-center gap-4 py-24 text-center">
      <Compass className="size-10 text-muted-foreground" />
      <div>
        <h1 className="text-xl font-semibold">Nothing known here yet</h1>
        <p className="text-sm text-muted-foreground">Either this page does not exist or the miners have not seen it.</p>
      </div>
      <Button asChild variant="outline">
        <Link to="/">Back to overview</Link>
      </Button>
    </div>
  )
}
