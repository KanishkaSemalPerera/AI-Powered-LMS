import { Clock, Users } from 'lucide-react'
import { Link } from 'react-router'
import Badge from '@/components/ui/Badge'
import StarRating from '@/components/ui/StarRating'
import { formatDuration, formatNumber, formatPrice } from '@/utils/format'
import CourseThumbnail from './CourseThumbnail'

/** Catalogue card. The whole card is one link (the title), keeping tab order short. */
export default function CourseCard({ course }) {
  return (
    <article className="group relative flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
      <CourseThumbnail course={course} className="transition group-hover:brightness-105" />

      <div className="flex flex-1 flex-col p-4">
        <div className="mb-2 flex flex-wrap items-center gap-2">
          {course.category && <Badge variant="brand">{course.category.name}</Badge>}
          <Badge>{course.level_label}</Badge>
        </div>

        <h3 className="line-clamp-2 font-semibold leading-snug">
          <Link to={`/courses/${course.slug}`} className="after:absolute after:inset-0 focus-visible:outline-none">
            {course.title}
          </Link>
        </h3>
        {course.instructor && (
          <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">{course.instructor.name}</p>
        )}

        <StarRating value={course.rating_avg} count={course.rating_count} className="mt-2" />

        <div className="mt-3 flex items-center gap-4 text-xs text-slate-500 dark:text-slate-400">
          {course.duration_seconds > 0 && (
            <span className="inline-flex items-center gap-1">
              <Clock className="size-3.5" aria-hidden="true" />
              {formatDuration(course.duration_seconds)}
            </span>
          )}
          <span className="inline-flex items-center gap-1">
            <Users className="size-3.5" aria-hidden="true" />
            {formatNumber(course.students_count, { compact: true })} students
          </span>
        </div>

        <p className="mt-auto pt-4 text-lg font-bold">
          {course.is_free ? (
            <span className="text-emerald-600 dark:text-emerald-400">Free</span>
          ) : (
            formatPrice(course.price, course.currency)
          )}
        </p>
      </div>
    </article>
  )
}
