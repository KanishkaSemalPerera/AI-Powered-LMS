// Central place for in-app URLs that several pages link to.

export const coursePath = (course) => `/courses/${course.slug}`

/** Course player; without a lesson id it resumes where the student left off. */
export const learnPath = (course, lessonId) =>
  lessonId ? `/learn/${course.slug}/lessons/${lessonId}` : `/learn/${course.slug}`

/** Where an enrolled student continues a course. */
export const continueLearningPath = (course) => learnPath(course)
