import client, { unwrap } from './client'

// ------------------------------------------------------------------ Instructor

/** → { lesson: { id, title, course_id }, quiz: Quiz | null } (quiz includes the answer key) */
export async function getLessonQuiz(lessonId) {
  return unwrap(await client.get(`/instructor/lessons/${lessonId}/quiz`)).data
}

/** Create or replace the quiz: { title, description, pass_mark, status, questions: [...] } */
export async function saveLessonQuiz(lessonId, payload) {
  return unwrap(await client.put(`/instructor/lessons/${lessonId}/quiz`, payload))
}

export async function deleteLessonQuiz(lessonId) {
  return unwrap(await client.delete(`/instructor/lessons/${lessonId}/quiz`))
}

// ------------------------------------------------------------------ Student

const quizBase = (slug, lessonId) => `/learn/${encodeURIComponent(slug)}/lessons/${lessonId}/quiz`

/** → { quiz (no answers), lesson, can_submit, summary: { attempts, best_score, passed } } */
export async function getQuiz(slug, lessonId) {
  return unwrap(await client.get(quizBase(slug, lessonId))).data
}

/** answers: { [questionId]: optionIndex } → { data: attempt with review, message } */
export async function submitQuizAttempt(slug, lessonId, answers) {
  return unwrap(await client.post(`${quizBase(slug, lessonId)}/attempts`, { answers }))
}

export async function getQuizAttempts(slug, lessonId, page = 1) {
  const { data, meta } = unwrap(await client.get(`${quizBase(slug, lessonId)}/attempts`, { params: { page } }))
  return { data, meta }
}

export async function getQuizAttempt(attemptId) {
  return unwrap(await client.get(`/quiz-attempts/${attemptId}`)).data
}
