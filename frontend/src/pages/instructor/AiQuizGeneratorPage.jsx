import { useMutation, useQuery } from '@tanstack/react-query'
import { Sparkles, Wand2 } from 'lucide-react'
import { useState } from 'react'
import { useNavigate, useSearchParams } from 'react-router'
import { toast } from 'sonner'
import { generateQuiz } from '@/api/ai'
import { getCurriculum } from '@/api/curriculum'
import { getMyCourses } from '@/api/instructor'
import Alert from '@/components/ui/Alert'
import AiUsageNote from '@/components/ui/AiUsageNote'
import Button from '@/components/ui/Button'
import Card, { CardBody, CardHeader } from '@/components/ui/Card'
import FormField from '@/components/ui/FormField'
import Select from '@/components/ui/Select'
import Textarea from '@/components/ui/Textarea'
import { useAiUsage } from '@/hooks/useAiUsage'
import { useDocumentTitle } from '@/hooks/useDocumentTitle'
import { getErrorMessage, getFieldErrors } from '@/utils/apiError'
import { cn } from '@/utils/cn'

const MIN_CHARS = 100

/**
 * AI quiz generator: pick a lesson (or paste material), choose the number of
 * questions and difficulty; the backend asks Claude and saves a DRAFT quiz,
 * then we open it in the quiz editor for review.
 */
