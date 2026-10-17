import { useMutation } from '@tanstack/react-query'
import { Check, RefreshCw, Sparkles } from 'lucide-react'
import { useState } from 'react'
import { toast } from 'sonner'
import { suggestCourseDescription } from '@/api/ai'
import Alert from '@/components/ui/Alert'
import AiUsageNote from '@/components/ui/AiUsageNote'
import Button from '@/components/ui/Button'
import FormField from '@/components/ui/FormField'
import Modal from '@/components/ui/Modal'
import Select from '@/components/ui/Select'
import Textarea from '@/components/ui/Textarea'
import { useAiUsage } from '@/hooks/useAiUsage'
import { getErrorMessage } from '@/utils/apiError'

const TONES = [
  { value: 'friendly', label: 'Friendly' },
  { value: 'professional', label: 'Professional' },
  { value: 'enthusiastic', label: 'Enthusiastic' },
  { value: 'concise', label: 'Concise' },
]

/**
 * "Write with AI" for the course editor. context() returns the current form
 * values (title, subtitle, level, category name); onApply receives the
 * accepted suggestion. Nothing is saved until the instructor saves the form.
 */
export default function AiDescriptionModal({ courseId, context, onApply, onClose }) {
  const [notes, setNotes] = useState('')
  const [tone, setTone] = useState('friendly')
  const { usage, configured, refresh } = useAiUsage()

  const suggest = useMutation({
    mutationFn: () => {
      const { title, subtitle, level, category } = context()
      return suggestCourseDescription({ title, subtitle, level, category, notes, tone, course_id: courseId ?? null })
    },
    onSettled: () => refresh(),
    onError: (error) => toast.error(getErrorMessage(error)),
  })

  const suggestion = suggest.data
  const titleTooShort = (context().title ?? '').trim().length < 5

  return (
    <Modal
      open
      onClose={() => !suggest.isPending && onClose()}
      size="lg"
      title="Write with AI"
      description="Claude drafts a subtitle, description and learning outcomes from your course title and notes."
      footer={
        suggestion ? (
          <>
            <Button variant="outline" onClick={() => suggest.mutate()} loading={suggest.isPending}>
              <RefreshCw className="size-4" aria-hidden="true" />
              Try again
            </Button>
            <Button
              onClick={() => {
                onApply(suggestion)
                onClose()
              }}
            >
              <Check className="size-4" aria-hidden="true" />
              Use this
            </Button>
          </>
        ) : (
          <>
            <Button variant="outline" onClick={onClose} disabled={suggest.isPending}>
              Cancel
            </Button>
            <Button onClick={() => suggest.mutate()} loading={suggest.isPending} disabled={!configured || titleTooShort}>
              <Sparkles className="size-4" aria-hidden="true" />
              Generate
            </Button>
          </>
        )
      }
    >
      <div className="space-y-4">
        {!configured && <AiUsageNote configured={false} />}
        {titleTooShort && <Alert variant="warning">Give the course a title first (at least 5 characters).</Alert>}

        {!suggestion ? (
          <>
            <FormField label="What does the course cover?" hint="Optional: topics, audience, projects. The more specific, the better.">
              <Textarea rows={5} value={notes} onChange={(event) => setNotes(event.target.value)} maxLength={3000} />
            </FormField>
            <FormField label="Tone">
              <Select value={tone} onChange={(event) => setTone(event.target.value)} options={TONES} />
            </FormField>
            {configured && <AiUsageNote usage={usage} configured />}
          </>
        ) : (
          <div className="space-y-4 text-sm">
            <Alert variant="info">Review the text — AI can make mistakes. Applying it only fills the form; nothing is saved yet.</Alert>
            <div>
              <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Subtitle</p>
              <p className="mt-1">{suggestion.subtitle}</p>
            </div>
            <div>
              <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Description</p>
              <div className="mt-1 max-h-56 space-y-2 overflow-y-auto">
                {suggestion.description.split(/\n{2,}/).map((paragraph, index) => (
                  <p key={index}>{paragraph}</p>
                ))}
              </div>
            </div>
            <div>
              <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">What students will learn</p>
              <ul className="mt-1 list-disc space-y-1 pl-5">
                {suggestion.what_you_will_learn.map((item) => (
                  <li key={item}>{item}</li>
                ))}
              </ul>
            </div>
          </div>
        )}
      </div>
    </Modal>
  )
}
