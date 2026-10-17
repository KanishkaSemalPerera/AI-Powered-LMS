import client, { unwrap } from './client'
import { cleanParams } from './courses'

/** POST /courses/{id}/enroll — free courses. Returns { data: enrollment, message }. */
export async function enrollInCourse(courseId) {
  return unwrap(await client.post(`/courses/${courseId}/enroll`))
}

/** POST /courses/{id}/checkout → { checkout_url, session_id } */
export async function startCheckout(courseId) {
  return unwrap(await client.post(`/courses/${courseId}/checkout`)).data
}

/** POST /checkout/confirm → { data: payment, message } */
export async function confirmCheckout(sessionId) {
  return unwrap(await client.post('/checkout/confirm', { session_id: sessionId }))
}

/** GET /my/courses — params: status (in_progress|completed), page, per_page */
export async function getMyCourses(params) {
  const { data, meta } = unwrap(await client.get('/my/courses', { params: cleanParams(params) }))
  return { data, meta }
}

export async function getPayments(params) {
  const { data, meta } = unwrap(await client.get('/payments', { params: cleanParams(params) }))
  return { data, meta }
}
