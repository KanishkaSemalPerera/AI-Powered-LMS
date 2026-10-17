import clsx from 'clsx'

/** Joins conditional class names: cn('a', isActive && 'b', { c: flag }). */
export function cn(...inputs) {
  return clsx(inputs)
}
