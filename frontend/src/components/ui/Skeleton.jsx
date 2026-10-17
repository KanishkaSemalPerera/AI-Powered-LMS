import { cn } from '@/utils/cn'

/** Pulsing placeholder shown while content loads. Size it with className. */
export default function Skeleton({ className }) {
  return (
    <div
      aria-hidden="true"
      className={cn('animate-pulse rounded-md bg-slate-200 dark:bg-slate-800', className)}
    />
  )
}
