import { Plus, X } from 'lucide-react'
import { useFieldArray } from 'react-hook-form'
import Button from '@/components/ui/Button'
import Input from '@/components/ui/Input'

/**
 * Editable list of short text items (e.g. "What you'll learn"), backed by
 * React Hook Form's useFieldArray. Items are stored as { value } objects.
 */
export default function ListFieldEditor({ control, register, name, label, hint, placeholder, errors, max = 12 }) {
  const { fields, append, remove } = useFieldArray({ control, name })

  return (
    <fieldset>
      <legend className="text-sm font-medium text-slate-700 dark:text-slate-200">{label}</legend>
      {hint && <p className="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{hint}</p>}

      <div className="mt-2 space-y-2">
        {fields.map((field, index) => (
          <div key={field.id}>
            <div className="flex gap-2">
              <Input
                {...register(`${name}.${index}.value`)}
                placeholder={placeholder}
                aria-label={`${label} ${index + 1}`}
                invalid={Boolean(errors?.[index]?.value)}
              />
              <Button variant="ghost" size="icon" onClick={() => remove(index)} aria-label={`Remove item ${index + 1}`}>
                <X className="size-4" aria-hidden="true" />
              </Button>
            </div>
            {errors?.[index]?.value && (
              <p role="alert" className="mt-1 text-xs font-medium text-red-600 dark:text-red-400">
                {errors[index].value.message}
              </p>
            )}
          </div>
        ))}
      </div>

      {fields.length < max && (
        <Button variant="outline" size="sm" className="mt-2" onClick={() => append({ value: '' })}>
          <Plus className="size-4" aria-hidden="true" />
          Add item
        </Button>
      )}
    </fieldset>
  )
}
