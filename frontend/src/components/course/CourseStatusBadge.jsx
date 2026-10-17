import Badge from '@/components/ui/Badge'

const variants = {
  draft: 'neutral',
  pending: 'warning',
  published: 'success',
  rejected: 'danger',
}

const labels = {
  draft: 'Draft',
  pending: 'Pending review',
  published: 'Published',
  rejected: 'Rejected',
}

export default function CourseStatusBadge({ status }) {
  return <Badge variant={variants[status] ?? 'neutral'}>{labels[status] ?? status}</Badge>
}
