import client, { unwrap } from './client'

/** Drops empty values so URLs stay clean (?q=&level= → nothing). */
export function cleanParams(params = {}) {
  return Object.fromEntries(
    Object.entries(params).filter(([, value]) => value !== undefined && value !== null && value !== ''),
  )
}

/**
 * GET /courses — params: q, category, level, price (free|paid), min_rating,
 * featured, sort, page, per_page → { data: Course[], meta }
 */
export async function getCourses(params) {
  const { data, meta } = unwrap(await client.get('/courses', { params: cleanParams(params) }))
  return { data, meta }
}

/** GET /courses/{slug} — includes curriculum outline. */
export async function getCourse(slug) {
  return unwrap(await client.get(`/courses/${encodeURIComponent(slug)}`)).data
}

/** GET /categories → Category[] (with published course counts). */
export async function getCategories() {
  return unwrap(await client.get('/categories', { params: { per_page: 100 } })).data
}
