import { useMutation } from '@tanstack/react-query'
import { Bot, Send, Sparkles, X } from 'lucide-react'
import { useEffect, useRef, useState } from 'react'
import { askAssistant } from '@/api/ai'
import Markdown from '@/components/course/Markdown'
import AiUsageNote from '@/components/ui/AiUsageNote'
import Button from '@/components/ui/Button'
import Textarea from '@/components/ui/Textarea'
import { useAiUsage } from '@/hooks/useAiUsage'
import { getErrorMessage } from '@/utils/apiError'
import { cn } from '@/utils/cn'

const SUGGESTIONS = ['Summarise this lesson in 3 bullet points', 'Explain the hardest idea in simpler words', 'Give me a practice question']

/**
 * Chat with the AI study assistant about the current lesson. The backend
 * sends the lesson content to Claude as context; the conversation is kept
 * in this component (one chat per lesson) and is not stored on the server.
 */
export default function StudyAssistant({ slug, lesson }) {
  const [open, setOpen] = useState(false)
  const [messages, setMessages] = useState([]) // { role, content, error? }
  const [draft, setDraft] = useState('')
  const listRef = useRef(null)
  const { usage, configured, refresh } = useAiUsage()

  const ask = useMutation({
    mutationFn: ({ question, history }) => askAssistant(slug, lesson.id, question, history),
    onSuccess: ({ answer, usage: latest }) => {
      setMessages((current) => [...current, { role: 'assistant', content: answer }])
      refresh(latest)
    },
    onError: (error) => {
      setMessages((current) => [...current, { role: 'assistant', content: getErrorMessage(error), error: true }])
      refresh()
    },
  })

  // Keep the newest message in view.
  useEffect(() => {
    listRef.current?.scrollTo({ top: listRef.current.scrollHeight, behavior: 'smooth' })
  }, [messages, ask.isPending])

  const send = (text) => {
    const question = text.trim()
    if (!question || ask.isPending) return
    // Only successful turns are sent back as context.
    const history = messages.filter((m) => !m.error).map(({ role, content }) => ({ role, content }))
    setMessages((current) => [...current, { role: 'user', content: question }])
    setDraft('')
    ask.mutate({ question, history })
  }

  const outOfRequests = usage && usage.remaining_today <= 0

  return (
    <>
      <Button
        onClick={() => setOpen(true)}
        className={cn('fixed bottom-5 right-5 z-30 rounded-full shadow-lg', open && 'hidden')}
        aria-haspopup="dialog"
      >
        <Sparkles className="size-4" aria-hidden="true" />
        Ask AI
      </Button>

      {open && (
        <section
          role="dialog"
          aria-label="AI study assistant"
          className="fixed inset-x-0 bottom-0 z-40 flex h-[85vh] flex-col rounded-t-2xl border border-slate-200 bg-white shadow-2xl sm:inset-x-auto sm:bottom-5 sm:right-5 sm:h-[36rem] sm:w-[26rem] sm:rounded-2xl dark:border-slate-800 dark:bg-slate-900"
        >
          <header className="flex items-center gap-3 border-b border-slate-200 px-4 py-3 dark:border-slate-800">
            <span className="grid size-8 place-items-center rounded-lg bg-brand-600 text-white">
              <Bot className="size-4" aria-hidden="true" />
            </span>
            <div className="min-w-0 flex-1">
              <p className="text-sm font-semibold">Study assistant</p>
              <p className="truncate text-xs text-slate-500">About: {lesson.title}</p>
            </div>
            <Button variant="ghost" size="icon" onClick={() => setOpen(false)} aria-label="Close the study assistant" autoFocus>
              <X className="size-5" aria-hidden="true" />
            </Button>
          </header>

          <div ref={listRef} className="flex-1 space-y-3 overflow-y-auto px-4 py-4" aria-live="polite">
            {!configured && <AiUsageNote configured={false} />}
            {configured && messages.length === 0 && (
              <div className="space-y-3 text-sm">
                <p className="text-slate-600 dark:text-slate-300">
                  Hi! I can explain this lesson, answer questions about it, or quiz you. What would you like to know?
                </p>
                <div className="flex flex-col items-start gap-2">
                  {SUGGESTIONS.map((suggestion) => (
                    <button
                      key={suggestion}
                      type="button"
                      onClick={() => send(suggestion)}
                      disabled={outOfRequests}
                      className="rounded-full border border-brand-200 px-3 py-1.5 text-left text-xs font-medium text-brand-700 hover:bg-brand-50 disabled:opacity-50 dark:border-brand-800 dark:text-brand-300 dark:hover:bg-brand-950"
                    >
                      {suggestion}
                    </button>
                  ))}
                </div>
              </div>
            )}

            {messages.map((message, index) =>
              message.role === 'user' ? (
                <p key={index} className="ml-auto w-fit max-w-[85%] whitespace-pre-wrap rounded-2xl rounded-br-sm bg-brand-600 px-3 py-2 text-sm text-white">
                  {message.content}
                </p>
              ) : (
                <div
                  key={index}
                  className={cn(
                    'max-w-[95%] rounded-2xl rounded-bl-sm px-3 py-2',
                    message.error ? 'bg-red-50 text-sm text-red-700 dark:bg-red-950/40 dark:text-red-300' : 'bg-slate-100 dark:bg-slate-800',
                  )}
                >
                  {message.error ? message.content : <Markdown className="prose-sm">{message.content}</Markdown>}
                </div>
              ),
            )}

            {ask.isPending && (
              <p className="flex items-center gap-2 text-sm text-slate-500" role="status">
                <span className="flex gap-1" aria-hidden="true">
                  <span className="size-1.5 animate-bounce rounded-full bg-slate-400" />
                  <span className="size-1.5 animate-bounce rounded-full bg-slate-400 [animation-delay:150ms]" />
                  <span className="size-1.5 animate-bounce rounded-full bg-slate-400 [animation-delay:300ms]" />
                </span>
                Thinking…
              </p>
            )}
          </div>

          <form
            onSubmit={(event) => {
              event.preventDefault()
              send(draft)
            }}
            className="border-t border-slate-200 p-3 dark:border-slate-800"
          >
            <div className="flex items-end gap-2">
              <Textarea
                rows={2}
                value={draft}
                onChange={(event) => setDraft(event.target.value)}
                onKeyDown={(event) => {
                  if (event.key === 'Enter' && !event.shiftKey) {
                    event.preventDefault()
                    send(draft)
                  }
                }}
                placeholder={outOfRequests ? 'Daily AI limit reached' : 'Ask about this lesson…'}
                aria-label="Your question"
                maxLength={2000}
                disabled={!configured || outOfRequests}
                className="min-h-0 resize-none"
              />
              <Button type="submit" size="icon" aria-label="Send" disabled={!draft.trim() || ask.isPending || !configured || outOfRequests}>
                <Send className="size-4" aria-hidden="true" />
              </Button>
            </div>
            <div className="mt-2 flex items-center justify-between gap-2">
              <p className="text-[11px] text-slate-400">AI can make mistakes. Check important facts.</p>
              {configured && <AiUsageNote usage={usage} configured className="text-[11px]" />}
            </div>
          </form>
        </section>
      )}
    </>
  )
}
