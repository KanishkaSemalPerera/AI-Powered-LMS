import {
  closestCenter,
  closestCorners,
  DndContext,
  KeyboardSensor,
  PointerSensor,
  useSensor,
  useSensors,
} from '@dnd-kit/core'
import { arrayMove, SortableContext, sortableKeyboardCoordinates, verticalListSortingStrategy } from '@dnd-kit/sortable'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import { ListPlus, Plus } from 'lucide-react'
import { useState } from 'react'
import { toast } from 'sonner'
import * as curriculumApi from '@/api/curriculum'
import Button from '@/components/ui/Button'
import ConfirmDialog from '@/components/ui/ConfirmDialog'
import EmptyState from '@/components/ui/EmptyState'
import ErrorState from '@/components/ui/ErrorState'
import Input from '@/components/ui/Input'
import Skeleton from '@/components/ui/Skeleton'
import { getErrorMessage } from '@/utils/apiError'
import { formatDuration, plural } from '@/utils/format'
import LessonModal from './LessonModal'
import SortableSection from './SortableSection'

const idOf = (dndId) => Number(String(dndId).split('-')[1])
const isSection = (dndId) => String(dndId).startsWith('section-')

/** Index of the section that contains a lesson (or is) the given dnd id. */
function findSectionIndex(sections, dndId) {
  if (isSection(dndId)) return sections.findIndex((s) => s.id === idOf(dndId))
  return sections.findIndex((s) => s.lessons.some((l) => l.id === idOf(dndId)))
}

const orderOf = (sections) => sections.map((s) => ({ id: s.id, lessons: s.lessons.map((l) => l.id) }))

/** Sections drag only among sections; lessons can be dropped into any section. */
function collisionDetection(args) {
  if (isSection(args.active.id)) {
    return closestCenter({ ...args, droppableContainers: args.droppableContainers.filter((c) => isSection(c.id)) })
  }
  return closestCorners(args)
}

