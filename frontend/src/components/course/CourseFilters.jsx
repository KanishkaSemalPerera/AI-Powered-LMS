import { getCategoryIcon } from '@/utils/categoryIcons'
import { cn } from '@/utils/cn'
import { LEVEL_OPTIONS, PRICE_OPTIONS, RATING_OPTIONS } from '@/utils/courseOptions'

function RadioGroup({ legend, name, options, value, onChange }) {
  return (
    <fieldset>
      <legend className="mb-2 text-sm font-semibold">{legend}</legend>
      <div className="space-y-1">
        {options.map((option) => {
          const checked = (value ?? '') === option.value
          const Icon = option.icon
          return (
            <label
              key={option.value || 'any'}
              className={cn(
                'flex cursor-pointer items-center gap-2 rounded-lg px-2 py-1.5 text-sm transition-colors',
                'has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-brand-500',
                checked
                  ? 'bg-brand-50 font-medium text-brand-700 dark:bg-brand-950 dark:text-brand-300'
                  : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800',
              )}
            >
              <input
                type="radio"
                name={name}
                value={option.value}
                checked={checked}
                onChange={() => onChange(option.value)}
                className="sr-only"
              />
              {Icon && <Icon className="size-4 shrink-0" aria-hidden="true" />}
              <span className="flex-1">{option.label}</span>
              {option.count !== undefined && (
                <span className="text-xs text-slate-400">{option.count}</span>
              )}
            </label>
          )
        })}
      </div>
    </fieldset>
  )
}

/**
 * Sidebar filters. `filters` holds the current URL values; `onChange(key, value)`
 * updates one filter.
 */
export default function CourseFilters({ filters, categories = [], onChange }) {
  const categoryOptions = [
    { value: '', label: 'All categories' },
    ...categories.map((category) => ({
      value: category.slug,
      label: category.name,
      icon: getCategoryIcon(category.icon),
      count: category.courses_count,
    })),
  ]

  return (
    <div className="space-y-6">
      <RadioGroup
        legend="Category"
        name="category"
        options={categoryOptions}
        value={filters.category}
        onChange={(value) => onChange('category', value)}
      />
      <RadioGroup
        legend="Level"
        name="level"
        options={[{ value: '', label: 'All levels' }, ...LEVEL_OPTIONS.filter((o) => o.value !== 'all_levels')]}
        value={filters.level}
        onChange={(value) => onChange('level', value)}
      />
      <RadioGroup legend="Price" name="price" options={PRICE_OPTIONS} value={filters.price} onChange={(value) => onChange('price', value)} />
      <RadioGroup
        legend="Rating"
        name="min_rating"
        options={RATING_OPTIONS}
        value={filters.min_rating}
        onChange={(value) => onChange('min_rating', value)}
      />
    </div>
  )
}
