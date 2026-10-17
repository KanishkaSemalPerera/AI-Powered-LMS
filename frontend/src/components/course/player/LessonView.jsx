import { ChevronLeft, ChevronRight, Circle, CircleCheck, ListChecks } from 'lucide-react'
import { Link } from 'react-router'
import Markdown from '@/components/course/Markdown'
import VideoPlayer from '@/components/course/VideoPlayer'
import Badge from '@/components/ui/Badge'
import Button from '@/components/ui/Button'
import { buttonClasses } from '@/components/ui/buttonStyles'
import { cn } from '@/utils/cn'
import { formatDuration } from '@/utils/format'
import { learnPath } from '@/utils/paths'

/**
 * One lesson: video and/or Markdown, completion toggle and previous/next.
 * canTrack is false in owner/admin preview mode.
 */
export default function LessonView({ course, data, canTrack, isCompleted, toggling, onToggleComplete, onVideoEnded, onNext }) {
  const { lesson, navigation } = data
  const isVideo = lesson.type === 'video'

  return (
    <article className="mx-auto max-w-4xl" aria-labelledby="lesson-title">
      {isVideo && (
        <div className="mb-6">
          <VideoPlayer key={lesson.id} url={lesson.playback_url} title={lesson.title} onEnded={onVideoEnded} />
        </div>
      )}

      <div className="flex flex-wrap items-start justify-between gap-4">
        <div className="min-w-0">
          <p className="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">
            Lesson {navigation.index} of {navigation.total} · {formatDuration(lesson.duration_seconds)}
          </p>
          <h1 id="lesson-title" className="mt-1 text-2xl font-bold tracking-tight sm:text-3xl">
            {lesson.title}
          </h1>
        </div>
        {canTrack && (
          <Button
            variant={isCompleted ? 'secondary' : 'outline'}
            onClick={onToggleComplete}
            loading={toggling}
            aria-pressed={isCompleted}
            className={cn(isCompleted && 'text-emerald-700 dark:text-emerald-300')}
          >
            {isCompleted ? (
              <CircleCheck className="size-4 text-emerald-500" aria-hidden="true" />
            ) : (
              <Circle className="size-4" aria-hidden="true" />
            )}
            {isCompleted ? 'Completed' : 'Mark as complete'}
          </Button>
        )}
      </div>

      {lesson.content?.trim() ? (
        <section aria-label={isVideo ? 'Lesson notes' : 'Lesson content'} className="mt-6">
          <Markdown>{lesson.content}</Markdown>
        </section>
      ) : (
        !isVideo && <p className="mt-6 text-slate-500">This lesson has no content yet.</p>
      )}

      {data.quiz && (
        <section
          aria-labelledby="quiz-card-title"
          className="mt-8 flex flex-col gap-4 rounded-2xl border border-brand-200 bg-brand-50 p-5 sm:flex-row sm:items-center dark:border-brand-900 dark:bg-brand-950/40"
        >
          <span className="grid size-11 shrink-0 place-items-center rounded-xl bg-brand-600 text-white">
            <ListChecks className="size-5" aria-hidden="true" />
          </span>
          <div className="min-w-0 flex-1">
            <h2 id="quiz-card-title" className="font-semibold">
              {data.quiz.title}
            </h2>
            <p className="mt-0.5 text-sm text-slate-600 dark:text-slate-400">
              {data.quiz.questions_count} questions · pass mark {data.quiz.pass_mark}%
              {data.quiz.best_score !== null && ` · best score ${data.quiz.best_score}%`}
            </p>
          </div>
          {data.quiz.passed && <Badge variant="success">Passed</Badge>}
          <Link to={`${learnPath(course, lesson.id)}/quiz`} className={buttonClasses({ variant: data.quiz.passed ? 'outline' : 'primary' })}>
            {data.quiz.attempts > 0 ? 'Retake quiz' : 'Take the quiz'}
          </Link>
        </section>
      )}

      <nav aria-label="Lesson navigation" className="mt-10 flex items-center justify-between gap-3 border-t border-slate-200 pt-6 dark:border-slate-800">
        {navigation.previous_lesson_id ? (
          <Link to={learnPath(course, navigation.previous_lesson_id)} className={buttonClasses({ variant: 'outline' })}>
            <ChevronLeft className="size-4" aria-hidden="true" />
            Previous
          </Link>
        ) : (
          <span />
        )}
        {navigation.next_lesson_id ? (
          <Button onClick={onNext}>
            {canTrack && !isCompleted ? 'Complete & continue' : 'Next lesson'}
            <ChevronRight className="size-4" aria-hidden="true" />
          </Button>
        ) : (
          canTrack &&
          !isCompleted && (
            <Button onClick={onToggleComplete} loading={toggling}>
              <CircleCheck className="size-4" aria-hidden="true" />
              Finish course
            </Button>
          )
        )}
      </nav>
    </article>
  )
}
