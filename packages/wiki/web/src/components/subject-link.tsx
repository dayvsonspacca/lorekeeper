import { Link } from 'react-router-dom'
import { ItemHover } from '@/features/item-hover'
import { type SubjectRef, subjectPath } from '@/lib/api'
import { kindByKey } from '@/lib/kinds'
import { cn } from '@/lib/utils'

export function SubjectLink({ subject, className, icon = false }: { subject: SubjectRef; className?: string; icon?: boolean }) {
  const Icon = kindByKey(subject.kind)?.icon

  const link = (
    <Link
      to={subjectPath(subject)}
      className={cn('inline-flex items-center gap-1.5 font-medium text-foreground underline-offset-4 hover:underline', className)}
    >
      {icon && Icon && <Icon className="size-3.5 text-muted-foreground" />}
      {subject.name}
    </Link>
  )

  return subject.kind === 'item' ? <ItemHover item={subject}>{link}</ItemHover> : link
}
