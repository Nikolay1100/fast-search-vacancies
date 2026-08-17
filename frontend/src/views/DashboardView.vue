<template>
  <div class="space-y-6 animate-in fade-in duration-500">
    <div class="space-y-1 px-1">
      <h2 class="text-3xl font-bold text-tg-text tracking-tight font-mono">Dashboard</h2>
      <p class="text-sm text-tg-hint font-medium uppercase tracking-widest soft-glow-text">Real-time System Monitoring</p>
    </div>

    <!-- Status Hero Card (Airy Layout) -->
    <div class="relative overflow-hidden p-10 text-tg-text glass-panel rounded-[40px] group transition-all hover:scale-[1.02] duration-500 cursor-pointer border border-glass-border">
      <!-- Ambient Background Glow -->
      <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-gradient-to-tl from-emerald-400/20 to-tg-button/20 blur-[40px] rounded-full pointer-events-none group-hover:scale-110 transition-transform duration-700"></div>

      <div class="relative z-10 space-y-6">
        <div class="flex justify-between items-start">
          <div class="space-y-1">
            <span class="text-[11px] font-bold text-tg-text uppercase tracking-widest opacity-70">Monitor Status</span>
            <div class="flex items-center gap-3">
              <div class="w-3 h-3 bg-tg-button rounded-full animate-pulse soft-glow"></div>
              <h3 class="text-3xl font-bold text-tg-text font-mono tracking-tight">Active & Scanning</h3>
            </div>
          </div>
          <div class="w-14 h-14 bg-gradient-to-br from-tg-button to-emerald-400 rounded-full flex items-center justify-center text-white shadow-lg group-hover:rotate-12 transition-transform duration-500">
            <ActivityIcon :size="24"/>
          </div>
        </div>

        <p class="text-sm font-medium text-tg-hint max-w-sm leading-relaxed">
          The system is successfully monitoring external channels for your active keywords.
        </p>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div @click="handleNavigate('archive')" class="group glass-panel p-8 rounded-[32px] transition-all cursor-pointer hover:scale-[1.02] border border-glass-border relative overflow-hidden active:scale-[0.98]">
        <div class="absolute -right-8 -top-8 w-32 h-32 bg-amber-500/10 blur-[30px] rounded-full pointer-events-none group-hover:scale-125 transition-transform duration-700"></div>
        <div class="flex items-center gap-5 relative z-10">
          <div
              class="w-16 h-16 bg-gradient-to-br from-amber-400 to-orange-400 text-white rounded-[24px] shadow-lg flex items-center justify-center group-hover:scale-110 group-hover:rotate-6 transition-all duration-300">
            <ZapIcon :size="28"/>
          </div>
          <div class="space-y-1">
            <div class="text-4xl font-bold text-tg-text tracking-tighter font-mono">{{ stats.matches }}</div>
            <div class="text-[10px] text-tg-hint font-bold uppercase tracking-widest">Matches (72h)</div>
          </div>
        </div>
      </div>
      <div @click="handleNavigate('keywords')" class="group glass-panel p-8 rounded-[32px] transition-all cursor-pointer hover:scale-[1.02] border border-glass-border relative overflow-hidden active:scale-[0.98]">
        <div class="absolute -right-8 -bottom-8 w-32 h-32 bg-emerald-500/10 blur-[30px] rounded-full pointer-events-none group-hover:scale-125 transition-transform duration-700"></div>
        <div class="flex items-center gap-5 relative z-10">
          <div
              class="w-16 h-16 bg-gradient-to-br from-tg-button to-emerald-400 text-white rounded-[24px] shadow-lg flex items-center justify-center group-hover:scale-110 group-hover:-rotate-6 transition-all duration-300">
            <KeyIcon :size="28"/>
          </div>
          <div class="space-y-1">
            <div class="text-4xl font-bold text-tg-text tracking-tighter font-mono">{{ stats.keywords }}</div>
            <div class="text-[10px] text-tg-hint font-bold uppercase tracking-widest">Active Keywords</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Tips -->
    <!-- <div class="glass-pill p-5 rounded-[28px] flex items-start gap-4">
        <div class="w-10 h-10 bg-tg-button/10 text-tg-button rounded-full flex items-center justify-center flex-shrink-0 border border-tg-button/20">
            <InfoIcon :size="20" />
        </div>
        <div class="space-y-1.5 pt-0.5">
            <h4 class="text-sm font-bold text-tg-text">Pro Tip</h4>
            <p class="text-xs text-tg-hint leading-relaxed font-medium">
                Use more specific keywords like "Senior Vue" instead of just "Vue" to reduce noise in your match history.
            </p>
        </div>
    </div> -->
  </div>
</template>

<script setup>
import {ref, onMounted} from 'vue'
import apiClient from '../api/client'
import {useNavigationStore} from '../stores/navigation'
import {
  KeyIcon,
  ActivityIcon,
  ZapIcon,
  InfoIcon
} from 'lucide-vue-next'

const nav = useNavigationStore()
const stats = ref({
  keywords: 0,
  matches: 0
})

const handleNavigate = (tab) => {
  nav.setTab(tab)
  if (window.Telegram?.WebApp?.HapticFeedback) {
    window.Telegram.WebApp.HapticFeedback.selectionChanged()
  }
}

const fetchStats = async () => {
  try {
    const [kwRes, vacRes] = await Promise.all([
      apiClient.get('/user/keywords'),
      apiClient.get('/user/vacancies')
    ])
    stats.value.keywords = kwRes.data.data.length
    stats.value.matches = vacRes.data.meta?.total || vacRes.data.data.length
  } catch (e) {
    console.error(e)
  }
}

onMounted(fetchStats)
</script>

<style scoped>
@keyframes fade-in {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

.animate-in {
  animation: fade-in 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}
</style>
