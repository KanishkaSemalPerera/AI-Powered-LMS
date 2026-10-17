// Central place for in-app URLs that several pages link to.

export const coursePath = (course) => `/courses/${course.slug}`

/** Where an enrolled student continues a course. */
export const continueLearningPath = () => '/my-courses'
