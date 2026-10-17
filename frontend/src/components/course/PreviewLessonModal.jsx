import { useQuery } from '@tanstack/react-query'
import { getPreviewLesson } from '@/api/learn'
import ErrorState from '@/components/ui/ErrorState'
import Modal from '@/components/ui/Modal'
import Skeleton from '@/components/ui/Skeleton'
import Markdown from './Markdown'
import VideoPlayer from './VideoPlayer'

/** Plays a free-preview lesson from the public course page. */
export default function PreviewLessonModal({ courseSlug, lessonId, onClose }) {
  const query = useQuery({
    queryKey: ['preview-lesson', courseSlug, lessonId],
    queryFn: () => getPreviewLesson(courseSlug, lessonId),
  })
  const lesson = query.data

  return (
    <Modal open onClose={onClose} size="lg" title={lesson ? `Preview: ${lesson.title}` : 'Lesson preview'}>
      {query.isPending ? (
        <Skeleton className="aspect-video w-full rounded-xl" />
      ) : query.error ? (
        <ErrorState title="Couldn't load this preview" error={query.error} />
      ) : (
        <div className="space-y-5">
          {lesson.type === 'video' && <VideoPlayer url={lesson.playback_url} title={lesson.title} />}
          {lesson.content?.trim() && (
            <div className="max-h-[40vh] overflow-y-auto">
              <Markdown className="prose-sm">{lesson.content}</Markdown>
            </div>
          )}
        </div>
      )}
    </Modal>
  )
}
