import { createElement } from 'react'
import { getCategoryIcon } from '@/utils/categoryIcons'

/** Renders the lucide icon stored on a category (e.g. "code"), falling back to a folder. */
export default function CategoryIcon({ name, ...props }) {
  return createElement(getCategoryIcon(name), props)
}
