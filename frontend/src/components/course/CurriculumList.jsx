import { ChevronDown, CirclePlay, FileText } from 'lucide-react'
import { useState } from 'react'
import Badge from '@/components/ui/Badge'
import { cn } from '@/utils/cn'
import { formatDuration, plural } from '@/utils/format'

/** Collapsible sections with lesson titles, durations and preview flags. */
export default function CurriculumList({
  sections = [],
  defaultOpen = 1,
  emptyText = "The curriculum hasn't been published yet.",
}) {
  const [openIds, setOpenIds] = useState(() => new Set(sections.slice(0, defaultOpen).map((s) => s.id)))

  const toggle = (id) =>
    setOpenIds((current) => {
      const next = new Set(current)
      if (next.has(id)) next.delete(id)
      else next.add(id)
      return next
    })

  const allOpen = sections.length > 0 && openIds.size === sections.length

  if (!sections.length) {
    return <p className="text-sm text-slate-500 dark:text-slate-400">{emptyText}</p>
  }

  return (
    <div>
      <div className="mb-3 flex justify-end">
        <button
          type="button"
          onClick={() => setOpenIds(allOpen ? new Set() : new Set(sections.map((s) => s.id)))}
          className="text-sm font-medium text-brand-600 hover:underline dark:text-brand-400"
        >
          {allOpen ? 'Collapse all sections' : 'Expand all sections'}
        </button>
      </div>

      <div className="divide-y divide-slate-200 overflow-hidden rounded-xl border border-slate-200 dark:divide-slate-800 dark:border-slate-800">
        {sections.map((section, index) => {
          const open = openIds.has(section.id)
          const duration = section.lessons.reduce((sum, lesson) => sum + lesson.duration_seconds, 0)
          const panelId = `section-panel-${section.id}`

          return (
            <div key={section.id}>
              <h3>
                <button
                  type="button"
                  onClick={() => toggle(section.id)}
                  aria-expanded={open}
                  aria-controls={panelId}
                  className="flex w-full items-center gap-3 bg-slate-50 px-4 py-3 text-left hover:bg-slate-100 dark:bg-slate-900 dark:hover:bg-slate-800"
                >
                  <ChevronDown
                    className={cn('size-4 shrink-0 text-slate-500 transition-transform', open && 'rotate-180')}
                    aria-hidden="true"
                  />
                  <span className="flex-1 font-semibold">
                    Section {index + 1}: {section.title}
                  </span>
                  <span className="hidden text-xs text-slate-500 sm:inline dark:text-slate-400">
                    {plural(section.lessons.length, 'lesson')} · {formatDuration(duration)}
                  </span>
                </button>
              </h3>

              {open && (
                <ul id={panelId} className="divide-y divide-slate-100 dark:divide-slate-800">
                  {section.lessons.map((lesson) => {
                    const Icon = lesson.type === 'video' ? CirclePlay : FileText
                    return (
                      <li key={lesson.id} className="flex items-center gap-3 px-4 py-2.5 text-sm">
                        <Icon className="size-4 shrink-0 text-slate-400" aria-hidden="true" />
                        <span className="flex-1">{lesson.title}</span>
                        {lesson.is_preview && <Badge variant="brand">Preview</Badge>}
                        <span className="w-14 text-right text-xs text-slate-500 dark:text-slate-400">
                          {formatDuration(lesson.duration_seconds)}
                        </span>
                      </li>
                    )
                  })}
                </ul>
              )}
            </div>
          )
        })}
      </div>
    </div>
  )
}
