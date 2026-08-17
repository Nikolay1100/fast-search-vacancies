<template>
  <div class="min-h-screen bg-tg-bg text-tg-text flex flex-col md:flex-row transition-colors duration-300">
    <!-- Desktop Sidebar (Premium Left Nav) -->
    <aside class="hidden md:flex w-72 glass-panel border-r border-glass-border flex-col sticky top-0 h-screen z-20">
      <div class="p-8 flex items-center gap-4 relative">
        <div class="absolute bottom-0 left-8 right-8 h-px bg-glass-border"></div>
        <div class="w-14 h-14 bg-gradient-to-br from-tg-button to-emerald-400 flex items-center justify-center text-white rounded-[24px] shadow-[0_10px_20px_rgba(34,197,94,0.3)] transform hover:scale-105 transition-transform">
          <SearchIcon :size="28" />
        </div>
        <div class="flex flex-col">
            <span class="text-xl leading-none text-tg-text font-mono font-bold uppercase">FastSearch</span>
            <span class="text-[9px] uppercase tracking-widest text-tg-button mt-1.5 soft-glow-text font-bold">Vacancy Monitor</span>
        </div>
      </div>
      
      <nav class="flex-1 p-6 space-y-3">
        <button 
          v-for="(tab, index) in nav.tabs" 
          :key="tab.id"
          @click="nav.setTab(tab.id)"
          :style="`animation-delay: ${index * 100}ms`"
          :class="[
            'w-full flex items-center gap-4 px-5 py-4 transition-all duration-300 group relative overflow-hidden animate-in fade-in slide-in-from-left-4 hover:scale-[1.02] rounded-2xl',
            nav.currentTab === tab.id 
              ? 'bg-tg-button/10 text-tg-button border border-tg-button/20 shadow-sm' 
              : 'text-tg-hint hover:bg-glass-hover hover:text-tg-text border border-transparent'
          ]"
        >
          <div v-if="nav.currentTab === tab.id" class="absolute left-0 top-1/2 -translate-y-1/2 h-8 w-1.5 bg-tg-button rounded-r-full soft-glow"></div>
          <component :is="icons[tab.icon]" :size="22" class="group-hover:scale-110 transition-transform duration-300" />
          <span class="text-sm tracking-wide uppercase font-bold">{{ tab.label }}</span>
        </button>
      </nav>

      <div class="p-8 relative">
         <div class="absolute top-0 left-8 right-8 h-px bg-glass-border"></div>
         <div class="flex items-center justify-between glass-panel p-4 rounded-[28px] hover:scale-[1.02] transition-transform">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-tr from-tg-button to-emerald-300 flex items-center justify-center text-white text-sm font-bold rounded-full shadow-md">
                    {{ userStore.profile?.name?.[0] || 'U' }}
                </div>
                <div class="flex flex-col">
                    <span class="text-xs font-bold truncate uppercase">{{ userStore.profile?.name || 'Loading...' }}</span>
                    <span class="text-[10px] text-tg-hint uppercase tracking-wider">ID: {{ userStore.profile?.telegram_id || '---' }}</span>
                </div>
            </div>
            <button
                @click="nav.setTab('plans')"
                class="w-8 h-8 flex items-center justify-center transition-all hover:scale-110 active:scale-95 shrink-0 rounded-full shadow-sm"
                :class="userStore.isPremium() ? 'bg-amber-500 text-white' : 'bg-tg-button text-white'"
            >
                <CrownIcon v-if="userStore.isPremium()" :size="16" />
                <SparklesIcon v-else :size="16" />
            </button>
         </div>
      </div>
    </aside>

    <!-- Mobile Native Header -->
    <header class="md:hidden glass-panel border-b border-glass-border px-5 py-4 flex justify-between items-center sticky top-0 z-30 transition-all duration-300">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 bg-gradient-to-br from-tg-button to-emerald-400 flex items-center justify-center text-white shadow-md rounded-[16px]">
          <component :is="currentIconComponent" :size="20" />
        </div>
        <h1 class="text-sm font-bold tracking-tight text-tg-text uppercase">{{ currentTabLabel }}</h1>
      </div>
      <div
        @click="handleTabChange('plans')"
        class="flex items-center gap-2 px-4 py-2 border border-glass-border cursor-pointer transition-all active:scale-95 glass-pill rounded-full shadow-sm"
        :class="userStore.isPremium() ? 'bg-amber-500/10 text-amber-500' : 'bg-tg-button/10 text-tg-button'"
      >
        <CrownIcon v-if="userStore.isPremium()" :size="14" stroke-width="2.5" />
        <SparklesIcon v-else :size="14" stroke-width="2.5" />
        <span class="text-[10px] uppercase font-bold tracking-wider">
            {{ userStore.isPremium() ? 'Premium' : 'Upgrade' }}
        </span>
      </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-1 flex flex-col relative min-w-0 z-10">
      <div class="flex-1 p-5 md:p-12 pb-32 md:pb-12">
        <div class="max-w-4xl mx-auto w-full animate-in fade-in slide-in-from-bottom-4 duration-700">
            <slot></slot>
        </div>
      </div>

      <!-- Mobile Bottom Nav -->
      <nav class="md:hidden fixed bottom-0 left-0 right-0 glass-panel border-t border-glass-border px-8 py-3 pb-safe flex justify-between items-center z-40 pt-4">
        <button 
          v-for="tab in nav.tabs" 
          :key="tab.id"
          @click="handleTabChange(tab.id)"
          :class="[
            'flex flex-col items-center gap-1.5 transition-all duration-300 active:scale-95',
            nav.currentTab === tab.id ? 'text-tg-button scale-110 soft-glow-text' : 'text-tg-hint'
          ]"
        >
          <div class="relative">
            <component :is="icons[tab.icon]" :size="24" stroke-width="2.5" />
          </div>
          <span class="text-[8px] uppercase tracking-widest opacity-90 mt-1 font-bold" :class="{'opacity-0 h-0': nav.currentTab !== tab.id}">{{ tab.label }}</span>
        </button>
      </nav>
    </main>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useNavigationStore } from '../../stores/navigation'
