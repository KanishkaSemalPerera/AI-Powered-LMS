import client, { unwrap } from './client'
import { cleanParams } from './courses'

// ------------------------------------------------------------------ Courses

/** GET /admin/courses — params: status, q, page, per_page */
export async function getAdminCourses(params) {
  const { data, meta } = unwrap(await client.get('/admin/courses', { params: cleanParams(params) }))
  return { data, meta }
}

export async function approveCourse(id) {
  return unwrap(await client.post(`/admin/courses/${id}/approve`))
}

export async function rejectCourse(id, reason) {
  return unwrap(await client.post(`/admin/courses/${id}/reject`, { reason }))
}

// ------------------------------------------------------------------ Categories

export async function getAdminCategories() {
  return unwrap(await client.get('/admin/categories', { params: { per_page: 100 } })).data
}

export async function createCategory(payload) {
  return unwrap(await client.post('/admin/categories', payload))
}

export async function updateCategory(id, payload) {
  return unwrap(await client.put(`/admin/categories/${id}`, payload))
}

export async function deleteCategory(id) {
  return unwrap(await client.delete(`/admin/categories/${id}`))
}
