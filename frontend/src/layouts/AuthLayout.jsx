import { Award, Bot, Brain } from 'lucide-react'
import { Suspense } from 'react'
import { Outlet } from 'react-router'
import Logo from '@/components/layout/Logo'
import ThemeToggle from '@/components/layout/ThemeToggle'
import PageLoader from '@/components/ui/PageLoader'

const highlights = [
  { icon: Bot, text: 'An AI study assistant that knows every lesson' },
  { icon: Brain, text: 'Practice quizzes with instant explanations' },
  { icon: Award, text: 'Verified certificates when you finish' },
]

/** Split-screen layout for login, register and password pages. */
export default function AuthLayout() {
  return (
    <div className="grid min-h-screen lg:grid-cols-2">
      <div className="flex flex-col px-4 py-6 sm:px-8">
        <div className="flex items-center justify-between">
          <Logo />
          <ThemeToggle />
        </div>
        <main id="main-content" className="flex flex-1 items-center justify-center py-10">
          <div className="w-full max-w-md">
            <Suspense fallback={<PageLoader />}>
              <Outlet />
            </Suspense>
          </div>
        </main>
      </div>

      <aside className="relative hidden overflow-hidden bg-brand-700 lg:flex lg:flex-col lg:justify-center lg:px-16 dark:bg-brand-950">
        <div
          aria-hidden="true"
          className="absolute -right-24 -top-24 size-96 rounded-full bg-brand-500/40 blur-3xl dark:bg-brand-700/40"
        />
        <div className="relative max-w-md text-white">
          <h2 className="text-3xl font-bold tracking-tight">Learn smarter, not longer.</h2>
          <p className="mt-4 text-brand-100">
            Join a community of learners and instructors building real skills with the help of AI.
          </p>
          <ul className="mt-10 space-y-5">
            {highlights.map(({ icon: Icon, text }) => (
              <li key={text} className="flex items-center gap-3">
                <span className="grid size-10 place-items-center rounded-lg bg-white/10">
                  <Icon className="size-5" aria-hidden="true" />
                </span>
                <span className="text-sm font-medium">{text}</span>
              </li>
            ))}
          </ul>
        </div>
      </aside>
    </div>
  )
}