import { useUserStore } from '../../stores/user'
import { 
  SearchIcon, 
  LayoutDashboardIcon, 
  HistoryIcon, 
  KeyIcon, 
  LayersIcon,
  CrownIcon,
  SparklesIcon
} from 'lucide-vue-next'

const nav = useNavigationStore()
const userStore = useUserStore()

const icons = {
  LayoutDashboardIcon,
  HistoryIcon,
  KeyIcon,
  LayersIcon,
  CrownIcon
}

const currentTabLabel = computed(() => {
  if (nav.currentTab === 'plans') return 'Premium'
  return nav.tabs.find(t => t.id === nav.currentTab)?.label || 'FastSearch'
})

const currentIconComponent = computed(() => {
  if (nav.currentTab === 'plans') return CrownIcon
  const tab = nav.tabs.find(t => t.id === nav.currentTab)
  return tab ? icons[tab.icon] : SearchIcon
})

const handleTabChange = (id) => {
    nav.setTab(id)
    if (window.Telegram?.WebApp?.HapticFeedback) {
        window.Telegram.WebApp.HapticFeedback.selectionChanged()
    }
}
</script>

<style scoped>
.pb-safe {
  padding-bottom: calc(0.8rem + env(safe-area-inset-bottom, 0px));
}

@keyframes fade-in { from { opacity: 0; } to { opacity: 1; } }
@keyframes slide-in-from-left-4 { from { transform: translateX(-1rem); } to { transform: translateX(0); } }
@keyframes slide-in-from-bottom-4 { from { transform: translateY(1rem); } to { transform: translateY(0); } }
@keyframes zoom-in { from { transform: translate(-50%, 0) scale(0); } to { transform: translate(-50%, 0) scale(1); } }

.animate-in {
  animation-fill-mode: both;
}
.duration-700 { animation-duration: 700ms; }
.fade-in { animation-name: fade-in; }
.slide-in-from-left-4 { animation-name: slide-in-from-left-4; }
.slide-in-from-bottom-4 { animation-name: slide-in-from-bottom-4; }
.zoom-in { animation-name: zoom-in; }
</style>
