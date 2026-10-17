import client, { unwrap } from './client'

// Course builder endpoints (instructor only).

export async function getCurriculum(courseId) {
  return unwrap(await client.get(`/instructor/courses/${courseId}/curriculum`)).data
}

/** order: [{ id: sectionId, lessons: [lessonId, ...] }, ...] — the complete new order. */
export async function reorderCurriculum(courseId, order) {
  return unwrap(await client.put(`/instructor/courses/${courseId}/curriculum/order`, { sections: order })).data
}

export async function createSection(courseId, title) {
  return unwrap(await client.post(`/instructor/courses/${courseId}/sections`, { title })).data
}

export async function renameSection(sectionId, title) {
  return unwrap(await client.put(`/instructor/sections/${sectionId}`, { title })).data
}

export async function deleteSection(sectionId) {
  await client.delete(`/instructor/sections/${sectionId}`)
}

/** payload: { title, type, content, video_url, duration_minutes, is_preview } */
export async function createLesson(sectionId, payload) {
  return unwrap(await client.post(`/instructor/sections/${sectionId}/lessons`, payload)).data
}

export async function updateLesson(lessonId, payload) {
  return unwrap(await client.put(`/instructor/lessons/${lessonId}`, payload)).data
}

export async function deleteLesson(lessonId) {
  await client.delete(`/instructor/lessons/${lessonId}`)
}

/**
 * Uploads a video file with progress reporting.
 * onProgress receives a number from 0 to 100.
 */
export async function uploadLessonVideo(lessonId, file, { durationSeconds, onProgress, signal } = {}) {
  const form = new FormData()
  form.append('video', file)
  if (durationSeconds) form.append('duration_seconds', String(durationSeconds))

  const response = await client.post(`/instructor/lessons/${lessonId}/video`, form, {
    timeout: 0, // large files can take minutes
    signal,
    onUploadProgress: (event) => {
      if (event.total) onProgress?.(Math.round((event.loaded / event.total) * 100))
    },
  })
  return unwrap(response).data
}

export async function deleteLessonVideo(lessonId) {
  return unwrap(await client.delete(`/instructor/lessons/${lessonId}/video`)).data
}
