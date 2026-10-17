import { LoaderCircle } from 'lucide-react'
import { cn } from '@/utils/cn'

export default function Spinner({ className, label = 'Loading' }) {
  return (
    <span role="status" className="inline-flex items-center">
      <LoaderCircle className={cn('size-5 animate-spin text-brand-600', className)} aria-hidden="true" />
      <span className="sr-only">{label}</span>
    </span>
  )
}
