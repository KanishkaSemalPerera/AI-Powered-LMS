import { cn } from '@/utils/cn'

const base =
  'inline-flex items-center justify-center gap-2 rounded-lg font-medium transition-colors ' +
  'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500 ' +
  'disabled:pointer-events-none disabled:opacity-60 select-none whitespace-nowrap'

const variants = {
  primary: 'bg-brand-600 text-white shadow-sm hover:bg-brand-700 dark:bg-brand-500 dark:hover:bg-brand-400',
  secondary:
    'bg-slate-100 text-slate-900 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700',
  outline:
    'border border-slate-300 bg-transparent text-slate-800 hover:bg-slate-50 ' +
    'dark:border-slate-700 dark:text-slate-100 dark:hover:bg-slate-900',
  ghost: 'text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800',
  danger: 'bg-red-600 text-white shadow-sm hover:bg-red-700',
}

const sizes = {
  sm: 'h-8 px-3 text-sm',
  md: 'h-10 px-4 text-sm',
  lg: 'h-12 px-6 text-base',
  icon: 'h-10 w-10',
}

/** Button classes, also used to style <Link> elements as buttons. */
export function buttonClasses({ variant = 'primary', size = 'md', className } = {}) {
  return cn(base, variants[variant], sizes[size], className)
}
