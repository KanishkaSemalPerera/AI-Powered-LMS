import { useSortable } from '@dnd-kit/sortable'
import { CSS } from '@dnd-kit/utilities'
import { CircleAlert, CirclePlay, FileText, GripVertical, Pencil, Trash2 } from 'lucide-react'
import Badge from '@/components/ui/Badge'
import Button from '@/components/ui/Button'
import { cn } from '@/utils/cn'
import { formatDuration } from '@/utils/format'

/** A lesson is "empty" when it has nothing to show students yet. */
function isLessonEmpty(lesson) {
  return lesson.type === 'video' ? !lesson.playback_url : !lesson.content?.trim()
}

export default function SortableLesson({ lesson, disabled, onEdit, onDelete }) {
  const { attributes, listeners, setNodeRef, setActivatorNodeRef, transform, transition, isDragging } = useSortable({
    id: `lesson-${lesson.id}`,
    data: { type: 'lesson', lessonId: lesson.id },
    disabled,
  })

  const Icon = lesson.type === 'video' ? CirclePlay : FileText
  const empty = isLessonEmpty(lesson)

  return (
    <li
      ref={setNodeRef}
      style={{ transform: CSS.Transform.toString(transform), transition }}
      className={cn(
        'flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-2 py-2 text-sm dark:border-slate-800 dark:bg-slate-900',
        isDragging && 'relative z-10 opacity-80 shadow-lg ring-2 ring-brand-500',
      )}
    >
      <button
        ref={setActivatorNodeRef}
        type="button"
        className="cursor-grab touch-none rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 disabled:cursor-not-allowed disabled:opacity-40 dark:hover:bg-slate-800"
        aria-label={`Drag to reorder lesson ${lesson.title}`}
        disabled={disabled}
        {...attributes}
        {...listeners}
      >
        <GripVertical className="size-4" aria-hidden="true" />
      </button>
      <Icon className="size-4 shrink-0 text-slate-400" aria-hidden="true" />
      <span className="min-w-0 flex-1 truncate">{lesson.title}</span>
      {lesson.is_preview && <Badge variant="brand">Preview</Badge>}
      {empty && (
        <Badge variant="warning">
          <CircleAlert className="size-3" aria-hidden="true" />
          {lesson.type === 'video' ? 'No video' : 'No content'}
        </Badge>
      )}
      <span className="hidden w-14 text-right text-xs text-slate-500 sm:inline">{formatDuration(lesson.duration_seconds)}</span>
      <Button variant="ghost" size="sm" onClick={onEdit} disabled={disabled} aria-label={`Edit lesson ${lesson.title}`}>
        <Pencil className="size-4" aria-hidden="true" />
      </Button>
      <Button
        variant="ghost"
        size="sm"
        onClick={onDelete}
        disabled={disabled}
        className="text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/40"
        aria-label={`Delete lesson ${lesson.title}`}
      >
        <Trash2 className="size-4" aria-hidden="true" />
      </Button>
    </li>
  )
}
