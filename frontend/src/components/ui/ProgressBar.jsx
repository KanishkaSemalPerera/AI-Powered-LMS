import { cn } from '@/utils/cn'

/** Accessible progress bar; value is 0–100. */
export default function ProgressBar({ value = 0, label = 'Progress', showValue = false, className }) {
  const percent = Math.min(100, Math.max(0, Math.round(value)))

  return (
    <div className={cn('flex items-center gap-3', className)}>
      <div
        role="progressbar"
        aria-label={label}
        aria-valuenow={percent}
        aria-valuemin={0}
        aria-valuemax={100}
        className="h-2 flex-1 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800"
      >
        <div
          className={cn('h-full rounded-full transition-all', percent === 100 ? 'bg-emerald-500' : 'bg-brand-600')}
          style={{ width: `${percent}%` }}
        />
      </div>
      {showValue && <span className="w-10 text-right text-xs font-medium text-slate-600 dark:text-slate-300">{percent}%</span>}
    </div>
  )
}
