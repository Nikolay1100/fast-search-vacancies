<template>
  <div class="space-y-6">
    <div class="flex justify-between items-end px-2">
      <div class="space-y-1">
        <h2 class="text-3xl font-bold text-tg-text tracking-tight font-mono">Archive</h2>
        <p class="text-sm text-tg-hint font-medium uppercase tracking-widest soft-glow-text">Matched Vacancies</p>
      </div>
      <div class="glass-pill px-4 py-2 flex items-center justify-center rounded-full shadow-sm">
        <span class="text-[10px] text-tg-button font-bold uppercase tracking-widest">{{ pagination.total }} records</span>
      </div>
    </div>
    
    <!-- Skeleton Loading -->
    <div v-if="loading && items.length === 0" class="space-y-4">
        <div v-for="n in 3" :key="n" class="glass-panel p-8 rounded-[32px] animate-pulse space-y-4 border border-glass-border">
            <div class="flex justify-between">
                <div class="w-20 h-4 bg-tg-text opacity-20"></div>
                <div class="w-12 h-3 bg-tg-text opacity-20"></div>
            </div>
            <div class="space-y-2">
                <div class="w-full h-3 bg-tg-text opacity-20"></div>
                <div class="w-5/6 h-3 bg-tg-text opacity-20"></div>
                <div class="w-4/6 h-3 bg-tg-text opacity-20"></div>
            </div>
            <div class="flex justify-between pt-2">
                <div class="w-24 h-4 bg-tg-text opacity-20"></div>
                <div class="w-16 h-8 bg-tg-text opacity-20"></div>
            </div>
        </div>
    </div>

    <!-- Empty State -->
    <div v-if="!loading && items.length === 0" class="glass-panel border border-glass-border rounded-[40px] p-16 text-center space-y-6">
        <div class="flex justify-center relative">
            <HistoryIcon :size="80" class="text-tg-hint/30 relative z-10" />
        </div>
        <div class="space-y-2">
            <h3 class="text-xl font-bold text-tg-text font-mono uppercase">Silence is Golden</h3>
            <p class="text-tg-hint text-sm max-w-[240px] mx-auto leading-relaxed">No matches yet. We'll notify you as soon as something appears.</p>
        </div>
    </div>

    <!-- Real List -->
    <transition-group
      name="list-complete"
      tag="div"
      class="grid grid-cols-1 gap-5 relative z-10"
    >
      <div 
        v-for="(item, index) in items" 
        :key="item.id"
        :style="`animation-delay: ${index * 50}ms`"
        class="glass-panel border border-glass-border p-8 rounded-[32px] flex flex-col group transition-all duration-300 animate-in fade-in slide-in-from-bottom-4"
      >
        <div class="flex justify-between items-start mb-5">
          <div class="flex flex-wrap gap-2">
            <div class="flex items-center gap-1.5 px-3 py-1 border border-tg-button/30 bg-tg-button/10 rounded-full">
              <div class="w-2 h-2 bg-tg-button rounded-full soft-glow"></div>
              <span class="text-[10px] text-tg-button font-bold uppercase tracking-widest">Matched</span>
            </div>
            <div class="px-3 py-1 border border-tg-link/30 bg-tg-link/10 rounded-full">
              <span class="text-[10px] text-tg-link font-bold uppercase tracking-widest">{{ item.keyword }}</span>
            </div>
          </div>
          <div class="flex items-center gap-1.5 text-tg-hint glass-pill px-3 py-1 rounded-full shadow-sm">
            <CalendarIcon :size="12" />
            <span class="text-[10px] font-bold">{{ formatTime(item.matched_at) }}</span>
          </div>
        </div>

        <div 
            @click="toggleExpand(item)"
            class="text-[15px] leading-relaxed relative mb-6 font-medium text-tg-text/90 cursor-pointer transition-all duration-300"
            :class="item.isExpanded ? '' : 'max-h-48 overflow-hidden'"
        >
          <div v-html="item.text" class="vacancy-content"></div>
          <div v-if="!item.isExpanded" class="absolute bottom-0 left-0 right-0 h-16 bg-gradient-to-t from-tg-secondary to-transparent pointer-events-none group-hover:opacity-70 transition-opacity"></div>
        </div>

        <div class="mt-auto flex justify-end items-center pt-3">
             <button 
               v-if="item.link"
               @click.stop.prevent="openLink(item.link)"
               class="bg-tg-button text-tg-button-text px-6 py-3 text-[11px] font-bold uppercase tracking-widest transition-all rounded-full shadow-md active:scale-95 hover:brightness-110 flex items-center gap-2"
             >
                Read Fully
             </button>
        </div>
      </div>
    </transition-group>

    <!-- Unified Load More -->
    <div v-if="pagination.last_page > 1" class="flex justify-center pt-4 pb-20 relative z-10">
        <button 
          @click="loadMore" 
          :disabled="loading || pagination.current_page >= pagination.last_page"
          class="group relative glass-panel border border-glass-border text-tg-text px-8 py-4 text-xs font-bold uppercase tracking-widest rounded-full transition-all active:scale-95 disabled:opacity-50 cursor-pointer shadow-md"
        >
            <div class="flex items-center gap-3">
                <Loader2Icon v-if="loading" class="animate-spin text-tg-button" :size="16" />
                <ArrowDownIcon v-else :size="16" class="text-tg-button group-hover:translate-y-1 transition-transform" />
                <span>{{ loading ? 'Loading...' : 'Older Matches' }}</span>
            </div>
        </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import apiClient from '../api/client'
