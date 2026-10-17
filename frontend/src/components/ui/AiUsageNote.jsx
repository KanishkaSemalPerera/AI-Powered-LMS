import { Sparkles } from 'lucide-react'
import Alert from '@/components/ui/Alert'
import { cn } from '@/utils/cn'

/** "AI not configured" warning, or a small "N AI requests left today" line. */
export default function AiUsageNote({ usage, configured, className }) {
  if (!configured) {
    return (
      <Alert variant="warning" title="AI isn’t set up yet" className={className}>
        Add an Anthropic API key (ANTHROPIC_API_KEY) to the backend .env file to enable AI features.
      </Alert>
    )
  }
  if (!usage) return null

  return (
    <p className={cn('inline-flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400', className)}>
      <Sparkles className="size-3.5 text-brand-500" aria-hidden="true" />
      {usage.remaining_today} of {usage.daily_limit} AI requests left today
    </p>
  )
}
