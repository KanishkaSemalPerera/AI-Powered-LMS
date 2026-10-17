import { keepPreviousData, useQuery } from '@tanstack/react-query'
import { Search, SlidersHorizontal, X } from 'lucide-react'
import { useEffect, useState } from 'react'
import { useSearchParams } from 'react-router'
import { getCategories, getCourses } from '@/api/courses'
import CourseFilters from '@/components/course/CourseFilters'
import CourseGrid from '@/components/course/CourseGrid'
import Button from '@/components/ui/Button'
import Input from '@/components/ui/Input'
import Pagination from '@/components/ui/Pagination'
import Select from '@/components/ui/Select'
import { useDebouncedValue } from '@/hooks/useDebouncedValue'
import { useDocumentTitle } from '@/hooks/useDocumentTitle'
import { SORT_OPTIONS } from '@/utils/courseOptions'
import { plural } from '@/utils/format'

const FILTER_KEYS = ['q', 'category', 'level', 'price', 'min_rating', 'sort', 'page']
const PER_PAGE = 12

/** Course catalogue. All filters live in the URL so results are shareable and survive refresh. */
export default function CoursesPage() {
  useDocumentTitle('Browse courses')
  const [searchParams, setSearchParams] = useSearchParams()
  const [showFilters, setShowFilters] = useState(false)

  const filters = Object.fromEntries(FILTER_KEYS.map((key) => [key, searchParams.get(key) ?? '']))

  /** Update filters in the URL; any change other than paging returns to page 1. */
  const updateParams = (changes) => {
    setSearchParams(
      (current) => {
        const next = new URLSearchParams(current)
        Object.entries(changes).forEach(([key, value]) => (value ? next.set(key, value) : next.delete(key)))
        if (!('page' in changes)) next.delete('page')
        return next
      },
      { replace: !('page' in changes) },
    )
  }

  // Debounced search box → URL. Reads the current URL inside the updater, so the
  // effect depends only on the debounced text.
  const [search, setSearch] = useState(filters.q)
  const debouncedSearch = useDebouncedValue(search.trim(), 400)
  useEffect(() => {
    setSearchParams(
      (current) => {
        if ((current.get('q') ?? '') === debouncedSearch) return current
        const next = new URLSearchParams(current)
        if (debouncedSearch) next.set('q', debouncedSearch)
        else next.delete('q')
        next.delete('page')
        return next
      },
      { replace: true },
    )
  }, [debouncedSearch, setSearchParams])

  const categoriesQuery = useQuery({ queryKey: ['categories'], queryFn: getCategories, staleTime: 5 * 60 * 1000 })

  const coursesQuery = useQuery({
    queryKey: ['courses', filters],
    queryFn: () => getCourses({ ...filters, per_page: PER_PAGE }),
    placeholderData: keepPreviousData,
  })

  const activeFilterCount = ['category', 'level', 'price', 'min_rating'].filter((key) => filters[key]).length
  const hasAnyFilter = activeFilterCount > 0 || filters.q

  const clearAll = () => {
    setSearch('')
    setSearchParams({}, { replace: true })
  }

  const changePage = (page) => {
    updateParams({ page: page > 1 ? String(page) : '' })
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }

  const filterPanel = (
    <CourseFilters
      filters={filters}
      categories={categoriesQuery.data}
      onChange={(key, value) => updateParams({ [key]: value })}
    />
  )

  return (
    <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <header className="mb-6">
        <h1 className="text-3xl font-bold tracking-tight">Browse courses</h1>
        <p className="mt-1 text-slate-600 dark:text-slate-400">Find the right course to learn your next skill.</p>
      </header>

      <div className="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center">
        <div className="relative flex-1">
          <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" aria-hidden="true" />
          <Input
            type="search"
            value={search}
            onChange={(event) => setSearch(event.target.value)}
            placeholder="Search courses, e.g. React, Python, design"
            aria-label="Search courses"
            className="pl-9"
          />
        </div>
        <div className="flex gap-2">
          <Button
            variant="outline"
            className="lg:hidden"
            onClick={() => setShowFilters((value) => !value)}
            aria-expanded={showFilters}
            aria-controls="mobile-filters"
          >
            <SlidersHorizontal className="size-4" aria-hidden="true" />
            Filters{activeFilterCount > 0 && ` (${activeFilterCount})`}
          </Button>
          <div className="w-48">
            <Select
              aria-label="Sort courses"
              value={filters.sort || 'newest'}
              onChange={(event) => updateParams({ sort: event.target.value === 'newest' ? '' : event.target.value })}
              options={SORT_OPTIONS}
            />
          </div>
        </div>
      </div>

      {showFilters && (
        <div id="mobile-filters" className="mb-6 rounded-2xl border border-slate-200 p-4 lg:hidden dark:border-slate-800">
          {filterPanel}
        </div>
      )}

      <div className="flex gap-8">
        <aside className="hidden w-60 shrink-0 lg:block" aria-label="Filters">
          <div className="sticky top-24">{filterPanel}</div>
        </aside>

        <section className="min-w-0 flex-1" aria-live="polite" aria-busy={coursesQuery.isFetching}>
          <div className="mb-4 flex min-h-8 flex-wrap items-center justify-between gap-2">
            <p className="text-sm text-slate-600 dark:text-slate-400">
              {coursesQuery.data ? plural(coursesQuery.data.meta.total, 'course') : ' '}
              {filters.q && (
                <>
                  {' '}
                  for <span className="font-semibold text-slate-900 dark:text-white">“{filters.q}”</span>
                </>
              )}
            </p>
            {hasAnyFilter && (
              <Button variant="ghost" size="sm" onClick={clearAll}>
                <X className="size-4" aria-hidden="true" />
                Clear all filters
              </Button>
            )}
          </div>

          <CourseGrid
            courses={coursesQuery.data?.data}
            isLoading={coursesQuery.isPending}
            error={coursesQuery.error}
            onRetry={() => coursesQuery.refetch()}
            emptyTitle="No courses match your search"
            emptyDescription="Try different keywords or remove some filters."
            emptyAction={
              hasAnyFilter ? (
                <Button variant="outline" onClick={clearAll}>
                  Clear all filters
                </Button>
              ) : null
            }
            className={coursesQuery.isPlaceholderData ? 'opacity-60 transition-opacity' : undefined}
          />

          <Pagination meta={coursesQuery.data?.meta} onPageChange={changePage} className="mt-8" />
        </section>
      </div>
    </div>
  )
}
