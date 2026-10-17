import {
  BookOpen,
  Brain,
  Briefcase,
  Calculator,
  Camera,
  Code,
  Database,
  Folder,
  Globe,
  HeartPulse,
  Languages,
  Megaphone,
  Music,
  Palette,
  Sparkles,
} from 'lucide-react'

/** Icon names accepted by the API (SaveCategoryRequest::ICONS) → lucide components. */
export const CATEGORY_ICONS = {
  code: Code,
  database: Database,
  brain: Brain,
  palette: Palette,
  briefcase: Briefcase,
  megaphone: Megaphone,
  sparkles: Sparkles,
  camera: Camera,
  music: Music,
  'heart-pulse': HeartPulse,
  calculator: Calculator,
  languages: Languages,
  'book-open': BookOpen,
  globe: Globe,
}

export function getCategoryIcon(name) {
  return CATEGORY_ICONS[name] ?? Folder
}
