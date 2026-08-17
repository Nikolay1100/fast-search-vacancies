<template>
  <AppLayout>
    <transition name="fade" mode="out-in">
      <component :is="currentView" />
    </transition>
  </AppLayout>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import AppLayout from './components/layout/AppLayout.vue'
import DashboardView from './views/DashboardView.vue'
import ArchiveView from './views/ArchiveView.vue'
import KeywordsView from './views/KeywordsView.vue'
import ChannelsView from './views/ChannelsView.vue'
import PlansView from './views/PlansView.vue'
import { useNavigationStore } from './stores/navigation'
import { useUserStore } from './stores/user'

const nav = useNavigationStore()
const userStore = useUserStore()

const views = {
  dashboard: DashboardView,
  archive: ArchiveView,
  keywords: KeywordsView,
  channels: ChannelsView,
  plans: PlansView
}

const currentView = computed(() => views[nav.currentTab])

onMounted(() => {
  // Fetch user profile on startup
  userStore.fetchUser()

  // Telegram WebApp Initialization
  if (window.Telegram?.WebApp) {
    const tg = window.Telegram.WebApp
    tg.ready()
    tg.expand()

    // Explicitly set bg color to let TG client know
    const updateTheme = () => {
      if (tg.colorScheme === 'dark') {
        document.documentElement.classList.add('dark')
      } else {
        document.documentElement.classList.remove('dark')
      }
    }
    
    updateTheme()
    tg.onEvent('themeChanged', updateTheme)

    if (tg.backgroundColor) {
        tg.setBackgroundColor(tg.backgroundColor)
    }
    if (tg.setHeaderColor && tg.themeParams.secondary_bg_color) {
        tg.setHeaderColor(tg.themeParams.secondary_bg_color)
    }
  }
})
</script>

<style>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1), transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: scale(0.98);
}
</style>
