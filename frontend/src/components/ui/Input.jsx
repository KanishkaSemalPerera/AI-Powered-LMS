import { cn } from '@/utils/cn'

export const inputClasses =
  'block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm ' +
  'placeholder:text-slate-400 transition-colors ' +
  'focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 ' +
  'disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500 ' +
  'aria-[invalid=true]:border-red-500 aria-[invalid=true]:focus:ring-red-500/30 ' +
  'dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500 dark:disabled:bg-slate-800'

/** Text input. React 19 passes `ref` through props, so React Hook Form's register() works directly. */
export default function Input({ className, invalid, ...props }) {
  return <input aria-invalid={invalid || undefined} className={cn(inputClasses, 'h-10', className)} {...props} />
}
