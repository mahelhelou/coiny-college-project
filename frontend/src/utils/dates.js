const pad = (n) => String(n).padStart(2, '0')

/** Local calendar date as YYYY-MM-DD (what the API expects). */
export function toIsoDate(date = new Date()) {
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`
}

export function tomorrowIso() {
  const date = new Date()
  date.setDate(date.getDate() + 1)
  return toIsoDate(date)
}

/** YYYY-MM-DD → dd/MM/yyyy */
export function formatDate(iso) {
  if (!iso) return ''
  const [y, m, d] = String(iso).slice(0, 10).split('-')
  return `${d}/${m}/${y}`
}

const dayLabel = new Intl.DateTimeFormat('en-GB', { day: 'numeric', month: 'short' })
const monthLabel = new Intl.DateTimeFormat('en-GB', { month: 'short', year: 'numeric' })
const longMonth = new Intl.DateTimeFormat('en-GB', { month: 'long', year: 'numeric' })

/** Chart bucket label: "1 Sep" for days (YYYY-MM-DD), "Sep 2026" for months (YYYY-MM). */
export function formatBucket(bucket, granularity) {
  const [y, m, d = '1'] = bucket.split('-')
  const date = new Date(Number(y), Number(m) - 1, Number(d))
  return granularity === 'day' ? dayLabel.format(date) : monthLabel.format(date)
}

export function formatMonthYear(isoDateTime) {
  return isoDateTime ? longMonth.format(new Date(isoDateTime)) : ''
}
