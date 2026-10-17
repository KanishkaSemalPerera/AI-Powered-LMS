import { zodResolver } from '@hookform/resolvers/zod'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import { ArrowLeft, ListChecks, Plus, Sparkles, Trash2 } from 'lucide-react'
import { useState } from 'react'
import { useFieldArray, useForm } from 'react-hook-form'
import { Link, useNavigate, useParams } from 'react-router'
import { toast } from 'sonner'
import { z } from 'zod'
import { getMyCourse } from '@/api/instructor'
import { deleteLessonQuiz, getLessonQuiz, saveLessonQuiz } from '@/api/quizzes'
import QuestionEditor from '@/components/quiz/QuestionEditor'
import Alert from '@/components/ui/Alert'
import Badge from '@/components/ui/Badge'
import Button from '@/components/ui/Button'
import Card, { CardBody, CardHeader } from '@/components/ui/Card'
import ConfirmDialog from '@/components/ui/ConfirmDialog'
import EmptyState from '@/components/ui/EmptyState'
import ErrorState from '@/components/ui/ErrorState'
import FormField from '@/components/ui/FormField'
import Input from '@/components/ui/Input'
import PageLoader from '@/components/ui/PageLoader'
import Select from '@/components/ui/Select'
import Textarea from '@/components/ui/Textarea'
import { useDocumentTitle } from '@/hooks/useDocumentTitle'
import { applyFieldErrors, getErrorMessage } from '@/utils/apiError'

const questionSchema = z
  .object({
    question: z.string().trim().min(5, 'Write a question of at least 5 characters').max(1000),
    options: z
      .array(z.object({ value: z.string().trim().min(1, 'Option can’t be empty').max(300) }))
      .min(2, 'Add at least 2 options')
      .max(6),
    // Radio inputs always yield strings, so coerce to a number here.
    correct_option: z.coerce.number({ error: 'Choose the correct option' }).int().min(0),
    explanation: z.string().max(1000),
  })
  .refine((q) => q.correct_option < q.options.length, { message: 'Choose the correct option', path: ['correct_option'] })
  .refine((q) => new Set(q.options.map((o) => o.value.trim().toLowerCase())).size === q.options.length, {
    message: 'Each option must be different',
    path: ['options'],
  })

const schema = z
  .object({
    title: z.string().trim().min(3, 'Title must be at least 3 characters').max(150),
    description: z.string().max(1000),
    pass_mark: z.coerce.number({ error: 'Enter a pass mark' }).int().min(1, 'At least 1%').max(100, 'At most 100%'),
    status: z.enum(['draft', 'published']),
    questions: z.array(questionSchema).max(50),
  })
  .refine((v) => v.status === 'draft' || v.questions.length > 0, {
    message: 'Add at least one question before publishing',
    path: ['questions'],
  })

const blankQuestion = () => ({ question: '', options: [{ value: '' }, { value: '' }, { value: '' }, { value: '' }], correct_option: 0, explanation: '' })

function toForm(quiz, lessonTitle) {
  return {
    title: quiz?.title ?? `Quiz: ${lessonTitle ?? ''}`.trim(),
    description: quiz?.description ?? '',
    pass_mark: quiz?.pass_mark ?? 70,
    status: quiz?.status ?? 'draft',
    questions: (quiz?.questions ?? []).map((q) => ({
      question: q.question,
      options: q.options.map((value) => ({ value })),
      correct_option: q.correct_option,
      explanation: q.explanation ?? '',
    })),
  }
}

function toPayload(values) {
  return {
    ...values,
    questions: values.questions.map((q) => ({
      ...q,
      correct_option: Number(q.correct_option),
      options: q.options.map((o) => o.value.trim()),
    })),
  }
}

