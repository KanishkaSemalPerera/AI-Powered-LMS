import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { ArrowLeft, CirclePlay, ExternalLink, ImagePlus, ListTree, Send, Sparkles, SquarePen } from 'lucide-react'
import { useRef, useState } from 'react'
import { useForm } from 'react-hook-form'
import { Link, useNavigate, useParams, useSearchParams } from 'react-router'
import { toast } from 'sonner'
import { z } from 'zod'
import { getCategories } from '@/api/courses'
import * as instructorApi from '@/api/instructor'
import CourseStatusBadge from '@/components/course/CourseStatusBadge'
import CourseThumbnail from '@/components/course/CourseThumbnail'
import CurriculumBuilder from '@/components/course/builder/CurriculumBuilder'
import AiDescriptionModal from '@/components/course/AiDescriptionModal'
import ListFieldEditor from '@/components/course/ListFieldEditor'
import Alert from '@/components/ui/Alert'
import Button from '@/components/ui/Button'
import { buttonClasses } from '@/components/ui/buttonStyles'
import Card, { CardBody, CardHeader } from '@/components/ui/Card'
import ConfirmDialog from '@/components/ui/ConfirmDialog'
import ErrorState from '@/components/ui/ErrorState'
import FormField from '@/components/ui/FormField'
import Input from '@/components/ui/Input'
import PageLoader from '@/components/ui/PageLoader'
import Select from '@/components/ui/Select'
import Textarea from '@/components/ui/Textarea'
import { useDocumentTitle } from '@/hooks/useDocumentTitle'
import { applyFieldErrors, getErrorMessage } from '@/utils/apiError'
import { LEVEL_OPTIONS } from '@/utils/courseOptions'
import { cn } from '@/utils/cn'
import { plural } from '@/utils/format'
import { learnPath } from '@/utils/paths'

const EDITOR_TABS = [
  { value: 'details', label: 'Details', icon: SquarePen },
  { value: 'curriculum', label: 'Curriculum', icon: ListTree },
]

const THUMBNAIL_TYPES = ['image/jpeg', 'image/png', 'image/webp']
const THUMBNAIL_MAX_BYTES = 4 * 1024 * 1024

const listItem = z.object({ value: z.string().trim().max(200, 'Keep each item under 200 characters') })

const schema = z.object({
  title: z.string().trim().min(5, 'Title must be at least 5 characters').max(150, 'Title must be at most 150 characters'),
  subtitle: z.string().trim().max(255, 'Subtitle must be at most 255 characters'),
  category_id: z.string(),
  level: z.enum(LEVEL_OPTIONS.map((o) => o.value)),
  language: z.string().trim().min(1, 'Language is required').max(50),
  price: z.coerce
    .number({ error: 'Enter a price (0 for free)' })
    .min(0, 'Price cannot be negative')
    .max(9999.99, 'Price is too high')
    .refine((value) => value === 0 || value >= 0.5, 'Paid courses must cost at least 0.50'),
  description: z.string().max(20000),
  what_you_will_learn: z.array(listItem).max(12),
  requirements: z.array(listItem).max(12),
})

/** API course → form values (lists become { value } objects for useFieldArray). */
function toFormValues(course) {
  return {
    title: course?.title ?? '',
    subtitle: course?.subtitle ?? '',
    category_id: course?.category_id ? String(course.category_id) : '',
    level: course?.level ?? 'beginner',
    language: course?.language ?? 'English',
    price: course?.price ?? 0,
    description: course?.description ?? '',
    what_you_will_learn: (course?.what_you_will_learn ?? []).map((value) => ({ value })),
    requirements: (course?.requirements ?? []).map((value) => ({ value })),
  }
}

/** Form values → API payload. */
function toPayload(values) {
  return {
    ...values,
    category_id: values.category_id ? Number(values.category_id) : null,
    what_you_will_learn: values.what_you_will_learn.map((item) => item.value).filter(Boolean),
    requirements: values.requirements.map((item) => item.value).filter(Boolean),
  }
}

// ------------------------------------------------------------------ Thumbnail