export default function AiQuizGeneratorPage() {
  useDocumentTitle('AI quiz generator')
  const navigate = useNavigate()
  const [params, setParams] = useSearchParams()
  const courseId = params.get('course') ?? ''
  const lessonId = params.get('lesson') ?? ''

  const [source, setSource] = useState('lesson')
  const [text, setText] = useState('')
  const [count, setCount] = useState('5')
  const [difficulty, setDifficulty] = useState('medium')
  const [replace, setReplace] = useState(false)
  const [errors, setErrors] = useState({})
  const { usage, configured, refresh } = useAiUsage()

  const coursesQuery = useQuery({ queryKey: ['instructor', 'courses', 'all'], queryFn: () => getMyCourses({ per_page: 50 }) })
  const curriculumQuery = useQuery({
    queryKey: ['instructor', 'curriculum', courseId],
    queryFn: () => getCurriculum(courseId),
    enabled: Boolean(courseId),
  })

  const courses = coursesQuery.data?.data ?? []
  const course = courses.find((c) => String(c.id) === courseId)
  const lessons = (curriculumQuery.data ?? []).flatMap((section) =>
    section.lessons.map((lesson) => ({ ...lesson, sectionTitle: section.title })),
  )
  const lesson = lessons.find((l) => String(l.id) === lessonId)
  const lessonChars = lesson?.content?.trim().length ?? 0
  const locked = course?.status === 'pending'

  const setParam = (key, value) =>
    setParams((current) => {
      const next = new URLSearchParams(current)
      if (value) next.set(key, value)
      else next.delete(key)
      if (key === 'course') next.delete('lesson')
      return next
    })

  const generate = useMutation({
    mutationFn: () =>
      generateQuiz(lessonId, {
        num_questions: Number(count),
        difficulty,
        source_text: source === 'paste' ? text : null,
        replace,
      }),
    onSuccess: ({ message }) => {
      refresh()
      toast.success(message)
      navigate(`/instructor/courses/${courseId}/lessons/${lessonId}/quiz`)
    },
    onError: (error) => {
      refresh()
      setErrors(getFieldErrors(error))
      toast.error(getErrorMessage(error))
    },
  })

  const sourceOk = source === 'lesson' ? lessonChars >= MIN_CHARS : text.trim().length >= MIN_CHARS
  const needsReplace = Boolean(lesson?.quiz) && !replace
  const canGenerate = configured && lesson && sourceOk && !needsReplace && !locked && (usage?.remaining_today ?? 1) > 0

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 className="flex items-center gap-2 text-2xl font-bold tracking-tight">
            <Sparkles className="size-6 text-brand-500" aria-hidden="true" />
            AI quiz generator
          </h1>
          <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Turn lesson content into multiple-choice questions. You’ll review the draft before students see it.
          </p>
        </div>
        <AiUsageNote usage={usage} configured />
      </div>

      {!configured && <AiUsageNote configured={false} />}

      <form
        onSubmit={(event) => {
          event.preventDefault()
          setErrors({})
          if (canGenerate) generate.mutate()
        }}
        className="grid gap-6 xl:grid-cols-[1fr_20rem]"
      >
        <div className="space-y-6">
          <Card aria-labelledby="step-lesson">
            <CardHeader id="step-lesson" title="1. Choose a lesson" />
            <CardBody className="grid gap-5 sm:grid-cols-2">
              <FormField label="Course">
                <Select
                  value={courseId}
                  onChange={(event) => setParam('course', event.target.value)}
                  placeholder={coursesQuery.isPending ? 'Loading…' : 'Select a course'}
                  options={courses.map((c) => ({ value: String(c.id), label: c.title }))}
                />
              </FormField>
              <FormField label="Lesson">
                <Select
                  value={lessonId}
                  onChange={(event) => setParam('lesson', event.target.value)}
                  disabled={!courseId || curriculumQuery.isPending}
                  placeholder={courseId ? 'Select a lesson' : 'Choose a course first'}
                  options={lessons.map((l) => ({ value: String(l.id), label: `${l.sectionTitle} — ${l.title}` }))}
                />
              </FormField>
              {locked && (
                <Alert variant="info" className="sm:col-span-2">
                  This course is under review, so its quizzes can’t change right now.
                </Alert>
              )}
              {lesson?.quiz && (
                <label className="flex items-start gap-3 rounded-xl border border-amber-300 bg-amber-50 p-3 text-sm sm:col-span-2 dark:border-amber-800 dark:bg-amber-950/40">
                  <input type="checkbox" className="mt-0.5 size-4 accent-amber-600" checked={replace} onChange={(e) => setReplace(e.target.checked)} />
                  <span>
                    This lesson already has a {lesson.quiz.status} quiz with {lesson.quiz.questions_count} questions.{' '}
                    <strong>Replace it with a new AI-generated draft.</strong>
                  </span>
                </label>
              )}
            </CardBody>
          </Card>

          <Card aria-labelledby="step-source">
            <CardHeader id="step-source" title="2. Source material" />
            <CardBody className="space-y-4">
              <div role="radiogroup" aria-label="Source material" className="grid gap-3 sm:grid-cols-2">
                {[
                  ['lesson', 'Use the lesson content', lesson ? `${lessonChars.toLocaleString()} characters` : 'Select a lesson'],
                  ['paste', 'Paste text', 'Notes, a transcript or an article'],
                ].map(([value, title, hint]) => (
                  <label
                    key={value}
                    className={cn(
                      'flex cursor-pointer flex-col rounded-xl border p-3 has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-brand-500',
                      source === value ? 'border-brand-500 bg-brand-50 dark:bg-brand-950/50' : 'border-slate-300 dark:border-slate-700',
                    )}
                  >
                    <input type="radio" name="source" value={value} checked={source === value} onChange={() => setSource(value)} className="sr-only" />
                    <span className="text-sm font-semibold">{title}</span>
                    <span className="text-xs text-slate-500 dark:text-slate-400">{hint}</span>
                  </label>
                ))}
              </div>

              {source === 'lesson' && lesson && lessonChars < MIN_CHARS && (
                <Alert variant="warning">
                  This lesson has only {lessonChars} characters of text. Add notes to the lesson or paste material instead (at least {MIN_CHARS}).
                </Alert>
              )}
              {source === 'lesson' && lesson && lessonChars >= MIN_CHARS && (
                <p className="max-h-40 overflow-y-auto whitespace-pre-line rounded-lg bg-slate-50 p-3 font-mono text-xs text-slate-600 dark:bg-slate-900 dark:text-slate-400">
                  {lesson.content.slice(0, 1200)}
                  {lesson.content.length > 1200 && '…'}
                </p>
              )}
              {source === 'paste' && (
                <FormField
                  label="Material"
                  error={errors.source_text}
                  hint={`${text.trim().length.toLocaleString()} / 30,000 characters (minimum ${MIN_CHARS})`}
                >
                  <Textarea rows={10} value={text} onChange={(event) => setText(event.target.value)} maxLength={30000} />
                </FormField>
              )}
            </CardBody>
          </Card>
        </div>

        <div>
          <Card aria-labelledby="step-options" className="xl:sticky xl:top-24">
            <CardHeader id="step-options" title="3. Options" />
            <CardBody className="space-y-5">
              <FormField label="Number of questions" error={errors.num_questions}>
                <Select value={count} onChange={(event) => setCount(event.target.value)} options={[3, 5, 8, 10, 15].map((n) => ({ value: String(n), label: String(n) }))} />
              </FormField>
              <FormField label="Difficulty" error={errors.difficulty}>
                <Select
                  value={difficulty}
                  onChange={(event) => setDifficulty(event.target.value)}
                  options={[
                    { value: 'easy', label: 'Easy — recall key facts' },
                    { value: 'medium', label: 'Medium — understand concepts' },
                    { value: 'hard', label: 'Hard — apply and analyse' },
                  ]}
                />
              </FormField>
              <Button type="submit" size="lg" className="w-full" loading={generate.isPending} disabled={!canGenerate}>
                <Wand2 className="size-4" aria-hidden="true" />
                {generate.isPending ? 'Generating…' : 'Generate draft quiz'}
              </Button>
              {generate.isPending && (
                <p className="text-center text-xs text-slate-500" role="status">
                  Claude is reading the material and writing questions. This can take up to a minute.
                </p>
              )}
            </CardBody>
          </Card>
        </div>
      </form>
    </div>
  )
}
