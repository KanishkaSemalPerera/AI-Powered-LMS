import { keepPreviousData, useQuery } from '@tanstack/react-query'
import { Receipt } from 'lucide-react'
import { useState } from 'react'
import { Link } from 'react-router'
import { getPayments } from '@/api/enrollment'
import Badge from '@/components/ui/Badge'
import { buttonClasses } from '@/components/ui/buttonStyles'
import EmptyState from '@/components/ui/EmptyState'
import ErrorState from '@/components/ui/ErrorState'
import Pagination from '@/components/ui/Pagination'
import Skeleton from '@/components/ui/Skeleton'
import { useDocumentTitle } from '@/hooks/useDocumentTitle'
import { formatDate, formatPrice } from '@/utils/format'

const STATUS = {
  paid: ['success', 'Paid'],
  refunded: ['neutral', 'Refunded'],
  failed: ['danger', 'Failed'],
  expired: ['warning', 'Expired'],
  pending: ['warning', 'Pending'],
}

export default function PaymentsPage() {
  useDocumentTitle('Payment history')
  const [page, setPage] = useState(1)
  const query = useQuery({
    queryKey: ['payments', page],
    queryFn: () => getPayments({ page }),
    placeholderData: keepPreviousData,
  })

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold tracking-tight">Payment history</h1>
        <p className="text-sm text-slate-500 dark:text-slate-400">Your course purchases. Card payments are processed securely by Stripe.</p>
      </div>

      {query.isPending ? (
        <Skeleton className="h-64 w-full" />
      ) : query.error ? (
        <ErrorState title="Couldn't load payments" error={query.error} onRetry={() => query.refetch()} />
      ) : query.data.data.length === 0 ? (
        <EmptyState
          icon={Receipt}
          title="No payments yet"
          description="Purchases of paid courses will appear here."
          action={
            <Link to="/courses?price=paid" className={buttonClasses({ variant: 'outline' })}>
              Browse paid courses
            </Link>
          }
        />
      ) : (
        <div className="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800">
          <table className="w-full min-w-[36rem] text-left text-sm">
            <caption className="sr-only">Your payments</caption>
            <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-900 dark:text-slate-400">
              <tr>
                <th scope="col" className="px-4 py-3 font-medium">Date</th>
                <th scope="col" className="px-4 py-3 font-medium">Course</th>
                <th scope="col" className="px-4 py-3 font-medium">Amount</th>
                <th scope="col" className="px-4 py-3 font-medium">Status</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-200 bg-white dark:divide-slate-800 dark:bg-slate-950">
              {query.data.data.map((payment) => {
                const [variant, label] = STATUS[payment.status] ?? ['neutral', payment.status]
                return (
                  <tr key={payment.id}>
                    <td className="whitespace-nowrap px-4 py-3 text-slate-600 dark:text-slate-300">
                      {formatDate(payment.paid_at ?? payment.created_at)}
                    </td>
                    <td className="px-4 py-3 font-medium">
                      {payment.course ? (
                        <Link to={`/courses/${payment.course.slug}`} className="hover:underline">
                          {payment.course.title}
                        </Link>
                      ) : (
                        '—'
                      )}
                    </td>
                    <td className="whitespace-nowrap px-4 py-3">{formatPrice(payment.amount, payment.currency)}</td>
                    <td className="px-4 py-3">
                      <Badge variant={variant}>{label}</Badge>
                    </td>
                  </tr>
                )
              })}
            </tbody>
          </table>
        </div>
      )}

      <Pagination meta={query.data?.meta} onPageChange={setPage} />
    </div>
  )
}
