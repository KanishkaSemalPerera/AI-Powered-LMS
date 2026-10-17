import { useQuery } from '@tanstack/react-query'
import {
  ArrowRight,
  Award,
  Bot,
  Brain,
  ChartColumn,
  CircleAlert,
  CircleCheck,
  Database,
  Search,
  Server,
  Sparkles,
} from 'lucide-react'
import { useState } from 'react'
import { Link, useNavigate } from 'react-router'
import { getCategories, getCourses } from '@/api/courses'
import { getHealth } from '@/api/system'
import CourseGrid from '@/components/course/CourseGrid'
import Button from '@/components/ui/Button'
import { buttonClasses } from '@/components/ui/buttonStyles'
import Input from '@/components/ui/Input'
import Skeleton from '@/components/ui/Skeleton'
import { useAuth } from '@/hooks/useAuth'
import { useDocumentTitle } from '@/hooks/useDocumentTitle'
import { getErrorMessage } from '@/utils/apiError'
import { getCategoryIcon } from '@/utils/categoryIcons'
import { cn } from '@/utils/cn'
import { plural } from '@/utils/format'
import { dashboardPath } from '@/utils/roles'

const features = [
  { icon: Bot, title: 'AI study assistant', text: 'Ask questions about any lesson and get answers grounded in the lesson content.' },
  { icon: Brain, title: 'AI quiz generator', text: 'Instructors turn lesson content into editable multiple-choice quizzes in seconds.' },
  { icon: ChartColumn, title: 'Progress tracking', text: 'Pick up where you left off and see your completion for every course.' },
  { icon: Award, title: 'Verified certificates', text: 'Finish a course to earn a PDF certificate with a public verification code.' },
]

// ------------------------------------------------------------------ Hero

function HeroSearch() {
  const navigate = useNavigate()
  const [q, setQ] = useState('')

  return (
    <form
      role="search"
      onSubmit={(event) => {
        event.preventDefault()
        navigate(q.trim() ? `/courses?q=${encodeURIComponent(q.trim())}` : '/courses')
      }}
      className="mt-8 flex max-w-xl gap-2"
    >
      <div className="relative flex-1">
        <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" aria-hidden="true" />
        <Input
          type="search"
          value={q}
          onChange={(event) => setQ(event.target.value)}
          placeholder="What do you want to learn?"
          aria-label="Search courses"
          className="h-12 pl-9 text-base"
        />
      </div>
      <Button type="submit" size="lg">
        Search
      </Button>
    </form>
  )
}

function HeroActions() {
  const { user } = useAuth()

  return (
    <div className="mt-6 flex flex-wrap items-center gap-x-6 gap-y-3 text-sm">
      <Link to="/courses" className="inline-flex items-center gap-1 font-semibold text-brand-600 hover:underline dark:text-brand-400">
        Browse all courses
        <ArrowRight className="size-4" aria-hidden="true" />
      </Link>
      {user ? (
        <Link to={dashboardPath(user.role)} className="font-medium text-slate-600 hover:underline dark:text-slate-300">
          Go to my dashboard
        </Link>
      ) : (
        <Link to="/register" className="font-medium text-slate-600 hover:underline dark:text-slate-300">
          Create a free account
        </Link>
      )}
    </div>
  )
}

/** Decorative preview of the AI study assistant (static content). */
function AssistantPreview() {
  return (
    <div aria-hidden="true" className="relative mx-auto w-full max-w-md">
      <div className="absolute -inset-4 -z-10 rounded-3xl bg-gradient-to-br from-brand-200 to-violet-200 opacity-60 blur-2xl dark:from-brand-900 dark:to-violet-900" />
      <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-xl dark:border-slate-800 dark:bg-slate-900">
        <div className="flex items-center gap-2 border-b border-slate-100 pb-3 dark:border-slate-800">
          <span className="grid size-8 place-items-center rounded-lg bg-brand-600 text-white">
            <Sparkles className="size-4" />
          </span>
          <div>
            <p className="text-sm font-semibold">Study assistant</p>
            <p className="text-xs text-slate-500">Lesson: State with useState</p>
          </div>
        </div>
        <div className="space-y-3 pt-4 text-sm">
          <p className="ml-auto w-fit max-w-[85%] rounded-2xl rounded-br-sm bg-brand-600 px-3 py-2 text-white">
            Why should I use setCount(c =&gt; c + 1)?
          </p>
          <p className="w-fit max-w-[90%] rounded-2xl rounded-bl-sm bg-slate-100 px-3 py-2 dark:bg-slate-800">
            The updater form always receives the latest state, so several updates in a row can&apos;t overwrite each other.
          </p>
        </div>
      </div>
    </div>
  )
}

// ------------------------------------------------------------------ Sections

function FeaturedCourses() {
  const query = useQuery({
    queryKey: ['courses', { featured: 1, sort: 'popular', per_page: 6 }],
    queryFn: () => getCourses({ featured: 1, sort: 'popular', per_page: 6 }),
  })

  return (
    <section className="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8" aria-labelledby="featured-heading">
      <div className="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
          <h2 id="featured-heading" className="text-2xl font-bold tracking-tight sm:text-3xl">
            Featured courses
          </h2>
          <p className="mt-1 text-slate-600 dark:text-slate-400">Hand-picked courses our learners love.</p>
        </div>
        <Link to="/courses" className={buttonClasses({ variant: 'outline' })}>
          View all courses
        </Link>
      </div>
      <CourseGrid
        courses={query.data?.data}
        isLoading={query.isPending}
        error={query.error}
        onRetry={() => query.refetch()}
        skeletonCount={3}
        emptyTitle="No featured courses yet"
        emptyDescription="Check back soon — new courses are being added."
      />
    </section>
  )
}

