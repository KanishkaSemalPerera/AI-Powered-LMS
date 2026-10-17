import { ChevronDown } from 'lucide-react'
import { cn } from '@/utils/cn'
import { inputClasses } from './Input'

/**
 * Native <select> (best accessibility and mobile UX), styled like Input.
 * options: [{ value, label }]
 */
export default function Select({ options = [], placeholder, className, invalid, ...props }) {
  return (
    <div className="relative">
      <select
        aria-invalid={invalid || undefined}
        className={cn(inputClasses, 'h-10 appearance-none pr-9', className)}
        {...props}
      >
        {placeholder !== undefined && <option value="">{placeholder}</option>}
        {options.map((option) => (
          <option key={option.value} value={option.value}>
            {option.label}
          </option>
        ))}
      </select>
      <ChevronDown
        className="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-slate-500"
        aria-hidden="true"
      />
    </div>
  )
}
