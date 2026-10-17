import { keepPreviousData, useQuery } from '@tanstack/react-query'
import { BookOpen, CircleCheck, CirclePlay } from 'lucide-react'
import { useState } from 'react'
import { Link } from 'react-router'
import { getMyCourses } from '@/api/enrollment'
import CourseThumbnail from '@/components/course/CourseThumbnail'
import { buttonClasses } from '@/components/ui/buttonStyles'
import EmptyState from '@/components/ui/EmptyState'
import ErrorState from '@/components/ui/ErrorState'
import Pagination from '@/components/ui/Pagination'
import ProgressBar from '@/components/ui/ProgressBar'
import Skeleton from '@/components/ui/Skeleton'
import { useDocumentTitle } from '@/hooks/useDocumentTitle'
import { cn } from '@/utils/cn'
import { formatDate } from '@/utils/format'
import { learnPath } from '@/utils/paths'

const TABS = [
  { value: '', label: 'All courses' },
  { value: 'in_progress', label: 'In progress' },
  { value: 'completed', label: 'Completed' },
]

export default function MyCoursesPage() {
  useDocumentTitle('My courses')
  const [status, setStatus] = useState('')
  const [page, setPage] = useState(1)

  const query = useQuery({
    queryKey: ['my-courses', { status, page }],
    queryFn: () => getMyCourses({ status, page, per_page: 9 }),
    placeholderData: keepPreviousData,
  })
  const enrollments = query.data?.data ?? []

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold tracking-tight">My courses</h1>
          <p className="text-sm text-slate-500 dark:text-slate-400">Everything you&apos;re enrolled in, most recent first.</p>
        </div>
        <Link to="/courses" className={buttonClasses({ variant: 'outline' })}>
          Browse more courses
        </Link>
      </div>

      <div role="tablist" aria-label="Filter courses" className="flex gap-1 overflow-x-auto">
        {TABS.map((tab) => (
          <button
            key={tab.value || 'all'}
            type="button"
            role="tab"
            aria-selected={status === tab.value}
            onClick={() => {
              setStatus(tab.value)
              setPage(1)
            }}
            className={cn(
              'shrink-0 rounded-full px-3 py-1.5 text-sm font-medium transition-colors',
              status === tab.value ? 'bg-brand-600 text-white' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800',
            )}
          >
            {tab.label}
          </button>
        ))}
      </div>

      {query.isPending ? (
        <div className="grid gap-6 sm:grid-cols-2 xl:grid-cols-3" aria-busy="true">
          {Array.from({ length: 3 }, (_, i) => (
            <Skeleton key={i} className="h-72" />
          ))}
        </div>
      ) : query.error ? (
        <ErrorState title="Couldn't load your courses" error={query.error} onRetry={() => query.refetch()} />
      ) : enrollments.length === 0 ? (
        <EmptyState
          icon={BookOpen}
          title={status ? 'Nothing here yet' : 'You haven’t enrolled in any courses yet'}
          description={status ? 'Courses will appear here as you make progress.' : 'Find a course you love and start learning today.'}
          action={
            <Link to="/courses" className={buttonClasses()}>
              Browse courses
            </Link>
          }
        />
      ) : (
        <div className={cn('grid gap-6 sm:grid-cols-2 xl:grid-cols-3', query.isPlaceholderData && 'opacity-60')}>
          {enrollments.map(({ id, course, progress_percent, completed_at, last_accessed_at, enrolled_at }) => (
            <article
              key={id}
              className="flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900"
            >
              <CourseThumbnail course={course} />
              <div className="flex flex-1 flex-col gap-3 p-4">
                <div>
                  <h2 className="line-clamp-2 font-semibold">{course.title}</h2>
                  <p className="mt-0.5 text-sm text-slate-500 dark:text-slate-400">{course.instructor?.name}</p>
                </div>
                <ProgressBar value={progress_percent} label={`Progress in ${course.title}`} showValue />
                <p className="text-xs text-slate-500 dark:text-slate-400">
                  {completed_at ? (
                    <span className="inline-flex items-center gap-1 font-medium text-emerald-600 dark:text-emerald-400">
                      <CircleCheck className="size-3.5" aria-hidden="true" />
                      Completed {formatDate(completed_at)}
                    </span>
                  ) : last_accessed_at ? (
                    `Last studied ${formatDate(last_accessed_at)}`
                  ) : (
                    `Enrolled ${formatDate(enrolled_at)}`
                  )}
                </p>
                <Link to={learnPath(course)} className={buttonClasses({ className: 'mt-auto w-full' })}>
                  <CirclePlay className="size-4" aria-hidden="true" />
                  {completed_at ? 'Review course' : progress_percent > 0 ? 'Continue learning' : 'Start course'}
                </Link>
              </div>
            </article>
          ))}
        </div>
      )}

      <Pagination meta={query.data?.meta} onPageChange={setPage} />
    </div>
  )
}
