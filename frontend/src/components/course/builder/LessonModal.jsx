import { zodResolver } from '@hookform/resolvers/zod'
import { FileText, FileVideo, Link2, Trash2, Upload } from 'lucide-react'
import { useRef, useState } from 'react'
import { useForm, useWatch } from 'react-hook-form'
import { toast } from 'sonner'
import { z } from 'zod'
import * as curriculumApi from '@/api/curriculum'
import VideoPlayer from '@/components/course/VideoPlayer'
import Alert from '@/components/ui/Alert'
import Button from '@/components/ui/Button'
import FormField from '@/components/ui/FormField'
import Input from '@/components/ui/Input'
import Modal from '@/components/ui/Modal'
import Textarea from '@/components/ui/Textarea'
import { applyFieldErrors, getErrorMessage } from '@/utils/apiError'
import { cn } from '@/utils/cn'
import { formatBytes, readVideoDuration, VIDEO_MAX_BYTES, VIDEO_TYPES } from '@/utils/video'

const schema = z.object({
  title: z.string().trim().min(2, 'Title must be at least 2 characters').max(150),
  type: z.enum(['video', 'text']),
  video_url: z.union([z.literal(''), z.url('Enter a full link starting with https://').max(500)]),
  content: z.string().max(100000),
  duration_minutes: z.coerce.number({ error: 'Enter a number' }).min(0).max(1440),
  is_preview: z.boolean(),
})

function TypeOption({ value, current, icon: Icon, title, text, register }) {
  return (
    <label
      className={cn(
        'flex cursor-pointer gap-3 rounded-xl border p-3 has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-brand-500',
        current === value ? 'border-brand-500 bg-brand-50 dark:bg-brand-950/50' : 'border-slate-300 dark:border-slate-700',
      )}
    >
      <input type="radio" value={value} className="sr-only" {...register('type')} />
      <Icon className={cn('mt-0.5 size-5', current === value ? 'text-brand-600' : 'text-slate-400')} aria-hidden="true" />
      <span>
        <span className="block text-sm font-semibold">{title}</span>
        <span className="block text-xs text-slate-500 dark:text-slate-400">{text}</span>
      </span>
    </label>
  )
}

/**
 * Create or edit a lesson. `lesson` is null when creating inside `sectionId`.
 * Saving runs in two steps when a file is chosen: save the lesson, then upload the video.
 */
