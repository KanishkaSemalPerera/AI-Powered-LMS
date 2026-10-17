import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { ArrowLeft, Eye, ListTree, PartyPopper, X } from 'lucide-react'
import { useEffect, useState } from 'react'
import { Link, Navigate, useNavigate, useParams } from 'react-router'
import { toast } from 'sonner'
import * as learnApi from '@/api/learn'
import LessonView from '@/components/course/player/LessonView'
import PlayerCurriculum from '@/components/course/player/PlayerCurriculum'
import ThemeToggle from '@/components/layout/ThemeToggle'
import Alert from '@/components/ui/Alert'
import Button from '@/components/ui/Button'
import { buttonClasses } from '@/components/ui/buttonStyles'
import ErrorState from '@/components/ui/ErrorState'
import Modal from '@/components/ui/Modal'
import PageLoader from '@/components/ui/PageLoader'
import ProgressBar from '@/components/ui/ProgressBar'
import Skeleton from '@/components/ui/Skeleton'
import { useDocumentTitle } from '@/hooks/useDocumentTitle'
import { getErrorMessage } from '@/utils/apiError'
import { coursePath, learnPath } from '@/utils/paths'

/**
 * Distraction-free course player: /learn/:slug (resumes) and
 * /learn/:slug/lessons/:lessonId. Progress is saved on the server.
 */
