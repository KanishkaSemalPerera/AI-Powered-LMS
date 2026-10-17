import { useQuery } from '@tanstack/react-query'
import { useState } from 'react'
import { Check, Clock, Globe, Layers, Signal, Users } from 'lucide-react'
import { Link, useParams } from 'react-router'
import { getCourse } from '@/api/courses'
import CourseStatusBadge from '@/components/course/CourseStatusBadge'
import CourseThumbnail from '@/components/course/CourseThumbnail'
import CurriculumList from '@/components/course/CurriculumList'
import EnrollPanel from '@/components/course/EnrollPanel'
import PreviewLessonModal from '@/components/course/PreviewLessonModal'
import Alert from '@/components/ui/Alert'
import Avatar from '@/components/ui/Avatar'
import Badge from '@/components/ui/Badge'
import ErrorState from '@/components/ui/ErrorState'
import Skeleton from '@/components/ui/Skeleton'
import StarRating from '@/components/ui/StarRating'
import { useAuth } from '@/hooks/useAuth'
import { useDocumentTitle } from '@/hooks/useDocumentTitle'
import NotFoundPage from '@/pages/public/NotFoundPage'
import { formatDate, formatDuration, formatNumber, formatPrice, plural } from '@/utils/format'

function DetailSkeleton() {
  return (
    <div aria-busy="true" aria-label="Loading course">
      <div className="bg-slate-900 py-12">
        <div className="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
          <Skeleton className="h-6 w-40 bg-slate-700 dark:bg-slate-700" />
          <Skeleton className="h-10 w-2/3 bg-slate-700 dark:bg-slate-700" />
          <Skeleton className="h-5 w-1/2 bg-slate-700 dark:bg-slate-700" />
        </div>
      </div>
      <div className="mx-auto max-w-7xl space-y-4 px-4 py-10 sm:px-6 lg:px-8">
        <Skeleton className="h-40 w-full lg:w-2/3" />
        <Skeleton className="h-64 w-full lg:w-2/3" />
      </div>
    </div>
  )
}

