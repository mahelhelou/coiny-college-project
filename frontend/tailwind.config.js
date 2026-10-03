import defaultTheme from 'tailwindcss/defaultTheme'
import plugin from 'tailwindcss/plugin'
import { hexToChannels, themes } from './src/utils/palette.js'

const GRAY_STEPS = [50, 100, 200, 300, 400, 500, 600, 700, 800, 900]
const TOKENS = [
  'primary',
  'primaryDark',
  'expense',
  'danger',
  'onAccent',
  'bg',
  'surface',
  'border',
]

const kebab = (name) => name.replace(/[A-Z]/g, (c) => `-${c.toLowerCase()}`)
const color = (variable) => `rgb(var(--${variable}) / <alpha-value>)`

/** CSS variables for one theme, e.g. --primary: 15 118 110; --gray-500: 107 114 128; */
function variables(theme) {
  return {
    ...Object.fromEntries(TOKENS.map((t) => [`--${kebab(t)}`, hexToChannels(theme[t])])),
    ...Object.fromEntries(GRAY_STEPS.map((s) => [`--gray-${s}`, hexToChannels(theme.gray[s])])),
  }
}

/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{vue,js}'],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        primary: { DEFAULT: color('primary'), dark: color('primary-dark') },
        expense: color('expense'),
        danger: color('danger'),
        'on-accent': color('on-accent'),
        bg: color('bg'),
        surface: color('surface'),
        border: color('border'),
        // Every gray-* class flips with the theme, so components need no dark: variants.
        gray: Object.fromEntries(GRAY_STEPS.map((s) => [s, color(`gray-${s}`)])),
      },
      borderColor: { DEFAULT: color('border') },
      ringOffsetColor: { DEFAULT: color('surface') },
      fontFamily: { sans: ['Inter', ...defaultTheme.fontFamily.sans] },
    },
  },
  plugins: [
    plugin(({ addBase }) => {
      addBase({
        ':root': { ...variables(themes.light), colorScheme: 'light' },
        '.dark': { ...variables(themes.dark), colorScheme: 'dark' },
      })
    }),
  ],
}
