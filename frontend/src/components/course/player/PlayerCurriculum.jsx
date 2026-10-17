import { CircleCheck, CirclePlay, FileText, ListChecks } from 'lucide-react'
import { Link } from 'react-router'
import { cn } from '@/utils/cn'
import { formatDuration } from '@/utils/format'
import { learnPath } from '@/utils/paths'

/** Curriculum list inside the player: completion ticks, current lesson highlighted. */
export default function PlayerCurriculum({ course, sections, currentLessonId, onNavigate }) {
  return (
    <nav aria-label="Course content" className="space-y-4">
      {sections.map((section, index) => {
        const done = section.lessons.filter((lesson) => lesson.is_completed).length
        return (
          <div key={section.id}>
            <div className="mb-1 px-3">
              <h3 className="text-sm font-semibold">
                Section {index + 1}: {section.title}
              </h3>
              <p className="text-xs text-slate-500 dark:text-slate-400">
                {done}/{section.lessons.length} completed
              </p>
            </div>
            <ol className="space-y-0.5">
              {section.lessons.map((lesson) => {
                const current = lesson.id === currentLessonId
                const TypeIcon = lesson.type === 'video' ? CirclePlay : FileText
                return (
                  <li key={lesson.id}>
                    <Link
                      to={learnPath(course, lesson.id)}
                      onClick={onNavigate}
                      aria-current={current ? 'page' : undefined}
                      className={cn(
                        'flex items-start gap-2.5 rounded-lg px-3 py-2 text-sm transition-colors',
                        current
                          ? 'bg-brand-50 font-medium text-brand-800 dark:bg-brand-950 dark:text-brand-200'
                          : 'text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800',
                      )}
                    >
                      {lesson.is_completed ? (
                        <CircleCheck className="mt-0.5 size-4 shrink-0 text-emerald-500" aria-label="Completed" />
                      ) : (
                        <TypeIcon className="mt-0.5 size-4 shrink-0 text-slate-400" aria-hidden="true" />
                      )}
                      <span className="flex-1">{lesson.title}</span>
                      {lesson.has_quiz && <ListChecks className="mt-0.5 size-3.5 shrink-0 text-brand-500" aria-label="Has a quiz" />}
                      <span className="shrink-0 text-xs text-slate-400">{formatDuration(lesson.duration_seconds)}</span>
                    </Link>
                  </li>
                )
              })}
            </ol>
          </div>
        )
      })}
    </nav>
  )
}
