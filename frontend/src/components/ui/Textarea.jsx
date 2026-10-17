import { cn } from '@/utils/cn'
import { inputClasses } from './Input'

export default function Textarea({ className, invalid, rows = 4, ...props }) {
  return (
    <textarea
      rows={rows}
      aria-invalid={invalid || undefined}
      className={cn(inputClasses, 'resize-y', className)}
      {...props}
    />
  )
}
