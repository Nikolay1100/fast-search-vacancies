<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center px-2">
      <div class="space-y-1">
        <h2 class="text-2xl font-bold text-tg-text tracking-tight font-mono">Channels</h2>
        <p class="text-xs text-tg-hint font-medium uppercase tracking-widest soft-glow-text">Monitored Data Sources</p>
      </div>
      <div class="glass-pill px-4 py-2 flex items-center justify-center rounded-full shadow-sm">
        <span class="text-[10px] text-tg-button font-bold uppercase tracking-widest">{{ channels.length }} active</span>
      </div>
    </div>

    <div v-if="loading" class="flex justify-center py-20">
        <Loader2Icon class="animate-spin text-tg-button" :size="32" />
    </div>

    <div class="grid grid-cols-1 gap-4">
      <div 
        v-for="(ch, index) in channels" 
        :key="ch.id"
        :style="`animation-delay: ${index * 50}ms`"
        class="glass-panel p-6 border border-glass-border rounded-[32px] flex justify-between items-center group hover:scale-[1.02] transition-all duration-300 animate-in fade-in slide-in-from-bottom-4"
      >
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 flex items-center justify-center transition-all duration-300 relative overflow-hidden bg-white/50 dark:bg-black/20 rounded-[20px] shadow-sm"
                 :class="ch.is_active ? 'shadow-md border border-glass-border' : 'opacity-50 grayscale'">
                <img :src="'https://api.dicebear.com/7.x/initials/svg?seed=' + ch.name + '&backgroundColor=transparent'" class="w-full h-full object-cover relative z-10 p-1" alt="avatar">
            </div>
            <div class="flex flex-col">
                <div class="text-sm font-bold text-tg-text tracking-tight">{{ ch.name }}</div>
                <div class="text-[10px] text-tg-hint/80 font-medium tracking-wide mt-0.5">@{{ ch.username || 'private' }}</div>
            </div>
        </div>
        <div class="flex items-center gap-2 glass-pill px-3 py-1.5 transition-colors rounded-full shadow-sm">
            <div 
              class="w-2 h-2 rounded-full"
              :class="ch.is_active ? 'bg-tg-button soft-glow' : 'bg-tg-hint'"
            ></div>
            <span 
              class="text-[10px] font-bold uppercase tracking-widest"
              :class="ch.is_active ? 'text-tg-button soft-glow-text' : 'text-tg-hint'"
            >
              {{ ch.is_active ? 'Active' : 'Paused' }}
            </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import apiClient from '../api/client'
import { Loader2Icon } from 'lucide-vue-next'

const channels = ref([])
const loading = ref(false)

const fetchChannels = async () => {
    loading.value = true
    try {
        const { data } = await apiClient.get('/channels')
        channels.value = data.data
    } catch (e) {
        console.error(e)
    } finally {
        loading.value = false
    }
}

onMounted(fetchChannels)
</script>

<style scoped>
@keyframes fade-in { from { opacity: 0; } to { opacity: 1; } }
@keyframes slide-in-from-bottom-4 { from { transform: translateY(1rem); } to { transform: translateY(0); } }

.animate-in { animation-duration: 500ms; animation-fill-mode: both; }
.fade-in { animation-name: fade-in; }
.slide-in-from-bottom-4 { animation-name: slide-in-from-bottom-4; }
</style>
