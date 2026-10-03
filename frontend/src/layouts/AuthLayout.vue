<script setup>
import AppLogo from '@/components/ui/AppLogo.vue'
import ThemeSwitcher from '@/components/ui/ThemeSwitcher.vue'
</script>

<template>
  <div class="flex min-h-screen flex-col bg-bg">
    <header class="flex justify-end p-4">
      <ThemeSwitcher />
    </header>

    <main class="flex flex-1 flex-col items-center justify-center px-4 pb-16">
      <div class="w-full max-w-sm">
        <div class="mb-8 flex justify-center">
          <AppLogo />
        </div>
        <RouterView />
      </div>
    </main>
  </div>
</template>
