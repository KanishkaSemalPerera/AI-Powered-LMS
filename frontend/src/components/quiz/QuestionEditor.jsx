import { ArrowDown, ArrowUp, Plus, Trash2, X } from 'lucide-react'
import { useFieldArray, useWatch } from 'react-hook-form'
import Button from '@/components/ui/Button'
import FormField from '@/components/ui/FormField'
import Input from '@/components/ui/Input'
import Textarea from '@/components/ui/Textarea'
import { cn } from '@/utils/cn'

const MAX_OPTIONS = 6

/**
 * One multiple-choice question in the quiz editor: text, 2–6 options with a
 * "correct" radio, and an explanation shown to students after they submit.
 */
export default function QuestionEditor({ index, total, control, register, setValue, errors, disabled, onRemove, onMove }) {
  const name = `questions.${index}`
  const { fields, append, remove } = useFieldArray({ control, name: `${name}.options` })
  const correct = Number(useWatch({ control, name: `${name}.correct_option` }))
  const e = errors?.questions?.[index]

  /** Keeps the correct answer pointing at the same option when another option is removed. */
  const removeOption = (optionIndex) => {
    remove(optionIndex)
    if (optionIndex < correct) setValue(`${name}.correct_option`, correct - 1, { shouldDirty: true })
    else if (optionIndex === correct) setValue(`${name}.correct_option`, 0, { shouldDirty: true })
  }

  return (
    <fieldset className="rounded-2xl border border-slate-200 bg-white p-4 sm:p-5 dark:border-slate-800 dark:bg-slate-900" disabled={disabled}>
      <div className="mb-3 flex items-center justify-between gap-2">
        <legend className="text-sm font-semibold">Question {index + 1}</legend>
        <div className="flex gap-1">
          <Button variant="ghost" size="sm" onClick={() => onMove(index, index - 1)} disabled={index === 0} aria-label={`Move question ${index + 1} up`}>
            <ArrowUp className="size-4" aria-hidden="true" />
          </Button>
          <Button variant="ghost" size="sm" onClick={() => onMove(index, index + 1)} disabled={index === total - 1} aria-label={`Move question ${index + 1} down`}>
            <ArrowDown className="size-4" aria-hidden="true" />
          </Button>
          <Button
            variant="ghost"
            size="sm"
            onClick={onRemove}
            className="text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/40"
            aria-label={`Delete question ${index + 1}`}
          >
            <Trash2 className="size-4" aria-hidden="true" />
          </Button>
        </div>
      </div>

      <div className="space-y-4">
        <FormField label="Question" error={e?.question?.message} required>
          <Textarea rows={2} {...register(`${name}.question`)} />
        </FormField>

        <div>
          <p className="mb-2 text-sm font-medium text-slate-700 dark:text-slate-200">
            Options <span className="font-normal text-slate-500">— select the correct answer</span>
          </p>
          <div className="space-y-2" role="radiogroup" aria-label={`Correct answer for question ${index + 1}`}>
            {fields.map((field, optionIndex) => (
              <div key={field.id}>
                <div
                  className={cn(
                    'flex items-center gap-2 rounded-lg border p-1.5 pl-3',
                    correct === optionIndex ? 'border-emerald-400 bg-emerald-50 dark:border-emerald-700 dark:bg-emerald-950/30' : 'border-slate-200 dark:border-slate-700',
                  )}
                >
                  <input
                    type="radio"
                    value={optionIndex}
                    className="size-4 accent-emerald-600"
                    aria-label={`Option ${optionIndex + 1} is correct`}
                    {...register(`${name}.correct_option`)}
                  />
                  <Input
                    {...register(`${name}.options.${optionIndex}.value`)}
                    placeholder={`Option ${optionIndex + 1}`}
                    aria-label={`Question ${index + 1}, option ${optionIndex + 1}`}
                    invalid={Boolean(e?.options?.[optionIndex]?.value)}
                    className="h-9 border-0 bg-transparent shadow-none focus:ring-0 dark:bg-transparent"
                  />
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={() => removeOption(optionIndex)}
                    disabled={fields.length <= 2}
                    aria-label={`Remove option ${optionIndex + 1}`}
                  >
                    <X className="size-4" aria-hidden="true" />
                  </Button>
                </div>
                {e?.options?.[optionIndex]?.value && (
                  <p role="alert" className="mt-1 text-xs font-medium text-red-600 dark:text-red-400">
                    {e.options[optionIndex].value.message}
                  </p>
                )}
              </div>
            ))}
          </div>
          {(e?.options?.root?.message || e?.options?.message || e?.correct_option?.message) && (
            <p role="alert" className="mt-1 text-xs font-medium text-red-600 dark:text-red-400">
              {e?.options?.root?.message || e?.options?.message || e?.correct_option?.message}
            </p>
          )}
          {fields.length < MAX_OPTIONS && (
            <Button variant="ghost" size="sm" className="mt-2" onClick={() => append({ value: '' })}>
              <Plus className="size-4" aria-hidden="true" />
              Add option
            </Button>
          )}
        </div>

        <FormField label="Explanation" error={e?.explanation?.message} hint="Shown after submitting — explain why the answer is correct.">
          <Textarea rows={2} {...register(`${name}.explanation`)} />
        </FormField>
      </div>
    </fieldset>
  )
}
