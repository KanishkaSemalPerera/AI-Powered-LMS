import { keepPreviousData, useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { ArrowLeft, Award, History, RotateCcw, TriangleAlert } from 'lucide-react'
import { useState } from 'react'
import { Link, useParams } from 'react-router'
import { toast } from 'sonner'
import { getQuiz, getQuizAttempt, getQuizAttempts, submitQuizAttempt } from '@/api/quizzes'
import QuizQuestion from '@/components/quiz/QuizQuestion'
import ThemeToggle from '@/components/layout/ThemeToggle'
import Alert from '@/components/ui/Alert'
import Badge from '@/components/ui/Badge'
import Button from '@/components/ui/Button'
import { buttonClasses } from '@/components/ui/buttonStyles'
import ConfirmDialog from '@/components/ui/ConfirmDialog'
import ErrorState from '@/components/ui/ErrorState'
import PageLoader from '@/components/ui/PageLoader'
import Pagination from '@/components/ui/Pagination'
import ProgressBar from '@/components/ui/ProgressBar'
import Skeleton from '@/components/ui/Skeleton'
import { useDocumentTitle } from '@/hooks/useDocumentTitle'
import { getErrorMessage } from '@/utils/apiError'
import { cn } from '@/utils/cn'
import { formatDate } from '@/utils/format'

function ResultHeader({ attempt }) {
  return (
    <div
      className={cn(
        'flex flex-col items-center rounded-2xl p-6 text-center',
        attempt.passed ? 'bg-emerald-50 dark:bg-emerald-950/40' : 'bg-amber-50 dark:bg-amber-950/40',
      )}
      role="status"
    >
      {attempt.passed ? (
        <Award className="size-12 text-emerald-600" aria-hidden="true" />
      ) : (
        <TriangleAlert className="size-12 text-amber-600" aria-hidden="true" />
      )}
      <p className="mt-3 text-4xl font-extrabold">{attempt.score_percent}%</p>
      <p className="mt-1 font-semibold">{attempt.passed ? 'You passed!' : 'Not quite there yet'}</p>
      <p className="mt-1 text-sm text-slate-600 dark:text-slate-400">
        {attempt.correct_count} of {attempt.total_questions} correct · pass mark {attempt.pass_mark}%
      </p>
    </div>
  )
}

function AttemptHistory({ slug, lessonId, onReview, refreshKey }) {
  const [page, setPage] = useState(1)
  const query = useQuery({
    queryKey: ['quiz-attempts', slug, lessonId, page, refreshKey],
    queryFn: () => getQuizAttempts(slug, lessonId, page),
    placeholderData: keepPreviousData,
  })

  if (query.isPending) return <Skeleton className="h-32 w-full" />
  if (query.error || query.data.data.length === 0) return null

  return (
    <section aria-labelledby="history-heading" className="rounded-2xl border border-slate-200 p-5 dark:border-slate-800">
      <h2 id="history-heading" className="mb-3 flex items-center gap-2 font-semibold">
        <History className="size-4" aria-hidden="true" />
        Your attempts
      </h2>
      <ul className="divide-y divide-slate-100 dark:divide-slate-800">
        {query.data.data.map((attempt) => (
          <li key={attempt.id} className="flex flex-wrap items-center justify-between gap-2 py-2.5 text-sm">
            <span className="text-slate-600 dark:text-slate-400">{formatDate(attempt.created_at)}</span>
            <span className="font-semibold">{attempt.score_percent}%</span>
            <Badge variant={attempt.passed ? 'success' : 'warning'}>{attempt.passed ? 'Passed' : 'Not passed'}</Badge>
            <Button variant="ghost" size="sm" onClick={() => onReview(attempt.id)}>
              Review
            </Button>
          </li>
        ))}
      </ul>
      <Pagination meta={query.data.meta} onPageChange={setPage} className="mt-3" />
    </section>
  )
}

/** /learn/:slug/lessons/:lessonId/quiz */
export default function QuizPage() {
  const { slug, lessonId } = useParams()
  const queryClient = useQueryClient()
  const [answers, setAnswers] = useState({})
  const [result, setResult] = useState(null) // attempt with review
  const [confirming, setConfirming] = useState(false)
  const [historyKey, setHistoryKey] = useState(0)

  const quizQuery = useQuery({ queryKey: ['quiz', slug, lessonId], queryFn: () => getQuiz(slug, lessonId) })
  useDocumentTitle(quizQuery.data?.quiz.title ?? 'Quiz')

  const submit = useMutation({
    mutationFn: () => submitQuizAttempt(slug, lessonId, answers),
    onSuccess: ({ data, message }) => {
      setConfirming(false)
      setResult(data)
      setHistoryKey((k) => k + 1)
      if (data.passed) toast.success(message)
      else toast.info(message)
      // Passing completes the lesson, so refresh the player and quiz summary.
      queryClient.invalidateQueries({ queryKey: ['learn', slug] })
      queryClient.invalidateQueries({ queryKey: ['quiz', slug, lessonId] })
      queryClient.invalidateQueries({ queryKey: ['my-courses'] })
      window.scrollTo({ top: 0, behavior: 'smooth' })
    },
    onError: (error) => {
      setConfirming(false)
      toast.error(getErrorMessage(error))
    },
  })

  const review = async (attemptId) => {
    try {
      setResult(await getQuizAttempt(attemptId))
      window.scrollTo({ top: 0, behavior: 'smooth' })
    } catch (error) {
      toast.error(getErrorMessage(error))
    }
  }

  const retake = () => {
    setAnswers({})
    setResult(null)
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }

  const lessonUrl = `/learn/${slug}/lessons/${lessonId}`
  const header = (
    <header className="sticky top-0 z-30 flex h-14 items-center gap-3 border-b border-slate-200 bg-white/90 px-3 backdrop-blur sm:px-4 dark:border-slate-800 dark:bg-slate-950/90">
      <Link to={lessonUrl} className="rounded-md p-2 text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800" aria-label="Back to the lesson">
        <ArrowLeft className="size-5" aria-hidden="true" />
      </Link>
      <p className="min-w-0 flex-1 truncate text-sm font-semibold">{quizQuery.data?.quiz.title ?? 'Quiz'}</p>
      <ThemeToggle />
    </header>
  )

  if (quizQuery.isPending) return <PageLoader fullScreen label="Loading quiz" />
  if (quizQuery.error) {
    return (
      <div className="min-h-screen">
        {header}
        <div className="mx-auto max-w-lg px-4 py-20">
          <ErrorState title="Couldn’t open this quiz" error={quizQuery.error} />
          <div className="mt-6 text-center">
            <Link to={lessonUrl} className={buttonClasses({ variant: 'outline' })}>
              Back to the lesson
            </Link>
          </div>
        </div>
      </div>
    )
  }

  const { quiz, lesson, can_submit: canSubmit, summary } = quizQuery.data
  const answered = quiz.questions.filter((q) => answers[q.id] !== undefined).length
  const unanswered = quiz.questions.length - answered

  return (
    <div className="min-h-screen">
      {header}
      <main id="main-content" className="mx-auto max-w-3xl space-y-6 px-4 py-8">
        <div>
          <p className="text-sm text-slate-500 dark:text-slate-400">Quiz for “{lesson.title}”</p>
          <h1 className="mt-1 text-2xl font-bold tracking-tight sm:text-3xl">{quiz.title}</h1>
          {quiz.description && <p className="mt-2 text-slate-600 dark:text-slate-400">{quiz.description}</p>}
          <div className="mt-3 flex flex-wrap gap-2 text-sm">
            <Badge>{quiz.questions.length} questions</Badge>
            <Badge>Pass mark {quiz.pass_mark}%</Badge>
            {summary.best_score !== null && <Badge variant={summary.passed ? 'success' : 'warning'}>Best score {summary.best_score}%</Badge>}
          </div>
        </div>

        {!canSubmit && (
          <Alert variant="info">Preview mode — you can read the questions, but only enrolled students can submit answers.</Alert>
        )}

        {result ? (
          <div className="space-y-6">
            <ResultHeader attempt={result} />
            <div className="flex flex-wrap justify-center gap-3">
              {canSubmit && (
                <Button onClick={retake} variant={result.passed ? 'outline' : 'primary'}>
                  <RotateCcw className="size-4" aria-hidden="true" />
                  Retake quiz
                </Button>
              )}
              <Link to={lessonUrl} className={buttonClasses({ variant: result.passed ? 'primary' : 'outline' })}>
                Back to the lesson
              </Link>
            </div>
            <h2 className="text-lg font-semibold">Review your answers</h2>
            {result.review.map((item, index) => (
              <QuizQuestion key={item.question_id} number={index + 1} question={item.question} options={item.options} review={item} />
            ))}
          </div>
        ) : (
          <form
            onSubmit={(event) => {
              event.preventDefault()
              if (unanswered > 0) setConfirming(true)
              else submit.mutate()
            }}
            className="space-y-4"
          >
            <div className="sticky top-14 z-20 -mx-4 bg-white/90 px-4 py-2 backdrop-blur dark:bg-slate-950/90">
              <ProgressBar value={(answered / quiz.questions.length) * 100} label="Questions answered" />
              <p className="mt-1 text-xs text-slate-500">
                {answered} of {quiz.questions.length} answered
              </p>
            </div>
            {quiz.questions.map((question, index) => (
              <QuizQuestion
                key={question.id}
                number={index + 1}
                question={question.question}
                options={question.options}
                value={answers[question.id]}
                onChange={(choice) => setAnswers((current) => ({ ...current, [question.id]: choice }))}
              />
            ))}
            {canSubmit && (
              <div className="flex justify-end">
                <Button type="submit" size="lg" loading={submit.isPending}>
                  Submit answers
                </Button>
              </div>
            )}
          </form>
        )}

        {canSubmit && <AttemptHistory slug={slug} lessonId={lessonId} onReview={review} refreshKey={historyKey} />}
      </main>

      <ConfirmDialog
        open={confirming}
        onClose={() => setConfirming(false)}
        onConfirm={() => submit.mutate()}
        loading={submit.isPending}
        confirmLabel="Submit anyway"
        title="Submit with unanswered questions?"
        message={`You haven’t answered ${unanswered} question${unanswered === 1 ? '' : 's'}. Unanswered questions count as incorrect.`}
      />
    </div>
  )
}
