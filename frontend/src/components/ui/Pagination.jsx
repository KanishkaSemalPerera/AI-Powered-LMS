import { ChevronLeft, ChevronRight } from 'lucide-react'
import { cn } from '@/utils/cn'

/** Page numbers to show, with "…" gaps: 1 … 4 5 6 … 12 */
function pageWindow(current, last) {
  const pages = new Set([1, last, current - 1, current, current + 1])
  const sorted = [...pages].filter((page) => page >= 1 && page <= last).sort((a, b) => a - b)

  return sorted.flatMap((page, index) => (index > 0 && page - sorted[index - 1] > 1 ? ['gap-' + page, page] : [page]))
}

const pageClass =
  'inline-flex h-9 min-w-9 items-center justify-center rounded-lg px-2 text-sm font-medium transition-colors ' +
  'disabled:pointer-events-none disabled:opacity-40'

/** meta: the API's pagination meta { current_page, last_page, total, from, to } */
export default function Pagination({ meta, onPageChange, className }) {
  if (!meta || meta.last_page <= 1) return null

  const { current_page: current, last_page: last } = meta

  return (
    <nav aria-label="Pagination" className={cn('flex flex-wrap items-center justify-between gap-3', className)}>
      <p className="text-sm text-slate-500 dark:text-slate-400">
        Showing <span className="font-medium text-slate-900 dark:text-white">{meta.from}</span>–
        <span className="font-medium text-slate-900 dark:text-white">{meta.to}</span> of{' '}
        <span className="font-medium text-slate-900 dark:text-white">{meta.total}</span>
      </p>
      <div className="flex items-center gap-1">
        <button
          type="button"
          className={cn(pageClass, 'hover:bg-slate-100 dark:hover:bg-slate-800')}
          onClick={() => onPageChange(current - 1)}
          disabled={current <= 1}
          aria-label="Previous page"
        >
          <ChevronLeft className="size-4" aria-hidden="true" />
        </button>
        {pageWindow(current, last).map((page) =>
          typeof page === 'string' ? (
            <span key={page} className="px-1 text-slate-400" aria-hidden="true">
              …
            </span>
          ) : (
            <button
              key={page}
              type="button"
              onClick={() => onPageChange(page)}
              aria-current={page === current ? 'page' : undefined}
              className={cn(
                pageClass,
                page === current
                  ? 'bg-brand-600 text-white'
                  : 'hover:bg-slate-100 dark:hover:bg-slate-800',
              )}
            >
              {page}
            </button>
          ),
        )}
        <button
          type="button"
          className={cn(pageClass, 'hover:bg-slate-100 dark:hover:bg-slate-800')}
          onClick={() => onPageChange(current + 1)}
          disabled={current >= last}
          aria-label="Next page"
        >
          <ChevronRight className="size-4" aria-hidden="true" />
        </button>
      </div>
    </nav>
  )
}
