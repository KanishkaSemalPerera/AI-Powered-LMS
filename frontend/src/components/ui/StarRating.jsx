import { Star } from 'lucide-react'
import { cn } from '@/utils/cn'

/** Read-only star rating with fractional fill, e.g. 4.5 → four and a half stars. */
export default function StarRating({ value = 0, count, size = 'sm', className }) {
  const iconSize = size === 'lg' ? 'size-5' : 'size-4'
  const rounded = Math.round(value * 10) / 10

  return (
    <span className={cn('inline-flex items-center gap-1.5', className)}>
      {value > 0 && <span className="text-sm font-semibold text-amber-600 dark:text-amber-400">{rounded.toFixed(1)}</span>}
      <span className="relative inline-flex" role="img" aria-label={`Rated ${rounded} out of 5`}>
        <span className="flex text-slate-300 dark:text-slate-600" aria-hidden="true">
          {[0, 1, 2, 3, 4].map((i) => (
            <Star key={i} className={cn(iconSize, 'fill-current')} />
          ))}
        </span>
        <span
          className="absolute inset-y-0 left-0 flex overflow-hidden text-amber-500"
          style={{ width: `${(Math.min(Math.max(value, 0), 5) / 5) * 100}%` }}
          aria-hidden="true"
        >
          {[0, 1, 2, 3, 4].map((i) => (
            <Star key={i} className={cn(iconSize, 'shrink-0 fill-current')} />
          ))}
        </span>
      </span>
      {count !== undefined && (
        <span className="text-xs text-slate-500 dark:text-slate-400">({count === 0 ? 'no ratings yet' : count})</span>
      )}
    </span>
  )
}
