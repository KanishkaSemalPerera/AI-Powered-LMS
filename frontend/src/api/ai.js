import client, { unwrap } from './client'

// All AI calls go to our Laravel API; the Claude API key never reaches the browser.

/** → { configured, used_today, daily_limit, remaining_today } */
export async function getAiUsage() {
  return unwrap(await client.get('/ai/usage')).data
}

/** payload: { num_questions, difficulty, source_text?, replace? } → { data: draft quiz, message } */
export async function generateQuiz(lessonId, payload) {
  // Generation can take a while (the model thinks before writing the questions).
  return unwrap(await client.post(`/instructor/lessons/${lessonId}/ai/quiz`, payload, { timeout: 180000 }))
}

/** payload: { title, subtitle?, category?, level?, notes?, tone?, course_id? } → { subtitle, description, what_you_will_learn } */
export async function suggestCourseDescription(payload) {
  return unwrap(await client.post('/instructor/ai/course-description', payload, { timeout: 120000 })).data
}

/** history: [{ role: 'user'|'assistant', content }] → { answer, usage } */
export async function askAssistant(slug, lessonId, question, history) {
  const url = `/learn/${encodeURIComponent(slug)}/lessons/${lessonId}/assistant`
  return unwrap(await client.post(url, { question, history }, { timeout: 120000 })).data
}
