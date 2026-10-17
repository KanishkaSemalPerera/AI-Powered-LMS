import CategoryIcon from '@/components/ui/CategoryIcon'
import { cn } from '@/utils/cn'

const gradients = [
  'from-brand-500 to-violet-600',
  'from-sky-500 to-indigo-600',
  'from-emerald-500 to-teal-600',
  'from-amber-500 to-rose-500',
  'from-fuchsia-500 to-purple-600',
  'from-cyan-500 to-blue-600',
]

/**
 * Course image in a 16:9 frame. Courses without an uploaded thumbnail get a
 * consistent gradient with their category icon instead of a broken image.
 */
export default function CourseThumbnail({ course, className }) {
  if (course.thumbnail_url) {
    return (
      <div className={cn('aspect-video overflow-hidden bg-slate-100 dark:bg-slate-800', className)}>
        <img src={course.thumbnail_url} alt="" loading="lazy" className="size-full object-cover" />
      </div>
    )
  }

  const icon = course.category?.icon
  const gradient = gradients[(course.category?.id ?? course.id ?? 0) % gradients.length]

  return (
    <div
      aria-hidden="true"
      className={cn('relative flex aspect-video items-center justify-center overflow-hidden bg-gradient-to-br', gradient, className)}
    >
      <CategoryIcon name={icon} className="absolute -bottom-6 -right-6 size-32 text-white/15" strokeWidth={1.5} />
      <CategoryIcon name={icon} className="size-12 text-white/90" strokeWidth={1.5} />
    </div>
  )
}
