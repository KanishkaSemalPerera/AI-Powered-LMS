import { Inbox } from 'lucide-react'
import { cn } from '@/utils/cn'

export default function EmptyState({ icon: Icon = Inbox, title, description, action, className }) {
  return (
    <div
      className={cn(
        'flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 px-6 py-12 text-center dark:border-slate-700',
        className,
      )}
    >
      <Icon className="mb-3 size-10 text-slate-400" aria-hidden="true" />
      <h3 className="text-base font-semibold">{title}</h3>
      {description && <p className="mt-1 max-w-md text-sm text-slate-500 dark:text-slate-400">{description}</p>}
      {action && <div className="mt-5">{action}</div>}
    </div>
  )
}