function ThumbnailCard({ course, disabled }) {
  const queryClient = useQueryClient()
  const input = useRef(null)

  const upload = useMutation({
    mutationFn: (file) => instructorApi.uploadCourseThumbnail(course.id, file),
    onSuccess: (updated) => {
      queryClient.setQueryData(['instructor', 'course', String(course.id)], updated)
      queryClient.invalidateQueries({ queryKey: ['instructor', 'courses'] })
      toast.success('Thumbnail updated.')
    },
    onError: (error) => toast.error(getErrorMessage(error)),
  })

  const onChange = (event) => {
    const file = event.target.files?.[0]
    event.target.value = ''
    if (!file) return
    if (!THUMBNAIL_TYPES.includes(file.type)) return toast.error('Please choose a JPG, PNG or WebP image.')
    if (file.size > THUMBNAIL_MAX_BYTES) return toast.error('The image must be smaller than 4 MB.')
    upload.mutate(file)
  }

  return (
    <Card aria-labelledby="thumb-heading">
      <CardHeader id="thumb-heading" title="Thumbnail" description="16:9, at least 640×360. JPG, PNG or WebP up to 4 MB." />
      <CardBody className="space-y-4">
        <CourseThumbnail course={course} className="rounded-xl" />
        <input ref={input} type="file" accept={THUMBNAIL_TYPES.join(',')} className="sr-only" onChange={onChange} aria-label="Choose a thumbnail image" />
        <Button variant="outline" className="w-full" onClick={() => input.current?.click()} loading={upload.isPending} disabled={disabled}>
          <ImagePlus className="size-4" aria-hidden="true" />
          {course.thumbnail_url ? 'Replace image' : 'Upload image'}
        </Button>
      </CardBody>
    </Card>
  )
}

// ------------------------------------------------------------------ Form

function CourseForm({ course, categories, disabled }) {
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const isNew = !course

  const {
    register,
    control,
    handleSubmit,
    setError,
    reset,
    setValue,
    getValues,
    formState: { errors, isSubmitting, isDirty },
  } = useForm({ resolver: zodResolver(schema), defaultValues: toFormValues(course) })
  const [aiOpen, setAiOpen] = useState(false)

  /** Current form values the AI helper writes from. */
  const aiContext = () => {
    const values = getValues()
    return {
      title: values.title,
      subtitle: values.subtitle || null,
      level: values.level,
      category: categories?.find((c) => String(c.id) === values.category_id)?.name ?? null,
    }
  }

  /** Fills the form with the accepted AI suggestion (saved only when the instructor saves). */
  const applySuggestion = ({ subtitle, description, what_you_will_learn }) => {
    const options = { shouldDirty: true, shouldValidate: true }
    setValue('subtitle', subtitle, options)
    setValue('description', description, options)
    setValue('what_you_will_learn', what_you_will_learn.map((value) => ({ value })), options)
    toast.success('Suggestion applied. Review it, then save your changes.')
  }

  const onSubmit = async (values) => {
    try {
      if (isNew) {
        const created = await instructorApi.createCourse(toPayload(values))
        toast.success('Course created. Keep building it below.')
        queryClient.invalidateQueries({ queryKey: ['instructor', 'courses'] })
        navigate(`/instructor/courses/${created.id}/edit`, { replace: true })
        return
      }
      const updated = await instructorApi.updateCourse(course.id, toPayload(values))
      queryClient.setQueryData(['instructor', 'course', String(course.id)], updated)
      queryClient.invalidateQueries({ queryKey: ['instructor', 'courses'] })
      reset(toFormValues(updated))
      toast.success('Course saved.')
    } catch (error) {
      if (!applyFieldErrors(error, setError)) toast.error(getErrorMessage(error))
    }
  }

  const categoryOptions = (categories ?? []).map((c) => ({ value: String(c.id), label: c.name }))

  return (
    <Card aria-labelledby="details-heading">
      <CardHeader id="details-heading" title="Course details" description="This information appears on the course page." />
      <CardBody>
        <form onSubmit={handleSubmit(onSubmit)} noValidate>
          <fieldset disabled={disabled || isSubmitting} className="space-y-5">
            <FormField label="Title" error={errors.title?.message} required>
              <Input placeholder="e.g. Modern JavaScript from Scratch" {...register('title')} />
            </FormField>
            <FormField label="Subtitle" error={errors.subtitle?.message} hint="One sentence that sells the course.">
              <Input {...register('subtitle')} />
            </FormField>

            <div className="grid gap-5 sm:grid-cols-2">
              <FormField label="Category" error={errors.category_id?.message}>
                <Select placeholder="Choose a category" options={categoryOptions} {...register('category_id')} />
              </FormField>
              <FormField label="Level" error={errors.level?.message}>
                <Select options={LEVEL_OPTIONS} {...register('level')} />
              </FormField>
              <FormField label="Language" error={errors.language?.message}>
                <Input {...register('language')} />
              </FormField>
              <FormField label="Price (USD)" error={errors.price?.message} hint="Enter 0 to make the course free.">
                <Input type="number" min="0" step="0.01" inputMode="decimal" {...register('price')} />
              </FormField>
            </div>

            <div className="flex justify-end">
              <Button variant="outline" size="sm" onClick={() => setAiOpen(true)}>
                <Sparkles className="size-4 text-brand-500" aria-hidden="true" />
                Write with AI
              </Button>
            </div>
            <FormField
              label="Description"
              error={errors.description?.message}
              hint="At least 100 characters are needed before you can submit for review. Separate paragraphs with a blank line."
            >
              <Textarea rows={8} {...register('description')} />
            </FormField>

            <ListFieldEditor
              control={control}
              register={register}
              name="what_you_will_learn"
              label="What students will learn"
              hint="Short, specific outcomes."
              placeholder="e.g. Build a REST API with Laravel"
              errors={errors.what_you_will_learn}
            />
            <ListFieldEditor
              control={control}
              register={register}
              name="requirements"
              label="Requirements"
              placeholder="e.g. Basic HTML knowledge"
              errors={errors.requirements}
            />

            <div className="flex justify-end gap-2 border-t border-slate-200 pt-5 dark:border-slate-800">
              {!isNew && (
                <Button variant="outline" onClick={() => reset(toFormValues(course))} disabled={!isDirty}>
                  Discard changes
                </Button>
              )}
              <Button type="submit" loading={isSubmitting} disabled={!isNew && !isDirty}>
                {isNew ? 'Create course' : 'Save changes'}
              </Button>
            </div>
          </fieldset>
        </form>
        {aiOpen && (
          <AiDescriptionModal courseId={course?.id} context={aiContext} onApply={applySuggestion} onClose={() => setAiOpen(false)} />
        )}
      </CardBody>
    </Card>
  )
}

