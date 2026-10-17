// Display formatters. They use the browser's locale for numbers and dates.

const currencyFormatters = new Map()

/** 29.99 → "$29.99"; 0 → "Free". */
export function formatPrice(amount, currency = 'usd') {
  if (!amount || Number(amount) <= 0) return 'Free'

  const code = currency.toUpperCase()
  if (!currencyFormatters.has(code)) {
    currencyFormatters.set(code, new Intl.NumberFormat(undefined, { style: 'currency', currency: code }))
  }
  return currencyFormatters.get(code).format(Number(amount))
}

/** 4620 → "1h 17m"; 540 → "9m"; 30 → "1m". */
export function formatDuration(seconds = 0) {
  const totalMinutes = Math.max(1, Math.round(Number(seconds) / 60))
  const hours = Math.floor(totalMinutes / 60)
  const minutes = totalMinutes % 60
  if (hours === 0) return `${minutes}m`
  return minutes ? `${hours}h ${minutes}m` : `${hours}h`
}

const numberFormat = new Intl.NumberFormat()
const compactFormat = new Intl.NumberFormat(undefined, { notation: 'compact' })

/** 1234 → "1,234" (or "1.2K" when compact). */
export function formatNumber(value = 0, { compact = false } = {}) {
  return (compact ? compactFormat : numberFormat).format(Number(value))
}

const dateFormat = new Intl.DateTimeFormat(undefined, { dateStyle: 'medium' })

export function formatDate(iso) {
  return iso ? dateFormat.format(new Date(iso)) : '—'
}

/** Singular/plural label: plural(1, 'lesson') → "1 lesson", plural(3, 'lesson') → "3 lessons". */
export function plural(count, word, pluralWord = `${word}s`) {
  return `${formatNumber(count)} ${count === 1 ? word : pluralWord}`
}
