import { cn } from '@/utils/cn'
import { parseVideoUrl } from '@/utils/video'

/**
 * Plays a lesson video: YouTube/Vimeo links are embedded, uploaded files and
 * direct links use the native <video> element (signed URLs work as-is).
 */
export default function VideoPlayer({ url, title, className, onEnded }) {
  const video = parseVideoUrl(url)
  const frame = cn('aspect-video w-full overflow-hidden rounded-xl bg-black', className)

  if (!video) {
    return (
      <div className={cn(frame, 'grid place-items-center text-sm text-slate-400')}>No video available for this lesson.</div>
    )
  }

  if (video.kind === 'youtube') {
    return (
      <iframe
        className={frame}
        src={`https://www.youtube-nocookie.com/embed/${encodeURIComponent(video.id)}?rel=0`}
        title={title ?? 'Lesson video'}
        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
        allowFullScreen
      />
    )
  }

  if (video.kind === 'vimeo') {
    return (
      <iframe
        className={frame}
        src={`https://player.vimeo.com/video/${encodeURIComponent(video.id)}`}
        title={title ?? 'Lesson video'}
        allow="autoplay; fullscreen; picture-in-picture"
        allowFullScreen
      />
    )
  }

  return (
    <video className={frame} src={video.url} controls preload="metadata" playsInline onEnded={onEnded}>
      Your browser does not support HTML5 video.
    </video>
  )
}