import { 
  Loader2Icon, 
  HistoryIcon, 
  HashIcon,
  CalendarIcon,
  ArrowDownIcon
} from 'lucide-vue-next'

const items = ref([])
const loading = ref(false)
const pagination = ref({ current_page: 1, last_page: 1, total: 0 })

const fetchVacancies = async (page = 1) => {
    loading.value = true
    try {
        const { data } = await apiClient.get('/user/vacancies', { params: { page } })
        if (page === 1) {
            items.value = data.data
        } else {
            items.value = [...items.value, ...data.data]
        }
        pagination.value = {
            current_page: data.meta?.current_page || page,
            last_page: data.meta?.last_page || 1,
            total: data.meta?.total || data.data.length
        }
    } catch (e) {
        console.error(e)
    } finally {
        loading.value = false
    }
}

const loadMore = () => {
    if (pagination.value.current_page < pagination.value.last_page) {
        fetchVacancies(pagination.value.current_page + 1)
    }
}

const handleHaptic = () => {
    if (window.Telegram?.WebApp?.HapticFeedback) {
        window.Telegram.WebApp.HapticFeedback.impactOccurred('light')
    }
}

const toggleExpand = (item) => {
    item.isExpanded = !item.isExpanded
    handleHaptic()
}

const openLink = (url) => {
    handleHaptic()
    if (window.Telegram?.WebApp) {
        if (url.includes('t.me')) {
            window.Telegram.WebApp.openTelegramLink(url)
        } else {
            window.Telegram.WebApp.openLink(url)
        }
    } else {
        window.open(url, '_blank')
    }
}

const formatTime = (dateStr) => {
    const date = new Date(dateStr)
    return date.toLocaleDateString([], { day: 'numeric', month: 'long', year: 'numeric' })
}

onMounted(() => fetchVacancies(1))
</script>

<style>
.vacancy-content b, .vacancy-content strong {
    color: var(--color-tg-text);
}
.vacancy-content a {
    color: var(--color-tg-link);
    text-decoration: underline;
    text-decoration-color: rgba(var(--color-tg-link), 0.3);
    text-underline-offset: 2px;
}

.list-complete-enter-from { opacity: 0; transform: translateY(30px) scale(0.98); }
.list-complete-leave-to { opacity: 0; transform: scale(0.95); }
.list-complete-leave-active { position: absolute; }
</style>
