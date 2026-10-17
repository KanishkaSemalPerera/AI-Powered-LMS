import { cloneElement, isValidElement, useId } from 'react'
import { cn } from '@/utils/cn'

/**
 * Label + control + hint + error, wired up for screen readers.
 * The single child control receives id, aria-invalid and aria-describedby.
 *
 * <FormField label="Email" error={errors.email?.message}>
 *   <Input type="email" {...register('email')} />
 * </FormField>
 */
export default function FormField({ label, error, hint, required, className, children }) {
  const id = useId()
  const hintId = hint ? `${id}-hint` : undefined
  const errorId = error ? `${id}-error` : undefined
  const describedBy = [hintId, errorId].filter(Boolean).join(' ') || undefined

  const control = isValidElement(children)
    ? cloneElement(children, {
        id,
        invalid: Boolean(error),
        'aria-describedby': describedBy,
        'aria-required': required || undefined,
      })
    : children

  return (
    <div className={cn('space-y-1.5', className)}>
      {label && (
        <div className="flex items-baseline gap-0.5">
          <label htmlFor={id} className="block text-sm font-medium text-slate-700 dark:text-slate-200">
            {label}
          </label>
          {/* Outside the <label> so screen readers announce "Email", not "Email star". */}
          {required && (
            <span className="text-sm text-red-500" aria-hidden="true">
              *
            </span>
          )}
        </div>
      )}
      {control}
      {hint && !error && (
        <p id={hintId} className="text-xs text-slate-500 dark:text-slate-400">
          {hint}
        </p>
      )}
      {error && (
        <p id={errorId} role="alert" className="text-xs font-medium text-red-600 dark:text-red-400">
          {error}
        </p>
      )}
    </div>
  )
}
