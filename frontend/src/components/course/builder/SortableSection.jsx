import { SortableContext, useSortable, verticalListSortingStrategy } from '@dnd-kit/sortable'
import { CSS } from '@dnd-kit/utilities'
import { Check, GripVertical, Pencil, Plus, Trash2, X } from 'lucide-react'
import { useState } from 'react'
import Button from '@/components/ui/Button'
import Input from '@/components/ui/Input'
import { cn } from '@/utils/cn'
import { formatDuration, plural } from '@/utils/format'
import SortableLesson from './SortableLesson'

function TitleEditor({ title, onSave, onCancel }) {
  const [value, setValue] = useState(title)
  const submit = (event) => {
    event.preventDefault()
    if (value.trim().length >= 2) onSave(value.trim())
  }

  return (
    <form onSubmit={submit} className="flex flex-1 items-center gap-2">
      <Input
        value={value}
        onChange={(event) => setValue(event.target.value)}
        onKeyDown={(event) => event.key === 'Escape' && onCancel()}
        aria-label="Section title"
        autoFocus
        maxLength={150}
        className="h-9"
      />
      <Button type="submit" size="sm" aria-label="Save section title" disabled={value.trim().length < 2}>
        <Check className="size-4" aria-hidden="true" />
      </Button>
      <Button variant="ghost" size="sm" onClick={onCancel} aria-label="Cancel renaming">
        <X className="size-4" aria-hidden="true" />
      </Button>
    </form>
  )
}

export default function SortableSection({ courseId, section, index, disabled, onRename, onDelete, onAddLesson, onEditLesson, onDeleteLesson }) {
  const [editing, setEditing] = useState(false)
  const { attributes, listeners, setNodeRef, setActivatorNodeRef, transform, transition, isDragging } = useSortable({
    id: `section-${section.id}`,
    data: { type: 'section', sectionId: section.id },
    disabled,
  })

  const duration = section.lessons.reduce((sum, lesson) => sum + lesson.duration_seconds, 0)

  return (
    <section
      ref={setNodeRef}
      style={{ transform: CSS.Transform.toString(transform), transition }}
      aria-label={`Section ${index + 1}: ${section.title}`}
      className={cn(
        'rounded-2xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-900/60',
        isDragging && 'relative z-20 opacity-90 shadow-xl ring-2 ring-brand-500',
      )}
    >
      <div className="flex items-center gap-2 px-1 pb-3">
        <button
          ref={setActivatorNodeRef}
          type="button"
          className="cursor-grab touch-none rounded p-1 text-slate-400 hover:bg-slate-200 hover:text-slate-600 disabled:cursor-not-allowed disabled:opacity-40 dark:hover:bg-slate-800"
          aria-label={`Drag to reorder section ${section.title}`}
          disabled={disabled}
          {...attributes}
          {...listeners}
        >
          <GripVertical className="size-5" aria-hidden="true" />
        </button>

        {editing ? (
          <TitleEditor
            title={section.title}
            onCancel={() => setEditing(false)}
            onSave={async (title) => {
              await onRename(title)
              setEditing(false)
            }}
          />
        ) : (
          <>
            <div className="min-w-0 flex-1">
              <h3 className="truncate font-semibold">
                <span className="text-slate-500 dark:text-slate-400">Section {index + 1}:</span> {section.title}
              </h3>
              <p className="text-xs text-slate-500 dark:text-slate-400">
                {plural(section.lessons.length, 'lesson')} · {formatDuration(duration)}
              </p>
            </div>
            <Button variant="ghost" size="sm" onClick={() => setEditing(true)} disabled={disabled} aria-label={`Rename section ${section.title}`}>
              <Pencil className="size-4" aria-hidden="true" />
            </Button>
            <Button
              variant="ghost"
              size="sm"
              onClick={onDelete}
              disabled={disabled}
              className="text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/40"
              aria-label={`Delete section ${section.title}`}
            >
              <Trash2 className="size-4" aria-hidden="true" />
            </Button>
          </>
        )}
      </div>

      <SortableContext items={section.lessons.map((lesson) => `lesson-${lesson.id}`)} strategy={verticalListSortingStrategy}>
        <ul className="min-h-12 space-y-2">
          {section.lessons.map((lesson) => (
            <SortableLesson
              key={lesson.id}
              courseId={courseId}
              lesson={lesson}
              disabled={disabled}
              onEdit={() => onEditLesson(lesson)}
              onDelete={() => onDeleteLesson(lesson)}
            />
          ))}
          {section.lessons.length === 0 && (
            <li className="rounded-lg border border-dashed border-slate-300 px-3 py-3 text-center text-xs text-slate-500 dark:border-slate-700">
              No lessons yet. Add one, or drag a lesson here.
            </li>
          )}
        </ul>
      </SortableContext>

      <Button variant="ghost" size="sm" className="mt-2" onClick={onAddLesson} disabled={disabled}>
        <Plus className="size-4" aria-hidden="true" />
        Add lesson
      </Button>
    </section>
  )
}
