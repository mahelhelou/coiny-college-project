<script setup>
import { ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import AppLogo from '@/components/ui/AppLogo.vue'
import AppIcon from '@/components/ui/AppIcon.vue'
import ThemeSwitcher from '@/components/ui/ThemeSwitcher.vue'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const ui = useUiStore()
const { user } = storeToRefs(auth)

const navigation = [
  { name: 'dashboard', label: 'Dashboard', icon: 'dashboard' },
  { name: 'transactions', label: 'Transactions', icon: 'transactions' },
  { name: 'categories', label: 'Categories', icon: 'categories' },
  { name: 'profile', label: 'Profile', icon: 'profile' },
]

const drawerOpen = ref(false)
const loggingOut = ref(false)

watch(
  () => route.fullPath,
  () => (drawerOpen.value = false),
)

async function logout() {
  loggingOut.value = true
  try {
    await auth.logout()
    router.push({ name: 'login' })
  } catch (error) {
    ui.fail(error, 'Could not log out.')
  } finally {
    loggingOut.value = false
  }
}

const initials = (name = '') =>
  name
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0].toUpperCase())
    .join('')
</script>

<template>
  <div class="min-h-screen bg-bg" @keydown.esc="drawerOpen = false">
    <!-- Top bar (below lg) -->
    <header
      class="sticky top-0 z-30 flex h-14 items-center justify-between bg-primary-dark px-4 text-white lg:hidden"
    >
      <button
        type="button"
        class="-ml-2 rounded-lg p-2 text-white/80 hover:bg-white/10 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
        aria-label="Open menu"
        :aria-expanded="drawerOpen"
        @click="drawerOpen = true"
      >
        <AppIcon name="menu" />
      </button>
      <RouterLink :to="{ name: 'dashboard' }" aria-label="Coiny home">
        <AppLogo inverse />
      </RouterLink>
      <RouterLink
        :to="{ name: 'profile' }"
        class="flex h-8 w-8 items-center justify-center rounded-full bg-white/15 text-xs font-semibold"
        :aria-label="`Profile of ${user?.name}`"
      >
        {{ initials(user?.name) }}
      </RouterLink>
    </header>

    <!-- Drawer backdrop -->
    <Transition
      enter-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      leave-active-class="transition-opacity duration-150"
      leave-to-class="opacity-0"
    >
      <div
        v-if="drawerOpen"
        class="fixed inset-0 z-40 bg-black/50 lg:hidden"
        aria-hidden="true"
        @click="drawerOpen = false"
      />
    </Transition>

    <!-- Sidebar: fixed on lg+, drawer below -->
    <aside
      :class="[
        'fixed inset-y-0 left-0 z-50 flex w-64 flex-col bg-primary-dark text-white transition-transform duration-200 ease-out lg:translate-x-0',
        drawerOpen ? 'translate-x-0' : '-translate-x-full',
      ]"
      aria-label="Main navigation"
    >
      <div class="flex h-16 items-center justify-between px-5">
        <RouterLink :to="{ name: 'dashboard' }"><AppLogo inverse /></RouterLink>
        <button
          type="button"
          class="rounded-lg p-2 text-white/70 hover:bg-white/10 hover:text-white lg:hidden"
          aria-label="Close menu"
          @click="drawerOpen = false"
        >
          <AppIcon name="x" />
        </button>
      </div>

      <nav class="flex-1 space-y-1 px-3 py-4">
        <RouterLink
          v-for="item in navigation"
          :key="item.name"
          :to="{ name: item.name }"
          class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-white/70 transition-colors hover:bg-white/5 hover:text-white"
          active-class="!bg-white/10 !text-white"
        >
          <AppIcon :name="item.icon" />
          {{ item.label }}
        </RouterLink>
      </nav>

      <div class="border-t border-white/10 p-3">
        <div class="mb-2 flex items-center justify-between px-3 py-1">
          <span class="text-xs font-medium text-white/60">Theme</span>
          <ThemeSwitcher variant="sidebar" />
        </div>
        <div class="flex items-center gap-3 rounded-lg px-3 py-2">
          <span
            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary text-sm font-semibold text-on-accent"
          >
            {{ initials(user?.name) }}
          </span>
          <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-medium">{{ user?.name }}</p>
            <p class="truncate text-xs text-white/60">{{ user?.email }}</p>
          </div>
        </div>
        <button
          type="button"
          class="mt-1 flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-white/70 transition-colors hover:bg-white/5 hover:text-white disabled:opacity-60"
          :disabled="loggingOut"
          @click="logout"
        >
          <AppIcon name="logout" />
          {{ loggingOut ? 'Logging out…' : 'Log out' }}
        </button>
      </div>
    </aside>

    <div class="lg:pl-64">
      <main class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
        <RouterView />
      </main>
    </div>
  </div>
</template>
