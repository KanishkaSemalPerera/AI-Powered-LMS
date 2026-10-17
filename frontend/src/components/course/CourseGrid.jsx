import { SearchX } from 'lucide-react'
import EmptyState from '@/components/ui/EmptyState'
import ErrorState from '@/components/ui/ErrorState'
import { cn } from '@/utils/cn'
import CourseCard from './CourseCard'
import CourseCardSkeleton from './CourseCardSkeleton'

/** Course cards with built-in loading, error and empty states. */
export default function CourseGrid({
  courses,
  isLoading,
  error,
  onRetry,
  skeletonCount = 6,
  emptyTitle = 'No courses found',
  emptyDescription,
  emptyAction,
  className,
}) {
  const grid = cn('grid gap-6 sm:grid-cols-2 xl:grid-cols-3', className)

  if (isLoading) {
    return (
      <div className={grid} aria-busy="true" aria-label="Loading courses">
        {Array.from({ length: skeletonCount }, (_, i) => (
          <CourseCardSkeleton key={i} />
        ))}
      </div>
    )
  }

  if (error) {
    return <ErrorState title="Couldn't load courses" error={error} onRetry={onRetry} />
  }

  if (!courses?.length) {
    return <EmptyState icon={SearchX} title={emptyTitle} description={emptyDescription} action={emptyAction} />
  }

  return (
    <div className={grid}>
      {courses.map((course) => (
        <CourseCard key={course.id} course={course} />
      ))}
    </div>
  )
}