export default function LessonModal({ lesson, sectionId, onClose, onSaved }) {
  const isNew = !lesson
  const fileInput = useRef(null)
  const [source, setSource] = useState(lesson?.video_url ? 'link' : 'upload')
  const [file, setFile] = useState(null)
  const [fileSeconds, setFileSeconds] = useState(null) // exact duration read from the chosen file
  const [progress, setProgress] = useState(null)
  const [current, setCurrent] = useState(lesson)
  const abort = useRef(null)

  const {
    register,
    handleSubmit,
    setError,
    setValue,
    control,
    formState: { errors, isSubmitting },
  } = useForm({
    resolver: zodResolver(schema),
    defaultValues: {
      title: lesson?.title ?? '',
      type: lesson?.type ?? 'video',
      video_url: lesson?.video_url ?? '',
      content: lesson?.content ?? '',
      duration_minutes: lesson ? Math.round((lesson.duration_seconds / 60) * 10) / 10 : 0,
      is_preview: lesson?.is_preview ?? false,
    },
  })
  const type = useWatch({ control, name: 'type' })
  const busy = isSubmitting || progress !== null

  const chooseFile = async (event) => {
    const picked = event.target.files?.[0]
    event.target.value = ''
    if (!picked) return
    if (!VIDEO_TYPES.includes(picked.type)) return toast.error('Choose an MP4, WebM or MOV video.')
    if (picked.size > VIDEO_MAX_BYTES) return toast.error(`The video must be smaller than ${formatBytes(VIDEO_MAX_BYTES)}.`)

    setFile(picked)
    const seconds = await readVideoDuration(picked)
    setFileSeconds(seconds)
    if (seconds) setValue('duration_minutes', Math.max(0.1, Math.round((seconds / 60) * 10) / 10), { shouldDirty: true })
  }

  const removeUpload = async () => {
    try {
      setCurrent(await curriculumApi.deleteLessonVideo(current.id))
      toast.success('Video removed.')
      onSaved()
    } catch (error) {
      toast.error(getErrorMessage(error))
    }
  }

  /** Footer "Cancel": aborts a running upload, otherwise closes the modal. */
  const handleCancel = () => {
    if (abort.current) abort.current.abort()
    else onClose()
  }

  const onSubmit = async (values) => {
    const payload = {
      ...values,
      // Only send a link when "Video link" is the chosen source.
      video_url: values.type === 'video' && source === 'link' ? values.video_url || null : null,
    }
    // Keep an existing upload when editing other fields with "Upload" selected.
    if (values.type === 'video' && source === 'upload') delete payload.video_url

    try {
      let saved = isNew
        ? await curriculumApi.createLesson(sectionId, payload)
        : await curriculumApi.updateLesson(lesson.id, payload)
      setCurrent(saved)

      if (values.type === 'video' && source === 'upload' && file) {
        abort.current = new AbortController()
        setProgress(0)
        saved = await curriculumApi.uploadLessonVideo(saved.id, file, {
          // Prefer the exact length from the file unless the instructor typed a different value.
          durationSeconds:
            fileSeconds && Math.abs(values.duration_minutes * 60 - fileSeconds) < 6
              ? fileSeconds
              : Math.round(values.duration_minutes * 60) || undefined,
          onProgress: setProgress,
          signal: abort.current.signal,
        })
      }

      toast.success(isNew ? 'Lesson added.' : 'Lesson saved.')
      onSaved()
      onClose()
    } catch (error) {
      if (error.code === 'ERR_CANCELED') {
        toast.info('Upload cancelled. The lesson details were saved.')
        onSaved()
      } else if (!applyFieldErrors(error, setError)) {
        toast.error(getErrorMessage(error))
      }
    } finally {
      setProgress(null)
      abort.current = null
    }
  }

  return (
    <Modal
      open
      onClose={() => !busy && onClose()}
      size="lg"
      title={isNew ? 'Add lesson' : 'Edit lesson'}
      footer={
        <>
          <Button variant="outline" onClick={handleCancel} disabled={isSubmitting && progress === null}>
            {progress !== null ? 'Cancel upload' : 'Cancel'}
          </Button>
          <Button type="submit" form="lesson-form" loading={busy}>
            {progress !== null ? `Uploading ${progress}%` : isNew ? 'Add lesson' : 'Save lesson'}
          </Button>
        </>
      }
    >
      <form id="lesson-form" onSubmit={(event) => handleSubmit(onSubmit)(event)} noValidate>
        <fieldset disabled={busy} className="space-y-5">
          <FormField label="Lesson title" error={errors.title?.message} required>
            <Input autoFocus {...register('title')} />
          </FormField>

          <fieldset>
            <legend className="mb-2 text-sm font-medium text-slate-700 dark:text-slate-200">Lesson type</legend>
            <div className="grid gap-3 sm:grid-cols-2">
              <TypeOption value="video" current={type} icon={FileVideo} title="Video" text="Upload a file or link to YouTube/Vimeo" register={register} />
              <TypeOption value="text" current={type} icon={FileText} title="Article" text="Written lesson in Markdown" register={register} />
            </div>
          </fieldset>

          {type === 'video' && (
            <div className="space-y-4 rounded-xl border border-slate-200 p-4 dark:border-slate-800">
              <div role="tablist" aria-label="Video source" className="flex gap-1 rounded-lg bg-slate-100 p-1 dark:bg-slate-800">
                {[
                  ['upload', 'Upload file', Upload],
                  ['link', 'Video link', Link2],
                ].map(([value, label, Icon]) => (
                  <button
                    key={value}
                    type="button"
                    role="tab"
                    aria-selected={source === value}
                    onClick={() => setSource(value)}
                    className={cn(
                      'flex flex-1 items-center justify-center gap-2 rounded-md px-3 py-1.5 text-sm font-medium',
                      source === value ? 'bg-white shadow-sm dark:bg-slate-900' : 'text-slate-600 dark:text-slate-300',
                    )}
                  >
                    <Icon className="size-4" aria-hidden="true" />
                    {label}
                  </button>
                ))}
              </div>

              {source === 'upload' ? (
                <div className="space-y-3">
                  {current?.video_upload && !file && (
                    <div className="flex items-center justify-between gap-3 rounded-lg bg-slate-50 px-3 py-2 text-sm dark:bg-slate-800/60">
                      <span className="min-w-0 truncate">
                        <FileVideo className="mr-2 inline size-4 text-brand-600" aria-hidden="true" />
                        {current.video_upload.original_name} · {formatBytes(current.video_upload.size)}
                      </span>
                      <Button variant="ghost" size="sm" onClick={removeUpload} aria-label="Remove uploaded video">
                        <Trash2 className="size-4" aria-hidden="true" />
                      </Button>
                    </div>
                  )}
                  {file && (
                    <p className="rounded-lg bg-brand-50 px-3 py-2 text-sm text-brand-800 dark:bg-brand-950 dark:text-brand-200">
                      Ready to upload: <strong>{file.name}</strong> · {formatBytes(file.size)}
                    </p>
                  )}
                  <input ref={fileInput} type="file" accept={VIDEO_TYPES.join(',')} className="sr-only" onChange={chooseFile} aria-label="Choose a video file" />
                  <Button variant="outline" onClick={() => fileInput.current?.click()}>
                    <Upload className="size-4" aria-hidden="true" />
                    {current?.video_upload || file ? 'Choose a different file' : 'Choose video file'}
                  </Button>
                  <p className="text-xs text-slate-500 dark:text-slate-400">
                    MP4, WebM or MOV up to {formatBytes(VIDEO_MAX_BYTES)}. Uploaded videos are private and only playable by enrolled students.
                  </p>
                  {progress !== null && (
                    <div>
                      <div className="h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800" role="progressbar" aria-valuenow={progress} aria-valuemin={0} aria-valuemax={100} aria-label="Upload progress">
                        <div className="h-full bg-brand-600 transition-all" style={{ width: `${progress}%` }} />
                      </div>
                      <p className="mt-1 text-xs text-slate-500">Uploading… {progress}% — keep this window open.</p>
                    </div>
                  )}
                </div>
              ) : (
                <FormField label="Video link" error={errors.video_url?.message} hint="YouTube, Vimeo or a direct https link to an MP4 file.">
                  <Input type="url" placeholder="https://www.youtube.com/watch?v=…" {...register('video_url')} />
                </FormField>
              )}

              {current?.playback_url && !file && (
                <div>
                  <p className="mb-2 text-xs font-medium uppercase tracking-wide text-slate-500">Current video</p>
                  <VideoPlayer url={current.playback_url} title={current.title} />
                </div>
              )}
            </div>
          )}

          <FormField
            label={type === 'text' ? 'Lesson content' : 'Lesson notes'}
            error={errors.content?.message}
            hint={
              type === 'text'
                ? 'Markdown is supported: ## headings, **bold**, lists and ```code blocks```.'
                : 'Optional notes or transcript shown under the video. The AI study assistant also reads these.'
            }
          >
            <Textarea rows={type === 'text' ? 12 : 5} className="font-mono text-xs" {...register('content')} />
          </FormField>

          <div className="grid gap-5 sm:grid-cols-2">
            <FormField label="Duration (minutes)" error={errors.duration_minutes?.message} hint={type === 'video' ? 'Filled in automatically from an uploaded file.' : 'Estimated reading time.'}>
              <Input type="number" min="0" step="0.5" inputMode="decimal" {...register('duration_minutes')} />
            </FormField>
            <label className="flex items-start gap-3 self-center rounded-xl border border-slate-200 p-3 dark:border-slate-800">
              <input type="checkbox" className="mt-0.5 size-4 accent-brand-600" {...register('is_preview')} />
              <span>
                <span className="block text-sm font-medium">Free preview</span>
                <span className="block text-xs text-slate-500 dark:text-slate-400">Anyone can watch this lesson before enrolling.</span>
              </span>
            </label>
          </div>

          {type === 'text' && current?.video_upload && (
            <Alert variant="warning">Saving as an article will delete the uploaded video from this lesson.</Alert>
          )}
        </fieldset>
      </form>
    </Modal>
  )
}
