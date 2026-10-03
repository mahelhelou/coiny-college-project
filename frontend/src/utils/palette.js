// Design tokens (CLAUDE.md §9) — the single source for both themes. Tailwind turns these into CSS
// variables (tailwind.config.js); Chart.js reads them through chartTheme().

const gray = {
  light: {
    50: '#F9FAFB',
    100: '#F3F4F6',
    200: '#E5E7EB',
    300: '#D1D5DB',
    400: '#9CA3AF',
    500: '#6B7280',
    600: '#4B5563',
    700: '#374151',
    800: '#1F2937',
    900: '#111827',
  },
  // Inverted ramp: gray-900 is still "strongest ink", gray-50 still "faintest fill".
  dark: {
    50: '#172033',
    100: '#1E293B',
    200: '#2B3648',
    300: '#3B475C',
    400: '#7C8594',
    500: '#9CA3AF',
    600: '#B4BAC4',
    700: '#D1D5DB',
    800: '#E5E7EB',
    900: '#F3F4F6',
  },
}

export const themes = {
  light: {
    primary: '#0F766E', // buttons, links, income
    primaryDark: '#0B3B37', // sidebar / top bar
    expense: '#D9480F', // expenses, negative balance
    danger: '#B42318', // validation errors, delete
    onAccent: '#FFFFFF', // text on primary / expense / danger fills
    bg: '#F9FAFB', // page background
    surface: '#FFFFFF', // cards, inputs, modals
    border: '#E5E7EB',
    gray: gray.light,
  },
  dark: {
    primary: '#14B8A6',
    primaryDark: '#0A2724',
    expense: '#F97316',
    danger: '#F87171',
    onAccent: '#0B1120',
    bg: '#0B1120',
    surface: '#111827',
    border: '#243044',
    gray: gray.dark,
  },
}

// Categorical slices, fixed order. The dark steps are validated against the dark surface.
const slices = {
  light: ['#0F766E', '#D9480F', '#2563EB', '#A16207', '#7C3AED', '#DB2777', '#4D7C0F', '#475569'],
  dark: ['#0D9488', '#EA580C', '#3B82F6', '#B7791F', '#8B5CF6', '#EC4899', '#65A30D', '#6B7FA8'],
}

export const SLICE_COUNT = slices.light.length

export function chartTheme(mode) {
  const t = themes[mode] ?? themes.light
  return {
    income: t.primary,
    expense: t.expense,
    surface: t.surface,
    border: t.border,
    grid: t.gray[100],
    ticks: t.gray[500],
    tooltipBg: mode === 'dark' ? '#243044' : t.primaryDark,
    slices: slices[mode] ?? slices.light,
  }
}

/** "#0F766E" → "15 118 110", the channel format Tailwind's <alpha-value> needs. */
export function hexToChannels(hex) {
  return [1, 3, 5].map((i) => parseInt(hex.slice(i, i + 2), 16)).join(' ')
}
