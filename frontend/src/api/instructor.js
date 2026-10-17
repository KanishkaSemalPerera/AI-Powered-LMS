import client, { unwrap } from './client'
import { cleanParams } from './courses'

/** GET /instructor/courses — params: q, status, page, per_page */
export async function getMyCourses(params) {
  const { data, meta } = unwrap(await client.get('/instructor/courses', { params: cleanParams(params) }))
  return { data, meta }
}

export async function getMyCourse(id) {
  return unwrap(await client.get(`/instructor/courses/${id}`)).data
}

export async function createCourse(payload) {
  return unwrap(await client.post('/instructor/courses', payload)).data
}

export async function updateCourse(id, payload) {
  return unwrap(await client.put(`/instructor/courses/${id}`, payload)).data
}

export async function uploadCourseThumbnail(id, file) {
  const form = new FormData()
  form.append('thumbnail', file)
  return unwrap(await client.post(`/instructor/courses/${id}/thumbnail`, form)).data
}

export async function submitCourse(id) {
  return unwrap(await client.post(`/instructor/courses/${id}/submit`)).data
}

export async function deleteCourse(id) {
  await client.delete(`/instructor/courses/${id}`)
}