export default function CoursePlayerPage() {
  const { slug, lessonId } = useParams()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [drawerOpen, setDrawerOpen] = useState(false)
  const [celebrate, setCelebrate] = useState(false)

  const playerKey = ['learn', slug]
  const player = useQuery({ queryKey: playerKey, queryFn: () => learnApi.getPlayer(slug) })
  const lessonQuery = useQuery({
    queryKey: ['learn', slug, 'lesson', lessonId],
    queryFn: () => learnApi.getLesson(slug, lessonId),
    enabled: Boolean(lessonId),
  })

  useDocumentTitle(lessonQuery.data?.lesson.title ?? player.data?.course.title ?? 'Course player')

  // Start each lesson at the top of the page. (The drawer closes from its own link click.)
  useEffect(() => {
    window.scrollTo({ top: 0 })
  }, [lessonId])

  const canTrack = player.data?.mode === 'student'
  const completedIds = new Set(player.data?.progress?.completed_lesson_ids ?? [])
  const isCompleted = lessonId ? completedIds.has(Number(lessonId)) : false

  /** Applies a progress response to the cached curriculum (ticks + percentage). */
  const applyProgress = (progress) => {
    queryClient.setQueryData(playerKey, (current) =>
      current && {
        ...current,
        progress: { ...current.progress, ...progress },
        sections: current.sections.map((section) => ({
          ...section,
          lessons: section.lessons.map((l) => ({ ...l, is_completed: progress.completed_lesson_ids.includes(l.id) })),
        })),
      },
    )
    queryClient.invalidateQueries({ queryKey: ['my-courses'] })
  }

  const toggle = useMutation({
    mutationFn: ({ id, complete }) => (complete ? learnApi.completeLesson(slug, id) : learnApi.uncompleteLesson(slug, id)),
    onSuccess: ({ data }) => {
      applyProgress(data)
      if (data.course_completed_now) setCelebrate(true)
    },
    onError: (error) => toast.error(getErrorMessage(error)),
  })

  const complete = (id) => toggle.mutateAsync({ id, complete: true })

  const goNext = async () => {
    const next = lessonQuery.data?.navigation.next_lesson_id
    if (canTrack && !isCompleted) {
      try {
        await complete(Number(lessonId))
      } catch {
        return // error toast already shown; stay on this lesson
      }
    }
    if (next) navigate(learnPath(player.data.course, next))
  }

  const onVideoEnded = () => {
    if (canTrack && !isCompleted && !toggle.isPending) {
      complete(Number(lessonId)).then(() => toast.success('Lesson complete — nice work!'), () => {})
    }
  }

  // ---------------------------------------------------------------- states

  if (player.isPending) return <PageLoader fullScreen label="Loading course" />
  if (player.error) {
    const status = player.error.response?.status
    return (
      <div className="mx-auto max-w-lg px-4 py-24">
        <ErrorState
          title={status === 403 ? 'You don’t have access to this course' : 'Couldn’t load this course'}
          error={player.error}
          onRetry={status === 403 ? undefined : () => player.refetch()}
        />
        <div className="mt-6 text-center">
          <Link to={`/courses/${slug}`} className={buttonClasses({ variant: 'outline' })}>
            Go to the course page
          </Link>
        </div>
      </div>
    )
  }

  const { course, sections, progress } = player.data
  const firstLessonId = sections.flatMap((s) => s.lessons)[0]?.id

  // /learn/:slug → open the resume lesson.
  if (!lessonId) {
    const target = progress?.resume_lesson_id ?? firstLessonId
    if (!target) {
      return (
        <div className="mx-auto max-w-lg px-4 py-24">
          <ErrorState title="This course has no lessons yet" error={{ message: 'Please check back later.' }} />
        </div>
      )
    }
    return <Navigate to={learnPath(course, target)} replace />
  }

  const curriculum = (
    <PlayerCurriculum course={course} sections={sections} currentLessonId={Number(lessonId)} onNavigate={() => setDrawerOpen(false)} />
  )

  return (
    <div className="flex min-h-screen flex-col">
      {/* ------------------------------------------------------------ Top bar */}
      <header className="sticky top-0 z-30 flex h-14 items-center gap-3 border-b border-slate-200 bg-white/90 px-3 backdrop-blur sm:px-4 dark:border-slate-800 dark:bg-slate-950/90">
        <Link
          to={canTrack ? '/my-courses' : coursePath(course)}
          className="rounded-md p-2 text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"
          aria-label={canTrack ? 'Back to my courses' : 'Back to the course page'}
        >
          <ArrowLeft className="size-5" aria-hidden="true" />
        </Link>
        <div className="min-w-0 flex-1">
          <p className="truncate text-sm font-semibold">{course.title}</p>
          {progress && (
            <div className="mt-0.5 flex max-w-xs items-center gap-2">
              <ProgressBar value={progress.progress_percent} label="Course progress" className="flex-1" />
              <span className="text-xs text-slate-500">
                {progress.completed_lessons}/{progress.total_lessons}
              </span>
            </div>
          )}
        </div>
        <ThemeToggle />
        <Button variant="outline" size="sm" className="lg:hidden" onClick={() => setDrawerOpen(true)} aria-expanded={drawerOpen} aria-controls="player-drawer">
          <ListTree className="size-4" aria-hidden="true" />
          <span className="sr-only sm:not-sr-only">Contents</span>
        </Button>
      </header>

      <div className="flex flex-1">
        {/* ---------------------------------------------------------- Lesson */}
        <main id="main-content" className="min-w-0 flex-1 px-4 py-6 sm:px-8 lg:py-8">
          {!canTrack && (
            <Alert variant="info" className="mx-auto mb-6 max-w-4xl">
              <span className="inline-flex items-center gap-1.5">
                <Eye className="size-4" aria-hidden="true" />
                Preview mode — you can view every lesson, but progress isn&apos;t tracked.
              </span>
            </Alert>
          )}
          {progress?.completed_at && progress.progress_percent === 100 && (
            <Alert variant="success" className="mx-auto mb-6 max-w-4xl" title="Course completed">
              You finished every lesson in this course. Great work!
            </Alert>
          )}

          {lessonQuery.isPending ? (
            <div className="mx-auto max-w-4xl space-y-4" aria-busy="true">
              <Skeleton className="aspect-video w-full rounded-xl" />
              <Skeleton className="h-8 w-2/3" />
              <Skeleton className="h-4 w-full" />
            </div>
          ) : lessonQuery.error ? (
            <ErrorState
              className="mx-auto max-w-xl"
              title="Couldn’t load this lesson"
              error={lessonQuery.error}
              onRetry={lessonQuery.error.response?.status === 404 ? undefined : () => lessonQuery.refetch()}
            />
          ) : (
            <LessonView
              course={course}
              data={lessonQuery.data}
              canTrack={canTrack}
              isCompleted={isCompleted}
              toggling={toggle.isPending}
              onToggleComplete={() => toggle.mutate({ id: Number(lessonId), complete: !isCompleted })}
              onVideoEnded={onVideoEnded}
              onNext={goNext}
            />
          )}
        </main>

        {/* ---------------------------------------------------------- Sidebar (desktop) */}
        <aside className="hidden w-80 shrink-0 border-l border-slate-200 lg:block dark:border-slate-800">
          <div className="sticky top-14 max-h-[calc(100vh-3.5rem)] overflow-y-auto px-2 py-5">{curriculum}</div>
        </aside>
      </div>

      {/* ------------------------------------------------------------ Drawer (mobile) */}
      {drawerOpen && (
        <div className="fixed inset-0 z-40 lg:hidden" role="dialog" aria-modal="true" aria-label="Course content" id="player-drawer">
          <button type="button" className="absolute inset-0 bg-slate-950/50" onClick={() => setDrawerOpen(false)} aria-label="Close course content" />
          <div className="absolute inset-y-0 right-0 flex w-[min(22rem,90vw)] flex-col bg-white shadow-xl dark:bg-slate-900">
            <div className="flex items-center justify-between border-b border-slate-200 px-4 py-3 dark:border-slate-800">
              <p className="font-semibold">Course content</p>
              <Button variant="ghost" size="icon" onClick={() => setDrawerOpen(false)} aria-label="Close course content" autoFocus>
                <X className="size-5" aria-hidden="true" />
              </Button>
            </div>
            <div className="flex-1 overflow-y-auto px-2 py-4">{curriculum}</div>
          </div>
        </div>
      )}

      {/* ------------------------------------------------------------ Completion */}
      <Modal
        open={celebrate}
        onClose={() => setCelebrate(false)}
        title="Course completed!"
        size="sm"
        footer={
          <>
            <Button variant="outline" onClick={() => setCelebrate(false)}>
              Keep reviewing
            </Button>
            <Link to="/my-courses" className={buttonClasses()}>
              My courses
            </Link>
          </>
        }
      >
        <div className="flex flex-col items-center text-center">
          <PartyPopper className="size-12 text-amber-500" aria-hidden="true" />
          <p className="mt-3 text-sm text-slate-600 dark:text-slate-300">
            You finished every lesson of <strong>{course.title}</strong>. You can come back and review any lesson at any time.
          </p>
        </div>
      </Modal>
    </div>
  )
}
