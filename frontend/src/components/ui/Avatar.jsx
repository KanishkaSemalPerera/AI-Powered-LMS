import { cn } from '@/utils/cn'

const sizes = {
  sm: 'size-8 text-xs',
  md: 'size-10 text-sm',
  lg: 'size-16 text-lg',
  xl: 'size-24 text-2xl',
}

const palette = [
  'bg-brand-100 text-brand-700 dark:bg-brand-900 dark:text-brand-200',
  'bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-200',
  'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
  'bg-rose-100 text-rose-700 dark:bg-rose-900 dark:text-rose-200',
  'bg-sky-100 text-sky-700 dark:bg-sky-900 dark:text-sky-200',
]

function initials(name = '') {
  const parts = name.trim().split(/\s+/).filter(Boolean)
  return ((parts[0]?.[0] ?? '') + (parts.length > 1 ? parts[parts.length - 1][0] : '')).toUpperCase() || '?'
}

/** Same name → same colour, so initials avatars stay consistent across the app. */
function colourFor(name = '') {
  const hash = [...name].reduce((sum, char) => sum + char.charCodeAt(0), 0)
  return palette[hash % palette.length]
}

export default function Avatar({ src, name, size = 'md', className }) {
  const base = cn('inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full font-semibold', sizes[size], className)

  if (src) {
    return <img src={src} alt={name ? `${name}'s profile photo` : ''} className={cn(base, 'object-cover')} />
  }

  return (
    <span className={cn(base, colourFor(name))} aria-hidden={name ? undefined : true} title={name}>
      {initials(name)}
    </span>
  )
}
