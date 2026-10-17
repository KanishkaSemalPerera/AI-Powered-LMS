import client, { unwrap } from './client'

const base = (slug) => `/learn/${encodeURIComponent(slug)}`

/** Curriculum with completion state → { course, mode: 'student'|'preview', sections, progress } */
export async function getPlayer(slug) {
  return unwrap(await client.get(base(slug))).data
}

/** Full lesson → { lesson, is_completed, navigation } (also saves the resume point). */
export async function getLesson(slug, lessonId) {
  return unwrap(await client.get(`${base(slug)}/lessons/${lessonId}`)).data
}

/** → { data: progress (with course_completed_now), message } */
export async function completeLesson(slug, lessonId) {
  return unwrap(await client.post(`${base(slug)}/lessons/${lessonId}/complete`))
}

export async function uncompleteLesson(slug, lessonId) {
  return unwrap(await client.delete(`${base(slug)}/lessons/${lessonId}/complete`))
}

/** Public free-preview lesson. */
export async function getPreviewLesson(slug, lessonId) {
  return unwrap(await client.get(`/courses/${encodeURIComponent(slug)}/lessons/${lessonId}/preview`)).data
}