// ------------------------------------------------------------------ Submit for review

/** "Submit for review" button; reports what is missing via onProblems (shown below the header). */
function SubmitButton({ course, onProblems }) {
  const queryClient = useQueryClient()
  const [confirming, setConfirming] = useState(false)
  const setProblems = onProblems

  const submit = useMutation({
    mutationFn: () => instructorApi.submitCourse(course.id),
    onSuccess: (updated) => {
      setConfirming(false)
      setProblems(null)
      queryClient.setQueryData(['instructor', 'course', String(course.id)], updated)
      queryClient.invalidateQueries({ queryKey: ['instructor', 'courses'] })
      toast.success('Submitted for review.')
    },
    onError: (error) => {
      setConfirming(false)
      const errors = error.response?.data?.errors
      if (error.response?.status === 422 && errors) setProblems(Object.values(errors).flat())
      else toast.error(getErrorMessage(error))
    },
  })

  if (!['draft', 'rejected'].includes(course.status)) return null

  return (
    <>
      <Button onClick={() => setConfirming(true)}>
        <Send className="size-4" aria-hidden="true" />
        Submit for review
      </Button>
      <ConfirmDialog
        open={confirming}
        onClose={() => setConfirming(false)}
        onConfirm={() => submit.mutate()}
        loading={submit.isPending}
        confirmLabel="Submit"
        title="Submit for review?"
        message="An admin will review your course before it is published. You can't edit it while it is under review."
      />
    </>
  )
}

// ------------------------------------------------------------------ Page

