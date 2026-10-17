import { RefreshCw, TriangleAlert } from 'lucide-react'
import { getErrorMessage } from '@/utils/apiError'
import { cn } from '@/utils/cn'
import Button from './Button'

/** Error panel with an optional retry button, typically fed a React Query error. */
export default function ErrorState({ error, title = 'Something went wrong', onRetry, className }) {
  return (
    <div
      role="alert"
      className={cn(
        'flex flex-col items-center justify-center rounded-xl border border-red-200 bg-red-50 px-6 py-10 text-center',
        'dark:border-red-900/60 dark:bg-red-950/30',
        className,
      )}
    >
      <TriangleAlert className="mb-3 size-9 text-red-500" aria-hidden="true" />
      <h3 className="font-semibold text-red-900 dark:text-red-200">{title}</h3>
      <p className="mt-1 max-w-md text-sm text-red-700 dark:text-red-300">{getErrorMessage(error)}</p>
      {onRetry && (
        <Button variant="outline" size="sm" className="mt-5" onClick={onRetry}>
          <RefreshCw className="size-4" aria-hidden="true" />
          Try again
        </Button>
      )}
    </div>
  )
}
