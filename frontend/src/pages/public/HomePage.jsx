import { useQuery } from '@tanstack/react-query'
import { Award, Bot, Brain, ChartColumn, CircleAlert, CircleCheck, Database, Server, Sparkles } from 'lucide-react'
import { getHealth } from '@/api/system'
import Button from '@/components/ui/Button'
import Skeleton from '@/components/ui/Skeleton'
import { useDocumentTitle } from '@/hooks/useDocumentTitle'
import { getErrorMessage } from '@/utils/apiError'
import { cn } from '@/utils/cn'

const features = [
  {
    icon: Bot,
    title: 'AI study assistant',
    text: 'Ask questions about any lesson and get answers grounded in the lesson content.',
  },
  {
    icon: Brain,
    title: 'AI quiz generator',
    text: 'Instructors turn lesson content into editable multiple-choice quizzes in seconds.',
  },
  {
    icon: ChartColumn,
    title: 'Progress tracking',
    text: 'Pick up where you left off and see your completion for every course.',
  },
  {
    icon: Award,
    title: 'Verified certificates',
    text: 'Finish a course to earn a PDF certificate with a public verification code.',
  },
]

function StatusRow({ icon: Icon, label, ok, value }) {
  return (
    <div className="flex items-center justify-between gap-4 py-2">
      <span className="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
        <Icon className="size-4" aria-hidden="true" />
        {label}
      </span>
      <span
        className={cn(
          'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium',
          ok
            ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300'
            : 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
        )}
      >
        {ok ? <CircleCheck className="size-3.5" aria-hidden="true" /> : <CircleAlert className="size-3.5" aria-hidden="true" />}
        {value}
      </span>
    </div>
  )
}

/** Shows live API/database status — confirms the frontend <-> backend wiring works. */
function SystemStatus() {
  const { data, error, isPending, refetch, isFetching } = useQuery({
    queryKey: ['health'],
    queryFn: getHealth,
  })

  // A 503 still carries the status payload in `data`.
  const status = data ?? error?.response?.data?.data

  return (
    <section
      aria-labelledby="status-heading"
      className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900"
    >
      <div className="mb-3 flex items-center justify-between">
        <h2 id="status-heading" className="font-semibold">
          Platform status
        </h2>
        <Button variant="ghost" size="sm" onClick={() => refetch()} loading={isFetching}>
          Refresh
        </Button>
      </div>

      {isPending ? (
        <div className="space-y-3">
          <Skeleton className="h-6 w-full" />
          <Skeleton className="h-6 w-full" />
          <Skeleton className="h-6 w-full" />
        </div>
      ) : (
        <div className="divide-y divide-slate-100 dark:divide-slate-800">
          <StatusRow icon={Server} label="API" ok={Boolean(status)} value={status ? 'Online' : 'Offline'} />
          <StatusRow
            icon={Database}
            label="Database"
            ok={status?.database === 'connected'}
            value={status?.database ?? 'Unknown'}
          />
          <StatusRow
            icon={Sparkles}
            label="Claude AI"
            ok={Boolean(status?.ai_configured)}
            value={status?.ai_configured ? 'Configured' : 'No API key'}
          />
          {error && !status && (
            <p className="pt-3 text-sm text-red-600 dark:text-red-400">{getErrorMessage(error)}</p>
          )}
        </div>
      )}
    </section>
  )
}

export default function HomePage() {
  useDocumentTitle('Learn anything, smarter')

  return (
    <>
      <section className="relative overflow-hidden">
        <div
          aria-hidden="true"
          className="absolute inset-x-0 -top-40 -z-10 h-96 bg-gradient-to-b from-brand-100 to-transparent blur-3xl dark:from-brand-950"
        />
        <div className="mx-auto grid max-w-7xl gap-12 px-4 py-16 sm:px-6 md:py-24 lg:grid-cols-[1.4fr_1fr] lg:items-center lg:px-8">
          <div>
            <span className="inline-flex items-center gap-2 rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700 dark:bg-brand-950 dark:text-brand-300">
              <Sparkles className="size-3.5" aria-hidden="true" />
              Powered by Claude AI
            </span>
            <h1 className="mt-5 text-4xl font-extrabold tracking-tight text-balance sm:text-5xl lg:text-6xl">
              Learn new skills with a study partner that never sleeps
            </h1>
            <p className="mt-5 max-w-2xl text-lg text-slate-600 dark:text-slate-300">
              Expert-led courses, AI-generated practice quizzes, a lesson-aware study assistant and verified
              certificates — all in one place.
            </p>
          </div>
          <SystemStatus />
        </div>
      </section>

      <section className="mx-auto max-w-7xl px-4 pb-20 sm:px-6 lg:px-8" aria-labelledby="features-heading">
        <h2 id="features-heading" className="text-2xl font-bold tracking-tight">
          Everything you need to learn and teach
        </h2>
        <div className="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
          {features.map(({ icon: Icon, title, text }) => (
            <div
              key={title}
              className="rounded-2xl border border-slate-200 p-6 transition-shadow hover:shadow-md dark:border-slate-800"
            >
              <span className="grid size-10 place-items-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-300">
                <Icon className="size-5" aria-hidden="true" />
              </span>
              <h3 className="mt-4 font-semibold">{title}</h3>
              <p className="mt-2 text-sm text-slate-600 dark:text-slate-400">{text}</p>
            </div>
          ))}
        </div>
      </section>
    </>
  )
}
