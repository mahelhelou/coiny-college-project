// BR-14 / BR-17: the API sends amounts as 2-decimal strings; this module only formats them.
export const currency = import.meta.env.VITE_CURRENCY || 'USD'

const standard = new Intl.NumberFormat('en-US', { style: 'currency', currency })
const compact = new Intl.NumberFormat('en-US', {
  style: 'currency',
  currency,
  notation: 'compact',
  maximumFractionDigits: 1,
})

export function formatMoney(amount) {
  return standard.format(Number(amount ?? 0))
}

export function formatCompactMoney(amount) {
  return compact.format(Number(amount ?? 0))
}

/** "+$250.00" for income, "−$250.00" for expenses — never rely on colour alone. */
export function formatSigned(amount, type) {
  return `${type === 'expense' ? '−' : '+'}${formatMoney(amount)}`
}

export function isNegative(amount) {
  return String(amount ?? '')
    .trim()
    .startsWith('-')
}
