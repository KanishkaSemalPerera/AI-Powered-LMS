// Helpers for lesson videos.

/** Max upload size, matching config/lms.php (video_max_kb = 512000). */
export const VIDEO_MAX_BYTES = 500 * 1024 * 1024
export const VIDEO_TYPES = ['video/mp4', 'video/webm', 'video/quicktime']

/**
 * Recognises YouTube and Vimeo links so they can be embedded with an iframe.
 * Anything else is treated as a direct video file URL.
 * @returns {{ kind: 'youtube' | 'vimeo' | 'file', id?: string, url: string } | null}
 */
export function parseVideoUrl(url) {
  if (!url) return null

  try {
    const parsed = new URL(url)
    const host = parsed.hostname.replace(/^www\.|^m\./, '')

    if (host === 'youtu.be') return { kind: 'youtube', id: parsed.pathname.slice(1), url }
    if (host === 'youtube.com' || host === 'youtube-nocookie.com') {
      const id = parsed.searchParams.get('v') || parsed.pathname.match(/\/(embed|shorts|live)\/([^/?]+)/)?.[2]
      if (id) return { kind: 'youtube', id, url }
    }
    if (host === 'vimeo.com' || host === 'player.vimeo.com') {
      const id = parsed.pathname.match(/(\d+)/)?.[1]
      if (id) return { kind: 'vimeo', id, url }
    }
  } catch {
    return null
  }

  return { kind: 'file', url }
}

/** Reads a video file's duration (seconds) in the browser, without uploading it. */
export function readVideoDuration(file) {
  return new Promise((resolve) => {
    const video = document.createElement('video')
    const objectUrl = URL.createObjectURL(file)
    const done = (seconds) => {
      URL.revokeObjectURL(objectUrl)
      resolve(seconds)
    }
    video.preload = 'metadata'
    video.onloadedmetadata = () => done(Number.isFinite(video.duration) ? Math.round(video.duration) : null)
    video.onerror = () => done(null)
    video.src = objectUrl
  })
}

/** 52428800 → "50 MB" */
export function formatBytes(bytes = 0) {
  if (bytes < 1024) return `${bytes} B`
  const units = ['KB', 'MB', 'GB']
  let value = bytes / 1024
  let unit = 0
  while (value >= 1024 && unit < units.length - 1) {
    value /= 1024
    unit += 1
  }
  return `${value >= 10 ? Math.round(value) : value.toFixed(1)} ${units[unit]}`
}
