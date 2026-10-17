import { UserRound } from 'lucide-react'
import { Link } from 'react-router'
import { buttonClasses } from '@/components/ui/buttonStyles'
import Card, { CardBody, CardHeader } from '@/components/ui/Card'
import { useAuth } from '@/hooks/useAuth'

const dateFormat = new Intl.DateTimeFormat(undefined, { dateStyle: 'medium' })

/** Account facts plus a shortcut to the profile page; used on every dashboard. */
export default function AccountSummary() {
  const { user } = useAuth()

  const rows = [
    ['Email', user.email],
    ['Member since', user.created_at ? dateFormat.format(new Date(user.created_at)) : '—'],
    ['Headline', user.headline || 'Not set'],
  ]

  return (
    <Card aria-labelledby="account-heading">
      <CardHeader
        id="account-heading"
        title="Your account"
        action={
          <Link to="/profile" className={buttonClasses({ variant: 'outline', size: 'sm' })}>
            <UserRound className="size-4" aria-hidden="true" />
            Edit profile
          </Link>
        }
      />
      <CardBody>
        <dl className="grid gap-4 sm:grid-cols-3">
          {rows.map(([label, value]) => (
            <div key={label}>
              <dt className="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">{label}</dt>
              <dd className="mt-1 truncate text-sm font-medium">{value}</dd>
            </div>
          ))}
        </dl>
      </CardBody>
    </Card>
  )
}
