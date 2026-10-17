import AccountSummary from '@/components/layout/AccountSummary'
import WelcomeHeader from '@/components/layout/WelcomeHeader'
import { useDocumentTitle } from '@/hooks/useDocumentTitle'

export default function InstructorDashboardPage() {
  useDocumentTitle('Instructor dashboard')

  return (
    <div className="space-y-6">
      <WelcomeHeader subtitle="Manage your courses, students and earnings from here." />
      <AccountSummary />
    </div>
  )
}