export default function CurriculumBuilder({ courseId, locked }) {
  const queryClient = useQueryClient()
  const queryKey = ['instructor', 'curriculum', String(courseId)]
  const query = useQuery({ queryKey, queryFn: () => curriculumApi.getCurriculum(courseId) })

  // Local copy while dragging; null means "use server data".
  const [dragSections, setDragSections] = useState(null)
  const sections = dragSections ?? query.data ?? []

  const [newSectionTitle, setNewSectionTitle] = useState('')
  const [addingSection, setAddingSection] = useState(false)
  const [lessonModal, setLessonModal] = useState(null) // { lesson?, sectionId }
  const [confirm, setConfirm] = useState(null) // { kind, item }
  const [deleting, setDeleting] = useState(false)

  const sensors = useSensors(
    useSensor(PointerSensor, { activationConstraint: { distance: 5 } }),
    useSensor(KeyboardSensor, { coordinateGetter: sortableKeyboardCoordinates }),
  )

  const refresh = () => {
    queryClient.invalidateQueries({ queryKey })
    queryClient.invalidateQueries({ queryKey: ['instructor', 'course', String(courseId)] })
  }

  // ---------------------------------------------------------------- Drag & drop

  const onDragStart = () => setDragSections(query.data ?? [])

  /** Moves a lesson into another section while hovering, so it can be dropped there. */
  const onDragOver = ({ active, over }) => {
    if (!over || isSection(active.id)) return
    setDragSections((current) => {
      const list = current ?? query.data ?? []
      const from = findSectionIndex(list, active.id)
      const to = findSectionIndex(list, over.id)
      if (from < 0 || to < 0 || from === to) return list

      const lesson = list[from].lessons.find((l) => l.id === idOf(active.id))
      const overIndex = isSection(over.id) ? list[to].lessons.length : list[to].lessons.findIndex((l) => l.id === idOf(over.id))

      return list.map((section, i) => {
        if (i === from) return { ...section, lessons: section.lessons.filter((l) => l.id !== lesson.id) }
        if (i === to) {
          const lessons = [...section.lessons]
          lessons.splice(overIndex < 0 ? lessons.length : overIndex, 0, lesson)
          return { ...section, lessons }
        }
        return section
      })
    })
  }

  const onDragEnd = async ({ active, over }) => {
    let next = dragSections ?? query.data ?? []

    if (over) {
      if (isSection(active.id)) {
        const from = next.findIndex((s) => s.id === idOf(active.id))
        const to = next.findIndex((s) => s.id === idOf(over.id))
        if (from >= 0 && to >= 0 && from !== to) next = arrayMove(next, from, to)
      } else {
        const sectionIndex = findSectionIndex(next, active.id)
        const section = next[sectionIndex]
        const from = section?.lessons.findIndex((l) => l.id === idOf(active.id)) ?? -1
        const to = isSection(over.id) ? section.lessons.length - 1 : section?.lessons.findIndex((l) => l.id === idOf(over.id))
        if (from >= 0 && to >= 0 && from !== to) {
          next = next.map((s, i) => (i === sectionIndex ? { ...s, lessons: arrayMove(s.lessons, from, to) } : s))
        }
      }
    }

    const changed = JSON.stringify(orderOf(next)) !== JSON.stringify(orderOf(query.data ?? []))
    if (!changed) {
      setDragSections(null)
      return
    }

    // Optimistic update, rolled back if the server rejects the new order.
    const previous = query.data
    queryClient.setQueryData(queryKey, next)
    setDragSections(null)
    try {
      queryClient.setQueryData(queryKey, await curriculumApi.reorderCurriculum(courseId, orderOf(next)))
      toast.success('Order saved.')
    } catch (error) {
      queryClient.setQueryData(queryKey, previous)
      toast.error(getErrorMessage(error))
    }
  }

  // ---------------------------------------------------------------- Actions

  const addSection = async (event) => {
    event.preventDefault()
    if (newSectionTitle.trim().length < 2) return
    setAddingSection(true)
    try {
      await curriculumApi.createSection(courseId, newSectionTitle.trim())
      setNewSectionTitle('')
      toast.success('Section added.')
      refresh()
    } catch (error) {
      toast.error(getErrorMessage(error))
    } finally {
      setAddingSection(false)
    }
  }

  const renameSection = async (section, title) => {
    try {
      await curriculumApi.renameSection(section.id, title)
      refresh()
    } catch (error) {
      toast.error(getErrorMessage(error))
      throw error
    }
  }

  const confirmDelete = async () => {
    setDeleting(true)
    try {
      if (confirm.kind === 'section') await curriculumApi.deleteSection(confirm.item.id)
      else await curriculumApi.deleteLesson(confirm.item.id)
      toast.success(confirm.kind === 'section' ? 'Section deleted.' : 'Lesson deleted.')
      setConfirm(null)
      refresh()
    } catch (error) {
      toast.error(getErrorMessage(error))
    } finally {
      setDeleting(false)
    }
  }

  // ---------------------------------------------------------------- Render

  if (query.isPending) {
    return (
      <div className="space-y-4" aria-busy="true">
        <Skeleton className="h-40 w-full" />
        <Skeleton className="h-40 w-full" />
      </div>
    )
  }
  if (query.error) return <ErrorState title="Couldn't load the curriculum" error={query.error} onRetry={() => query.refetch()} />

  const lessonCount = sections.reduce((sum, s) => sum + s.lessons.length, 0)
  const totalDuration = sections.reduce((sum, s) => sum + s.lessons.reduce((t, l) => t + l.duration_seconds, 0), 0)

  return (
    <div className="space-y-4">
      <div className="flex flex-wrap items-center justify-between gap-2">
        <p className="text-sm text-slate-600 dark:text-slate-400">
          {plural(sections.length, 'section')} · {plural(lessonCount, 'lesson')} · {formatDuration(totalDuration)}
        </p>
        {!locked && sections.length > 0 && (
          <p className="text-xs text-slate-500">Drag the handles to reorder. Lessons can be moved between sections.</p>
        )}
      </div>

      {sections.length === 0 ? (
        <EmptyState icon={ListPlus} title="Build your curriculum" description="Start by adding a section, then add lessons to it." />
      ) : (
        <DndContext
          sensors={sensors}
          collisionDetection={collisionDetection}
          onDragStart={onDragStart}
          onDragOver={onDragOver}
          onDragEnd={onDragEnd}
          onDragCancel={() => setDragSections(null)}
        >
          <SortableContext items={sections.map((s) => `section-${s.id}`)} strategy={verticalListSortingStrategy}>
            <div className="space-y-4">
              {sections.map((section, index) => (
                <SortableSection
                  key={section.id}
                  courseId={courseId}
                  section={section}
                  index={index}
                  disabled={locked}
                  onRename={(title) => renameSection(section, title)}
                  onDelete={() => setConfirm({ kind: 'section', item: section })}
                  onAddLesson={() => setLessonModal({ sectionId: section.id })}
                  onEditLesson={(lesson) => setLessonModal({ lesson, sectionId: section.id })}
                  onDeleteLesson={(lesson) => setConfirm({ kind: 'lesson', item: lesson })}
                />
              ))}
            </div>
          </SortableContext>
        </DndContext>
      )}

      {!locked && (
        <form onSubmit={addSection} className="flex flex-col gap-2 rounded-2xl border border-dashed border-slate-300 p-3 sm:flex-row dark:border-slate-700">
          <Input
            value={newSectionTitle}
            onChange={(event) => setNewSectionTitle(event.target.value)}
            placeholder="New section title, e.g. Getting started"
            aria-label="New section title"
            maxLength={150}
          />
          <Button type="submit" loading={addingSection} disabled={newSectionTitle.trim().length < 2}>
            <Plus className="size-4" aria-hidden="true" />
            Add section
          </Button>
        </form>
      )}

      {lessonModal && (
        <LessonModal
          lesson={lessonModal.lesson ?? null}
          sectionId={lessonModal.sectionId}
          onClose={() => setLessonModal(null)}
          onSaved={refresh}
        />
      )}

      <ConfirmDialog
        open={Boolean(confirm)}
        onClose={() => setConfirm(null)}
        onConfirm={confirmDelete}
        loading={deleting}
        variant="danger"
        confirmLabel={confirm?.kind === 'section' ? 'Delete section' : 'Delete lesson'}
        title={confirm?.kind === 'section' ? 'Delete this section?' : 'Delete this lesson?'}
        message={
          confirm?.kind === 'section'
            ? `“${confirm?.item.title}” and its ${plural(confirm?.item.lessons.length ?? 0, 'lesson')} (including uploaded videos) will be permanently deleted.`
            : `“${confirm?.item.title}” and any uploaded video will be permanently deleted.`
        }
      />
    </div>
  )
}