export default function CourseDetailPage() {
  const { slug } = useParams()
  // The response depends on who is viewing (enrollment, owner preview), so the
  // viewer is part of the cache key: logging in or out never shows stale data.
  const { user } = useAuth()
  const [previewLesson, setPreviewLesson] = useState(null)
  const { data: course, error, isPending, refetch } = useQuery({
    queryKey: ['course', slug, user?.id ?? 'guest'],
    queryFn: () => getCourse(slug),
  })

  useDocumentTitle(course?.title ?? 'Course')

  if (isPending) return <DetailSkeleton />
  if (error?.response?.status === 404) return <NotFoundPage />
  if (error) {
    return (
      <div className="mx-auto max-w-xl px-4 py-24">
        <ErrorState title="Couldn't load this course" error={error} onRetry={() => refetch()} />
      </div>
    )
  }

  const facts = [
    { icon: Layers, label: plural(course.lessons_count ?? 0, 'lesson') },
    { icon: Clock, label: formatDuration(course.duration_seconds) },
    { icon: Signal, label: course.level_label },
    { icon: Globe, label: course.language },
  ]

  return (
    <>
      {/* ------------------------------------------------------------ Hero */}
      <section className="bg-slate-900 text-white">
        <div className="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 lg:grid-cols-[1fr_22rem] lg:px-8 lg:py-14">
          <div>
            <nav aria-label="Breadcrumb" className="mb-4 text-sm text-slate-300">
              <Link to="/courses" className="hover:text-white hover:underline">
                Courses
              </Link>
              {course.category && (
                <>
                  <span className="mx-2" aria-hidden="true">
                    /
                  </span>
                  <Link to={`/courses?category=${course.category.slug}`} className="hover:text-white hover:underline">
                    {course.category.name}
                  </Link>
                </>
              )}
            </nav>

            {course.status !== 'published' && (
              <div className="mb-4">
                <CourseStatusBadge status={course.status} />
              </div>
            )}

            <h1 className="text-3xl font-bold tracking-tight text-balance sm:text-4xl">{course.title}</h1>
            {course.subtitle && <p className="mt-3 text-lg text-slate-300">{course.subtitle}</p>}

            <div className="mt-5 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm">
              <StarRating value={course.rating_avg} count={course.rating_count} />
              <span className="inline-flex items-center gap-1.5 text-slate-300">
                <Users className="size-4" aria-hidden="true" />
                {formatNumber(course.students_count)} students
              </span>
              {course.published_at && <span className="text-slate-400">Published {formatDate(course.published_at)}</span>}
            </div>

            {course.instructor && (
              <p className="mt-4 text-sm text-slate-300">
                Created by <span className="font-semibold text-white">{course.instructor.name}</span>
              </p>
            )}
          </div>
        </div>
      </section>

      <div className="mx-auto grid max-w-7xl gap-10 px-4 py-10 sm:px-6 lg:grid-cols-[1fr_22rem] lg:px-8">
        {/* ------------------------------------------------------------ Sidebar card */}
        <aside className="lg:col-start-2 lg:row-start-1 lg:-mt-64">
          <div className="sticky top-24 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-lg dark:border-slate-800 dark:bg-slate-900">
            <CourseThumbnail course={course} />
            <div className="space-y-5 p-6">
              <p className="text-3xl font-bold">
                {course.is_free ? (
                  <span className="text-emerald-600 dark:text-emerald-400">Free</span>
                ) : (
                  formatPrice(course.price, course.currency)
                )}
              </p>
              <EnrollPanel course={course} />
              <ul className="space-y-2.5 text-sm text-slate-600 dark:text-slate-300">
                {facts.map(({ icon: Icon, label }) => (
                  <li key={label} className="flex items-center gap-2">
                    <Icon className="size-4 text-slate-400" aria-hidden="true" />
                    {label}
                  </li>
                ))}
              </ul>
            </div>
          </div>
        </aside>

        {/* ------------------------------------------------------------ Main column */}
        <div className="min-w-0 space-y-10 lg:col-start-1 lg:row-start-1">
          {course.can_manage && course.status === 'rejected' && course.rejection_reason && (
            <Alert variant="error" title="This course was rejected">
              {course.rejection_reason}
            </Alert>
          )}

          {course.what_you_will_learn.length > 0 && (
            <section aria-labelledby="learn-heading" className="rounded-2xl border border-slate-200 p-6 dark:border-slate-800">
              <h2 id="learn-heading" className="text-xl font-bold">
                What you&apos;ll learn
              </h2>
              <ul className="mt-4 grid gap-3 sm:grid-cols-2">
                {course.what_you_will_learn.map((item) => (
                  <li key={item} className="flex gap-2 text-sm">
                    <Check className="mt-0.5 size-4 shrink-0 text-emerald-500" aria-hidden="true" />
                    {item}
                  </li>
                ))}
              </ul>
            </section>
          )}

          <section aria-labelledby="curriculum-heading">
            <h2 id="curriculum-heading" className="text-xl font-bold">
              Course content
            </h2>
            <p className="mb-4 mt-1 text-sm text-slate-500 dark:text-slate-400">
              {plural(course.sections?.length ?? 0, 'section')} · {plural(course.lessons_count ?? 0, 'lesson')} ·{' '}
              {formatDuration(course.duration_seconds)} total
            </p>
            <CurriculumList sections={course.sections} onPreview={setPreviewLesson} />
          </section>

          {course.requirements.length > 0 && (
            <section aria-labelledby="requirements-heading">
              <h2 id="requirements-heading" className="text-xl font-bold">
                Requirements
              </h2>
              <ul className="mt-3 list-disc space-y-1.5 pl-5 text-sm text-slate-700 dark:text-slate-300">
                {course.requirements.map((item) => (
                  <li key={item}>{item}</li>
                ))}
              </ul>
            </section>
          )}

          {course.description && (
            <section aria-labelledby="description-heading">
              <h2 id="description-heading" className="text-xl font-bold">
                Description
              </h2>
              <div className="mt-3 space-y-4 text-slate-700 dark:text-slate-300">
                {course.description.split(/\n{2,}/).map((paragraph, index) => (
                  <p key={index} className="whitespace-pre-line">
                    {paragraph}
                  </p>
                ))}
              </div>
            </section>
          )}

          {course.instructor && (
            <section aria-labelledby="instructor-heading" className="rounded-2xl border border-slate-200 p-6 dark:border-slate-800">
              <h2 id="instructor-heading" className="text-xl font-bold">
                Your instructor
              </h2>
              <div className="mt-4 flex items-center gap-4">
                <Avatar src={course.instructor.avatar_url} name={course.instructor.name} size="lg" />
                <div>
                  <p className="font-semibold">{course.instructor.name}</p>
                  {course.instructor.headline && (
                    <p className="text-sm text-slate-500 dark:text-slate-400">{course.instructor.headline}</p>
                  )}
                </div>
              </div>
              {course.instructor.bio && <p className="mt-4 text-sm text-slate-700 dark:text-slate-300">{course.instructor.bio}</p>}
            </section>
          )}

          {course.category && (
            <p className="text-sm text-slate-500 dark:text-slate-400">
              Category:{' '}
              <Link to={`/courses?category=${course.category.slug}`}>
                <Badge variant="brand">{course.category.name}</Badge>
              </Link>
            </p>
          )}
        </div>
      </div>

      {previewLesson && (
        <PreviewLessonModal courseSlug={course.slug} lessonId={previewLesson.id} onClose={() => setPreviewLesson(null)} />
      )}
    </>
  )
}
