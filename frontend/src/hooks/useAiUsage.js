import { useQuery, useQueryClient } from '@tanstack/react-query'
import { getAiUsage } from '@/api/ai'

const KEY = ['ai', 'usage']

/** Whether AI is configured on the server and how many requests remain today. */
export function useAiUsage() {
  const queryClient = useQueryClient()
  const query = useQuery({ queryKey: KEY, queryFn: getAiUsage, staleTime: 30 * 1000 })

  return {
    ...query,
    usage: query.data,
    configured: query.data?.configured ?? true,
    /** Replace the cached usage (e.g. from an assistant response) or refetch it. */
    refresh: (usage) => (usage ? queryClient.setQueryData(KEY, usage) : queryClient.invalidateQueries({ queryKey: KEY })),
  }
}
