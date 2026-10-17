import { keepPreviousData, useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Check, ClipboardCheck, ExternalLink, X } from 'lucide-react'
import { useState } from 'react'
import { Link } from 'react-router'
import { toast } from 'sonner'
import { approveCourse, getAdminCourses, rejectCourse } from '@/api/admin'
import CourseStatusBadge from '@/components/course/CourseStatusBadge'
import CourseThumbnail from '@/components/course/CourseThumbnail'
import Button from '@/components/ui/Button'
import { buttonClasses } from '@/components/ui/buttonStyles'
import ConfirmDialog from '@/components/ui/ConfirmDialog'
import EmptyState from '@/components/ui/EmptyState'
import ErrorState from '@/components/ui/ErrorState'
import FormField from '@/components/ui/FormField'
import Modal from '@/components/ui/Modal'
import Pagination from '@/components/ui/Pagination'
import Skeleton from '@/components/ui/Skeleton'
import Textarea from '@/components/ui/Textarea'
import { useDocumentTitle } from '@/hooks/useDocumentTitle'
import { getErrorMessage, getFieldErrors } from '@/utils/apiError'
import { cn } from '@/utils/cn'
import { COURSE_STATUS_TABS } from '@/utils/courseOptions'
import { formatDate, formatPrice, plural } from '@/utils/format'

const TABS = [COURSE_STATUS_TABS[2], COURSE_STATUS_TABS[3], COURSE_STATUS_TABS[4], COURSE_STATUS_TABS[1], COURSE_STATUS_TABS[0]]

function RejectModal({ course, onClose, onRejected }) {
  const [reason, setReason] = useState('')
  const [error, setError] = useState(null)

  const reject = useMutation({
    mutationFn: () => rejectCourse(course.id, reason.trim()),
    onSuccess: ({ message }) => {
      toast.success(message)
      onRejected()
    },
    onError: (err) => setError(getFieldErrors(err).reason ?? getErrorMessage(err)),
  })

  return (
    <Modal
      open
      onClose={onClose}
      title="Reject course"
      description={`Explain what “${course.title}” needs to change. The instructor will see this message.`}
      footer={
        <>
          <Button variant="outline" onClick={onClose} disabled={reject.isPending}>
            Cancel
          </Button>
          <Button variant="danger" onClick={() => reject.mutate()} loading={reject.isPending} disabled={reason.trim().length < 10}>
            Reject course
          </Button>
        </>
      }
    >
      <FormField label="Feedback for the instructor" error={error} hint="At least 10 characters." required>
        <Textarea rows={5} value={reason} onChange={(event) => setReason(event.target.value)} autoFocus />
      </FormField>
    </Modal>
  )
}

export default function AdminCourseApprovalsPage() {
  useDocumentTitle('Course approvals')
  const queryClient = useQueryClient()
  const [status, setStatus] = useState('pending')
  const [page, setPage] = useState(1)
  const [toApprove, setToApprove] = useState(null)
  const [toReject, setToReject] = useState(null)

  const query = useQuery({
    queryKey: ['admin', 'courses', { status, page }],
    queryFn: () => getAdminCourses({ status, page, per_page: 10 }),
    placeholderData: keepPreviousData,
  })

  const refresh = () => {
    queryClient.invalidateQueries({ queryKey: ['admin', 'courses'] })
    queryClient.invalidateQueries({ queryKey: ['courses'] })
  }

  const approve = useMutation({
    mutationFn: (course) => approveCourse(course.id),
    onSuccess: ({ message }) => {
      toast.success(message)
      setToApprove(null)
      refresh()
    },
    onError: (error) => toast.error(getErrorMessage(error)),
  })

  const courses = query.data?.data ?? []

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold tracking-tight">Course approvals</h1>
        <p className="text-sm text-slate-500 dark:text-slate-400">Review submitted courses before they go live. Oldest submissions first.</p>
      </div>

      <div role="tablist" aria-label="Filter by status" className="flex gap-1 overflow-x-auto">
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
        <div className="space-y-3" aria-busy="true">
          {Array.from({ length: 3 }, (_, i) => (
            <Skeleton key={i} className="h-28 w-full" />
          ))}
        </div>
      ) : query.error ? (
        <ErrorState title="Couldn't load courses" error={query.error} onRetry={() => query.refetch()} />
      ) : courses.length === 0 ? (
        <EmptyState
          icon={ClipboardCheck}
          title={status === 'pending' ? 'All caught up!' : 'No courses here'}
          description={status === 'pending' ? 'There are no courses waiting for review.' : 'No courses have this status.'}
        />
      ) : (
        <ul className={cn('space-y-3', query.isPlaceholderData && 'opacity-60')}>
          {courses.map((course) => (
            <li
              key={course.id}
              className="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-4 md:flex-row md:items-center dark:border-slate-800 dark:bg-slate-900"
            >
              <CourseThumbnail course={course} className="w-full shrink-0 rounded-lg md:w-40" />
              <div className="min-w-0 flex-1">
                <div className="flex flex-wrap items-center gap-2">
                  <CourseStatusBadge status={course.status} />
                  {course.category && <span className="text-xs text-slate-500">{course.category.name}</span>}
                </div>
                <h2 className="mt-1 font-semibold">{course.title}</h2>
                <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">
                  by {course.instructor?.name} · {course.is_free ? 'Free' : formatPrice(course.price, course.currency)} ·{' '}
                  {plural(course.lessons_count ?? 0, 'lesson')} · updated {formatDate(course.updated_at)}
                </p>
              </div>
              <div className="flex shrink-0 flex-wrap gap-2">
                <Link to={`/courses/${course.slug}`} className={buttonClasses({ variant: 'outline', size: 'sm' })}>
                  <ExternalLink className="size-4" aria-hidden="true" />
                  Preview
                </Link>
                {course.status === 'pending' && (
                  <>
                    <Button size="sm" onClick={() => setToApprove(course)}>
                      <Check className="size-4" aria-hidden="true" />
                      Approve
                    </Button>
                    <Button size="sm" variant="danger" onClick={() => setToReject(course)}>
                      <X className="size-4" aria-hidden="true" />
                      Reject
                    </Button>
                  </>
                )}
              </div>
            </li>
          ))}
        </ul>
      )}

      <Pagination meta={query.data?.meta} onPageChange={setPage} />

      <ConfirmDialog
        open={Boolean(toApprove)}
        onClose={() => setToApprove(null)}
        onConfirm={() => approve.mutate(toApprove)}
        loading={approve.isPending}
        confirmLabel="Approve & publish"
        title="Publish this course?"
        message={`“${toApprove?.title}” will be published and visible to all students.`}
      />

      {toReject && (
        <RejectModal
          course={toReject}
          onClose={() => setToReject(null)}
          onRejected={() => {
            setToReject(null)
            refresh()
          }}
        />
      )}
    </div>
  )
}
