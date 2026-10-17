import Avatar from '@/components/ui/Avatar'
import Badge from '@/components/ui/Badge'
import { useAuth } from '@/hooks/useAuth'

function greeting() {
  const hour = new Date().getHours()
  if (hour < 12) return 'Good morning'
  if (hour < 18) return 'Good afternoon'
  return 'Good evening'
}

/** Personal greeting shown at the top of each role dashboard. */
export default function WelcomeHeader({ subtitle }) {
  const { user } = useAuth()

  return (
    <div className="flex flex-col gap-4 rounded-2xl bg-gradient-to-br from-brand-600 to-brand-800 p-6 text-white sm:flex-row sm:items-center">
      <Avatar src={user.avatar_url} name={user.name} size="lg" className="ring-4 ring-white/20" />
      <div>
        <div className="flex flex-wrap items-center gap-2">
          <h1 className="text-xl font-bold sm:text-2xl">
            {greeting()}, {user.name.split(' ')[0]}
          </h1>
          <Badge className="bg-white/15 text-white dark:bg-white/15 dark:text-white">{user.role_label}</Badge>
        </div>
        {subtitle && <p className="mt-1 text-sm text-brand-100">{subtitle}</p>}
      </div>
    </div>
  )
}
