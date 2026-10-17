import { useEffect } from 'react'

const APP_NAME = import.meta.env.VITE_APP_NAME || 'LearnHub'

/** Sets the browser tab title, e.g. "Courses · LearnHub". */
export function useDocumentTitle(title) {
  useEffect(() => {
    document.title = title ? `${title} · ${APP_NAME}` : APP_NAME
  }, [title])
}