function QuizForm({ courseId, lessonId, data, locked }) {
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [confirmDelete, setConfirmDelete] = useState(false)
  const [deleting, setDeleting] = useState(false)
  const quiz = data.quiz

  const {
    register,
    control,
    handleSubmit,
    setValue,
    setError,
    reset,
    formState: { errors, isSubmitting, isDirty },
  } = useForm({ resolver: zodResolver(schema), defaultValues: toForm(quiz, data.lesson.title) })
  const { fields, append, remove, move } = useFieldArray({ control, name: 'questions' })

  const refresh = () => {
    queryClient.invalidateQueries({ queryKey: ['instructor', 'quiz', String(lessonId)] })
    queryClient.invalidateQueries({ queryKey: ['instructor', 'curriculum', String(courseId)] })
  }

  const onSubmit = async (values) => {
    try {
      const { data: saved, message } = await saveLessonQuiz(lessonId, toPayload(values))
      reset(toForm(saved))
      toast.success(message)
      refresh()
    } catch (error) {
      if (!applyFieldErrors(error, setError)) toast.error(getErrorMessage(error))
    }
  }

  const onDelete = async () => {
    setDeleting(true)
    try {
      await deleteLessonQuiz(lessonId)
      toast.success('Quiz deleted.')
      refresh()
      navigate(`/instructor/courses/${courseId}/edit?tab=curriculum`)
    } catch (error) {
      toast.error(getErrorMessage(error))
      setDeleting(false)
    }
  }

  return (
    <form onSubmit={(event) => handleSubmit(onSubmit)(event)} noValidate className="space-y-6">
      {quiz?.is_ai_generated && (
        <Alert variant="info" title="Generated with AI">
          Review every question and answer carefully before publishing.
        </Alert>
      )}

      <Card aria-labelledby="quiz-settings">
        <CardHeader id="quiz-settings" title="Quiz settings" />
        <CardBody>
          <fieldset disabled={locked} className="grid gap-5 sm:grid-cols-2">
            <FormField label="Title" error={errors.title?.message} required className="sm:col-span-2">
              <Input {...register('title')} />
            </FormField>
            <FormField label="Description" error={errors.description?.message} className="sm:col-span-2">
              <Textarea rows={2} {...register('description')} />
            </FormField>
            <FormField label="Pass mark (%)" error={errors.pass_mark?.message} hint="Students need at least this score to pass.">
              <Input type="number" min="1" max="100" {...register('pass_mark')} />
            </FormField>
            <FormField label="Status" error={errors.status?.message} hint="Draft quizzes are hidden from students.">
              <Select
                options={[
                  { value: 'draft', label: 'Draft' },
                  { value: 'published', label: 'Published' },
                ]}
                {...register('status')}
              />
            </FormField>
          </fieldset>
        </CardBody>
      </Card>

      <section aria-labelledby="questions-heading" className="space-y-4">
        <div className="flex flex-wrap items-center justify-between gap-2">
          <h2 id="questions-heading" className="text-lg font-semibold">
            Questions <span className="text-slate-500">({fields.length})</span>
          </h2>
        </div>
        {(errors.questions?.message || errors.questions?.root?.message) && (
          <Alert variant="error">{errors.questions?.message || errors.questions?.root?.message}</Alert>
        )}

        {fields.length === 0 ? (
          <EmptyState icon={ListChecks} title="No questions yet" description="Add your first multiple-choice question." />
        ) : (
          fields.map((field, index) => (
            <QuestionEditor
              key={field.id}
              index={index}
              total={fields.length}
              control={control}
              register={register}
              setValue={setValue}
              errors={errors}
              disabled={locked}
              onRemove={() => remove(index)}
              onMove={move}
            />
          ))
        )}

        {!locked && (
          <Button variant="outline" onClick={() => append(blankQuestion())} disabled={fields.length >= 50}>
            <Plus className="size-4" aria-hidden="true" />
            Add question
          </Button>
        )}
      </section>

      {!locked && (
        <div className="sticky bottom-0 -mx-4 flex flex-wrap items-center justify-between gap-2 border-t border-slate-200 bg-white/90 px-4 py-3 backdrop-blur sm:mx-0 sm:rounded-2xl sm:border dark:border-slate-800 dark:bg-slate-950/90">
          {quiz ? (
            <Button
              variant="ghost"
              className="text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/40"
              onClick={() => setConfirmDelete(true)}
            >
              <Trash2 className="size-4" aria-hidden="true" />
              Delete quiz
            </Button>
          ) : (
            <span />
          )}
          <Button type="submit" loading={isSubmitting} disabled={quiz && !isDirty}>
            {quiz ? 'Save quiz' : 'Create quiz'}
          </Button>
        </div>
      )}

      <ConfirmDialog
        open={confirmDelete}
        onClose={() => setConfirmDelete(false)}
        onConfirm={onDelete}
        loading={deleting}
        variant="danger"
        confirmLabel="Delete quiz"
        title="Delete this quiz?"
        message="The quiz, its questions and all student attempts will be permanently deleted."
      />
    </form>
  )
}

export default function QuizEditorPage() {
  const { courseId, lessonId } = useParams()
  const quizQuery = useQuery({ queryKey: ['instructor', 'quiz', lessonId], queryFn: () => getLessonQuiz(lessonId) })
  const courseQuery = useQuery({ queryKey: ['instructor', 'course', courseId], queryFn: () => getMyCourse(courseId) })
  useDocumentTitle(quizQuery.data ? `Quiz: ${quizQuery.data.lesson.title}` : 'Quiz editor')

  if (quizQuery.isPending || courseQuery.isPending) return <PageLoader />
  const error = quizQuery.error || courseQuery.error
  if (error) return <ErrorState title="Couldn't load the quiz" error={error} onRetry={() => quizQuery.refetch()} />

  const locked = courseQuery.data.status === 'pending'
  const quiz = quizQuery.data.quiz

  return (
    <div className="space-y-6">
      <Link
        to={`/instructor/courses/${courseId}/edit?tab=curriculum`}
        className="inline-flex items-center gap-1 text-sm font-medium text-slate-600 hover:underline dark:text-slate-300"
      >
        <ArrowLeft className="size-4" aria-hidden="true" />
        {courseQuery.data.title}
      </Link>

      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <div className="flex flex-wrap items-center gap-2">
            <h1 className="text-2xl font-bold tracking-tight">Lesson quiz</h1>
            {quiz && <Badge variant={quiz.status === 'published' ? 'success' : 'neutral'}>{quiz.status === 'published' ? 'Published' : 'Draft'}</Badge>}
            {quiz?.is_ai_generated && (
              <Badge variant="brand">
                <Sparkles className="size-3" aria-hidden="true" />
                AI generated
              </Badge>
            )}
          </div>
          <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">For the lesson “{quizQuery.data.lesson.title}”.</p>
        </div>
      </div>

      {locked && <Alert variant="info">This course is under review, so its quizzes can’t be edited right now.</Alert>}

      <QuizForm key={quiz?.updated_at ?? 'new'} courseId={courseId} lessonId={lessonId} data={quizQuery.data} locked={locked} />
    </div>
  )
}
