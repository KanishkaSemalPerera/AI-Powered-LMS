import { GraduationCap } from 'lucide-react'
import { Link } from 'react-router'

const APP_NAME = import.meta.env.VITE_APP_NAME || 'LearnHub'

export default function Logo() {
  return (
    <Link to="/" className="flex items-center gap-2 rounded-md font-bold tracking-tight">
      <span className="grid size-8 place-items-center rounded-lg bg-brand-600 text-white">
        <GraduationCap className="size-5" aria-hidden="true" />
      </span>
      <span className="text-lg">{APP_NAME}</span>
    </Link>
  )
}
