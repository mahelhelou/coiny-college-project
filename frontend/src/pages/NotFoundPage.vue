<script setup>
import AppLogo from '@/components/ui/AppLogo.vue'
</script>

<template>
  <main class="flex min-h-screen flex-col items-center justify-center bg-bg px-4 text-center">
    <AppLogo />
    <p class="mt-10 text-sm font-semibold text-primary">404</p>
    <h1 class="mt-2 text-2xl font-semibold tracking-tight text-gray-900">Page not found</h1>
    <p class="mt-2 max-w-sm text-sm text-gray-500">
      The page you're looking for doesn't exist or has been moved.
    </p>
    <RouterLink
      :to="{ name: 'dashboard' }"
      class="mt-6 inline-flex h-10 items-center rounded-lg bg-primary px-4 text-sm font-medium text-on-accent hover:bg-primary/90"
    >
      Back to dashboard
    </RouterLink>
  </main>
</template>