export default function CourseEditorPage() {
  const { id } = useParams()
  const isNew = !id

  const categoriesQuery = useQuery({ queryKey: ['categories'], queryFn: getCategories, staleTime: 5 * 60 * 1000 })
  const courseQuery = useQuery({
    queryKey: ['instructor', 'course', id],
    queryFn: () => instructorApi.getMyCourse(id),
    enabled: !isNew,
  })

  const course = courseQuery.data
  const [problems, setProblems] = useState(null)
  const [searchParams, setSearchParams] = useSearchParams()
  const tab = searchParams.get('tab') === 'curriculum' ? 'curriculum' : 'details'
  useDocumentTitle(isNew ? 'New course' : course ? `Edit: ${course.title}` : 'Edit course')

  if (!isNew && courseQuery.isPending) return <PageLoader />
  if (!isNew && courseQuery.error) {
    return <ErrorState title="Couldn't load this course" error={courseQuery.error} onRetry={() => courseQuery.refetch()} />
  }

  const locked = course?.status === 'pending'

  return (
    <div className="space-y-6">
      <Link to="/instructor/courses" className="inline-flex items-center gap-1 text-sm font-medium text-slate-600 hover:underline dark:text-slate-300">
        <ArrowLeft className="size-4" aria-hidden="true" />
        My courses
      </Link>

      <div className="flex flex-wrap items-start justify-between gap-4">
        <div className="min-w-0">
          <div className="flex flex-wrap items-center gap-3">
            <h1 className="text-2xl font-bold tracking-tight">{isNew ? 'Create a course' : course.title}</h1>
            {course && <CourseStatusBadge status={course.status} />}
          </div>
          <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
            {isNew
              ? 'Start with the basics. You can add lessons and a thumbnail after creating it.'
              : `${plural(course.sections?.length ?? 0, 'section')} · ${plural(course.lessons_count ?? 0, 'lesson')}`}
          </p>
        </div>
        {course && (
          <div className="flex flex-wrap gap-2">
            <Link to={`/courses/${course.slug}`} className={buttonClasses({ variant: 'outline' })}>
              <ExternalLink className="size-4" aria-hidden="true" />
              {course.status === 'published' ? 'View page' : 'Preview'}
            </Link>
            {(course.lessons_count ?? 0) > 0 && (
              <Link to={learnPath(course)} className={buttonClasses({ variant: 'outline' })}>
                <CirclePlay className="size-4" aria-hidden="true" />
                Preview as student
              </Link>
            )}
            <SubmitButton course={course} onProblems={setProblems} />
          </div>
        )}
      </div>

      {problems && (
        <Alert variant="warning" title="A few things are missing before you can submit for review">
          <ul className="mt-1 list-disc pl-5">
            {problems.map((problem) => (
              <li key={problem}>{problem}</li>
            ))}
          </ul>
        </Alert>
      )}

      {course?.status === 'rejected' && course.rejection_reason && (
        <Alert variant="error" title="Changes requested by the reviewer">
          {course.rejection_reason}
        </Alert>
      )}
      {locked && (
        <Alert variant="info" title="Under review">
          This course is waiting for an admin. Editing is disabled until the review is finished.
        </Alert>
      )}
      {course?.status === 'published' && (
        <Alert variant="success">This course is live. Changes you save are visible to students immediately.</Alert>
      )}

      {course && (
        <div role="tablist" aria-label="Course editor sections" className="flex gap-1 border-b border-slate-200 dark:border-slate-800">
          {EDITOR_TABS.map((item) => (
            <button
              key={item.value}
              type="button"
              role="tab"
              id={`tab-${item.value}`}
              aria-selected={tab === item.value}
              aria-controls={`panel-${item.value}`}
              onClick={() => setSearchParams(item.value === 'details' ? {} : { tab: item.value }, { replace: true })}
              className={cn(
                '-mb-px flex items-center gap-2 border-b-2 px-4 py-2.5 text-sm font-medium transition-colors',
                tab === item.value
                  ? 'border-brand-600 text-brand-700 dark:text-brand-300'
                  : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200',
              )}
            >
              <item.icon className="size-4" aria-hidden="true" />
              {item.label}
            </button>
          ))}
        </div>
      )}

      {tab === 'curriculum' && course ? (
        <div role="tabpanel" id="panel-curriculum" aria-labelledby="tab-curriculum">
          <CurriculumBuilder courseId={course.id} locked={locked} />
        </div>
      ) : (
        <div role="tabpanel" id="panel-details" aria-labelledby={course ? 'tab-details' : undefined} className="grid gap-6 xl:grid-cols-[1fr_20rem]">
          <CourseForm key={course?.id ?? 'new'} course={course} categories={categoriesQuery.data} disabled={locked} />
          {course && (
            <div>
              <ThumbnailCard course={course} disabled={locked} />
            </div>
          )}
        </div>
      )}
    </div>
  )
}
