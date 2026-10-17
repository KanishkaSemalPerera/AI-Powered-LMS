import { Suspense } from 'react'
import { NavLink, Outlet } from 'react-router'
import Navbar from '@/components/layout/Navbar'
import { sidebarNavigation } from '@/components/layout/navigation'
import PageLoader from '@/components/ui/PageLoader'
import { useAuth } from '@/hooks/useAuth'
import { cn } from '@/utils/cn'

const sideLinkClass = ({ isActive }) =>
  cn(
    'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
    isActive
      ? 'bg-brand-50 text-brand-700 dark:bg-brand-950 dark:text-brand-300'
      : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white',
  )

const tabLinkClass = ({ isActive }) =>
  cn(
    'flex shrink-0 items-center gap-2 rounded-full px-3 py-1.5 text-sm font-medium',
    isActive
      ? 'bg-brand-600 text-white'
      : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200',
  )

/** Signed-in area: navbar, role-based sidebar (tabs on mobile) and page content. */
export default function DashboardLayout() {
  const { user } = useAuth()
  const items = sidebarNavigation[user.role] ?? []

  return (
    <div className="flex min-h-screen flex-col">
      <Navbar />

      {/* Mobile: horizontally scrollable tabs */}
      <nav
        aria-label="Section"
        className="flex gap-2 overflow-x-auto border-b border-slate-200 px-4 py-3 md:hidden dark:border-slate-800"
      >
        {items.map(({ to, label, icon: Icon, end }) => (
          <NavLink key={to} to={to} end={end} className={tabLinkClass}>
            <Icon className="size-4" aria-hidden="true" />
            {label}
          </NavLink>
        ))}
      </nav>

      <div className="mx-auto flex w-full max-w-7xl flex-1 gap-8 px-4 py-6 sm:px-6 md:py-8 lg:px-8">
        <aside className="hidden w-56 shrink-0 md:block">
          <nav aria-label="Section" className="sticky top-24 space-y-1">
            {items.map(({ to, label, icon: Icon, end }) => (
              <NavLink key={to} to={to} end={end} className={sideLinkClass}>
                <Icon className="size-4" aria-hidden="true" />
                {label}
              </NavLink>
            ))}
          </nav>
        </aside>

        <main id="main-content" className="min-w-0 flex-1">
          <Suspense fallback={<PageLoader />}>
            <Outlet />
          </Suspense>
        </main>
      </div>
    </div>
  )
}
