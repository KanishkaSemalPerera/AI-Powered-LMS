import AccountSummary from '@/components/layout/AccountSummary'
import WelcomeHeader from '@/components/layout/WelcomeHeader'
import { useDocumentTitle } from '@/hooks/useDocumentTitle'

export default function AdminDashboardPage() {
  useDocumentTitle('Admin dashboard')

  return (
    <div className="space-y-6">
      <WelcomeHeader subtitle="Oversee users, course approvals and platform revenue." />
      <AccountSummary />
    </div>
  )
}
