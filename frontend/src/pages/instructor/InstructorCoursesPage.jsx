import { keepPreviousData, useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Eye, Library, Pencil, Plus, Search, Trash2, Users } from 'lucide-react'
import { useState } from 'react'
import { Link } from 'react-router'
import { toast } from 'sonner'
import { deleteCourse, getMyCourses } from '@/api/instructor'
import CourseStatusBadge from '@/components/course/CourseStatusBadge'
import CourseThumbnail from '@/components/course/CourseThumbnail'
import { buttonClasses } from '@/components/ui/buttonStyles'
import Button from '@/components/ui/Button'
import ConfirmDialog from '@/components/ui/ConfirmDialog'
import EmptyState from '@/components/ui/EmptyState'
import ErrorState from '@/components/ui/ErrorState'
import Input from '@/components/ui/Input'
import Pagination from '@/components/ui/Pagination'
import Skeleton from '@/components/ui/Skeleton'
import StarRating from '@/components/ui/StarRating'
import { useDebouncedValue } from '@/hooks/useDebouncedValue'
import { useDocumentTitle } from '@/hooks/useDocumentTitle'
import { getErrorMessage } from '@/utils/apiError'
import { cn } from '@/utils/cn'
import { COURSE_STATUS_TABS } from '@/utils/courseOptions'
import { formatDate, formatNumber, formatPrice, plural } from '@/utils/format'

/** Mirrors CoursePolicy::delete so the button only shows when the API would allow it. */
const canDelete = (course) => ['draft', 'rejected'].includes(course.status) && course.students_count === 0

export default function InstructorCoursesPage() {
  useDocumentTitle('My courses')
  const queryClient = useQueryClient()
  const [status, setStatus] = useState('')
  const [search, setSearch] = useState('')
  const [page, setPage] = useState(1)
  const [toDelete, setToDelete] = useState(null)
  const q = useDebouncedValue(search.trim(), 400)

  const params = { status, q, page }
  const query = useQuery({
    queryKey: ['instructor', 'courses', params],
    queryFn: () => getMyCourses({ ...params, per_page: 10 }),
    placeholderData: keepPreviousData,
  })

  const remove = useMutation({
    mutationFn: (course) => deleteCourse(course.id),
    onSuccess: () => {
      toast.success('Course deleted.')
      setToDelete(null)
      queryClient.invalidateQueries({ queryKey: ['instructor', 'courses'] })
    },
    onError: (error) => toast.error(getErrorMessage(error)),
  })

  const courses = query.data?.data ?? []

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold tracking-tight">My courses</h1>
          <p className="text-sm text-slate-500 dark:text-slate-400">Create, edit and publish your courses.</p>
        </div>
        <Link to="/instructor/courses/new" className={buttonClasses()}>
          <Plus className="size-4" aria-hidden="true" />
          New course
        </Link>
      </div>

      <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div role="tablist" aria-label="Filter by status" className="flex gap-1 overflow-x-auto">
          {COURSE_STATUS_TABS.map((tab) => (
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
                status === tab.value
                  ? 'bg-brand-600 text-white'
                  : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800',
              )}
            >
              {tab.label}
            </button>
          ))}
        </div>
        <div className="relative lg:w-72">
          <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" aria-hidden="true" />
          <Input
            type="search"
            value={search}
            onChange={(event) => {
              setSearch(event.target.value)
              setPage(1)
            }}
            placeholder="Search my courses"
            aria-label="Search my courses"
            className="pl-9"
          />
        </div>
      </div>

      {query.isPending ? (
        <div className="space-y-3" aria-busy="true">
          {Array.from({ length: 3 }, (_, i) => (
            <Skeleton key={i} className="h-28 w-full" />
          ))}
        </div>
      ) : query.error ? (
        <ErrorState title="Couldn't load your courses" error={query.error} onRetry={() => query.refetch()} />
      ) : courses.length === 0 ? (
        <EmptyState
          icon={Library}
          title={status || q ? 'No courses match' : 'You haven’t created a course yet'}
          description={status || q ? 'Try another filter or search term.' : 'Start with a title — you can fill in the rest later.'}
          action={
            !status && !q ? (
              <Link to="/instructor/courses/new" className={buttonClasses()}>
                <Plus className="size-4" aria-hidden="true" />
                Create your first course
              </Link>
            ) : null
          }
        />
      ) : (
        <ul className={cn('space-y-3', query.isPlaceholderData && 'opacity-60')}>
          {courses.map((course) => (
            <li
              key={course.id}
              className="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-4 sm:flex-row sm:items-center dark:border-slate-800 dark:bg-slate-900"
            >
              <CourseThumbnail course={course} className="w-full shrink-0 rounded-lg sm:w-40" />
              <div className="min-w-0 flex-1">
                <div className="flex flex-wrap items-center gap-2">
                  <CourseStatusBadge status={course.status} />
                  {course.category && <span className="text-xs text-slate-500">{course.category.name}</span>}
                </div>
                <h2 className="mt-1 truncate font-semibold">{course.title}</h2>
                <div className="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500 dark:text-slate-400">
                  <span>{course.is_free ? 'Free' : formatPrice(course.price, course.currency)}</span>
                  <span>{plural(course.lessons_count ?? 0, 'lesson')}</span>
                  <span className="inline-flex items-center gap-1">
                    <Users className="size-3.5" aria-hidden="true" />
                    {formatNumber(course.students_count)}
                  </span>
                  {course.rating_count > 0 && <StarRating value={course.rating_avg} />}
                  <span>Updated {formatDate(course.updated_at)}</span>
                </div>
              </div>
              <div className="flex shrink-0 gap-2">
                <Link to={`/instructor/courses/${course.id}/edit`} className={buttonClasses({ variant: 'outline', size: 'sm' })}>
                  <Pencil className="size-4" aria-hidden="true" />
                  Edit
                </Link>
                <Link
                  to={`/courses/${course.slug}`}
                  className={buttonClasses({ variant: 'ghost', size: 'sm' })}
                  aria-label={`View ${course.title}`}
                >
                  <Eye className="size-4" aria-hidden="true" />
                  View
                </Link>
                {canDelete(course) && (
                  <Button
                    variant="ghost"
                    size="sm"
                    className="text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/40"
                    onClick={() => setToDelete(course)}
                    aria-label={`Delete ${course.title}`}
                  >
                    <Trash2 className="size-4" aria-hidden="true" />
                  </Button>
                )}
              </div>
            </li>
          ))}
        </ul>
      )}

      <Pagination meta={query.data?.meta} onPageChange={setPage} />

      <ConfirmDialog
        open={Boolean(toDelete)}
        onClose={() => setToDelete(null)}
        onConfirm={() => remove.mutate(toDelete)}
        loading={remove.isPending}
        variant="danger"
        confirmLabel="Delete course"
        title="Delete this course?"
        message={`“${toDelete?.title}” and all its sections and lessons will be permanently deleted.`}
      />
    </div>
  )
}
