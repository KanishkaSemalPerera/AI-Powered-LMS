import AccountSummary from '@/components/layout/AccountSummary'
import WelcomeHeader from '@/components/layout/WelcomeHeader'
import { useDocumentTitle } from '@/hooks/useDocumentTitle'

export default function StudentDashboardPage() {
  useDocumentTitle('My dashboard')

  return (
    <div className="space-y-6">
      <WelcomeHeader subtitle="Pick up where you left off and keep your learning streak going." />
      <AccountSummary />
    </div>
  )
}