function CategoryGrid() {
  const { data: categories, isPending } = useQuery({
    queryKey: ['categories'],
    queryFn: getCategories,
    staleTime: 5 * 60 * 1000,
  })

  return (
    <section className="bg-slate-50 py-16 dark:bg-slate-900/50" aria-labelledby="categories-heading">
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <h2 id="categories-heading" className="text-2xl font-bold tracking-tight sm:text-3xl">
          Explore categories
        </h2>
        <div className="mt-8 grid grid-cols-2 gap-4 md:grid-cols-4">
          {isPending
            ? Array.from({ length: 8 }, (_, i) => <Skeleton key={i} className="h-24" />)
            : categories?.map((category) => {
                const Icon = getCategoryIcon(category.icon)
                return (
                  <Link
                    key={category.id}
                    to={`/courses?category=${category.slug}`}
                    className="group flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4 transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-md dark:border-slate-800 dark:bg-slate-900 dark:hover:border-brand-700"
                  >
                    <span className="grid size-11 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-600 group-hover:bg-brand-600 group-hover:text-white dark:bg-brand-950 dark:text-brand-300">
                      <Icon className="size-5" aria-hidden="true" />
                    </span>
                    <span className="min-w-0">
                      <span className="block truncate font-semibold">{category.name}</span>
                      <span className="text-xs text-slate-500 dark:text-slate-400">{plural(category.courses_count ?? 0, 'course')}</span>
                    </span>
                  </Link>
                )
              })}
        </div>
      </div>
    </section>
  )
}

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

/** Development-only API/database status, to confirm the frontend <-> backend wiring. */
function SystemStatus() {
  const { data, error, isPending, refetch, isFetching } = useQuery({ queryKey: ['health'], queryFn: getHealth })
  const status = data ?? error?.response?.data?.data // a 503 still carries the status payload

  return (
    <section aria-labelledby="status-heading" className="mx-auto max-w-md rounded-2xl border border-dashed border-slate-300 p-5 dark:border-slate-700">
      <div className="mb-2 flex items-center justify-between">
        <h2 id="status-heading" className="text-sm font-semibold">
          Platform status <span className="font-normal text-slate-500">(development only)</span>
        </h2>
        <Button variant="ghost" size="sm" onClick={() => refetch()} loading={isFetching}>
          Refresh
        </Button>
      </div>
      {isPending ? (
        <Skeleton className="h-24 w-full" />
      ) : (
        <div className="divide-y divide-slate-100 dark:divide-slate-800">
          <StatusRow icon={Server} label="API" ok={Boolean(status)} value={status ? 'Online' : 'Offline'} />
          <StatusRow icon={Database} label="Database" ok={status?.database === 'connected'} value={status?.database ?? 'Unknown'} />
          <StatusRow
            icon={Sparkles}
            label="Claude AI"
            ok={Boolean(status?.ai_configured)}
            value={status?.ai_configured ? 'Configured' : 'No API key'}
          />
          {error && !status && <p className="pt-3 text-sm text-red-600 dark:text-red-400">{getErrorMessage(error)}</p>}
        </div>
      )}
    </section>
  )
}

// ------------------------------------------------------------------ Page

export default function HomePage() {
  useDocumentTitle('Learn anything, smarter')

  return (
    <>
      <section className="relative overflow-hidden">
        <div
          aria-hidden="true"
          className="absolute inset-x-0 -top-40 -z-10 h-96 bg-gradient-to-b from-brand-100 to-transparent blur-3xl dark:from-brand-950"
        />
        <div className="mx-auto grid max-w-7xl gap-12 px-4 py-16 sm:px-6 md:py-24 lg:grid-cols-[1.3fr_1fr] lg:items-center lg:px-8">
          <div>
            <span className="inline-flex items-center gap-2 rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700 dark:bg-brand-950 dark:text-brand-300">
              <Sparkles className="size-3.5" aria-hidden="true" />
              Powered by Claude AI
            </span>
            <h1 className="mt-5 text-4xl font-extrabold tracking-tight text-balance sm:text-5xl lg:text-6xl">
              Learn new skills with a study partner that never sleeps
            </h1>
            <p className="mt-5 max-w-2xl text-lg text-slate-600 dark:text-slate-300">
              Expert-led courses, AI-generated practice quizzes, a lesson-aware study assistant and verified certificates — all
              in one place.
            </p>
            <HeroSearch />
            <HeroActions />
          </div>
          <AssistantPreview />
        </div>
      </section>

      <FeaturedCourses />
      <CategoryGrid />

      <section className="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8" aria-labelledby="features-heading">
        <h2 id="features-heading" className="text-2xl font-bold tracking-tight sm:text-3xl">
          Everything you need to learn and teach
        </h2>
        <div className="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
          {features.map(({ icon: Icon, title, text }) => (
            <div key={title} className="rounded-2xl border border-slate-200 p-6 dark:border-slate-800">
              <span className="grid size-10 place-items-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-300">
                <Icon className="size-5" aria-hidden="true" />
              </span>
              <h3 className="mt-4 font-semibold">{title}</h3>
              <p className="mt-2 text-sm text-slate-600 dark:text-slate-400">{text}</p>
            </div>
          ))}
        </div>
      </section>

      {import.meta.env.DEV && (
        <div className="px-4 pb-16">
          <SystemStatus />
        </div>
      )}
    </>
  )
}
