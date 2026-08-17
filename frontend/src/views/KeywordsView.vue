<template>
  <div class="space-y-6 pb-32">
    <!-- Header Section -->
    <div class="space-y-1 px-1">
        <h2 class="text-3xl font-bold text-tg-text tracking-tight font-mono">Filters</h2>
        <p class="text-sm text-tg-hint font-medium uppercase tracking-widest soft-glow-text">Manage Search Rules</p>
    </div>

    <!-- Strictly Side-by-side Layout on all screen widths -->
    <div class="grid grid-cols-2 gap-4">

        <!-- Left Column: Keywords to Monitor -->
        <div class="space-y-4">
            <div class="flex flex-col px-1">
                <h3 class="text-sm font-bold text-tg-text tracking-tight uppercase tracking-wider">Keywords</h3>
                <p class="text-[10px] text-tg-hint font-medium leading-tight mt-0.5">Vacancies containing these will match</p>
            </div>

            <!-- Input Area for Keywords -->
            <div class="glass-panel p-6 rounded-[32px] flex flex-col gap-3 relative overflow-hidden transition-all duration-300">

              <div class="relative group z-10">
                  <input
                    v-model="newWord"
                    @keyup.enter="addKeyword"
                    :disabled="loading"
                    type="text"
                    placeholder="e.g. python, php"
                    class="w-full glass-pill rounded-full pl-10 pr-3 py-3 text-xs font-bold placeholder:text-tg-hint/40 transition-all disabled:opacity-50 outline-none text-tg-text"
                  >
                  <div class="absolute left-3.5 top-1/2 -translate-y-1/2 text-tg-hint group-focus-within:text-tg-button group-focus-within:scale-110 transition-all duration-300">
                    <SearchIcon :size="16" stroke-width="2.5" />
                  </div>
              </div>

              <button
                @click="addKeyword"
                :disabled="loading || !newWord.trim()"
                class="relative z-10 w-full bg-gradient-to-r from-tg-button to-emerald-400 text-white py-3.5 text-[10px] font-bold uppercase tracking-widest rounded-full shadow-md active:scale-95 transition-all duration-300 disabled:opacity-50 flex items-center justify-center gap-2 hover:brightness-110 cursor-pointer"
              >
                <PlusIcon v-if="!loading" :size="14" stroke-width="2.5" />
                <Loader2Icon v-else class="animate-spin" :size="14" stroke-width="2.5" />
                {{ loading ? 'Saving...' : 'Add Keyword' }}
              </button>
            </div>

            <!-- Error Toast for Keywords -->
            <transition name="fade">
                <div v-if="error" class="glass-panel border-red-500/20 bg-red-500/10 text-red-500 p-3 rounded-2xl shadow-sm text-[10px] font-bold flex items-center gap-2">
                    <AlertCircleIcon :size="14" class="shrink-0" />
                    <span class="leading-tight">{{ error }}</span>
                </div>
            </transition>

            <!-- Keywords List -->
            <div class="space-y-2 relative z-10">
              <div v-if="initialLoading" class="flex justify-center py-6">
                 <Loader2Icon class="animate-spin text-tg-button" :size="24" />
              </div>

              <div v-if="keywords.length === 0 && !initialLoading" class="p-8 text-center text-tg-hint space-y-3 glass-panel rounded-[32px] border-dashed">
                 <div class="flex justify-center flex-col items-center gap-3">
                    <KeyIcon :size="24" class="opacity-40" stroke-width="1.5" />
                    <p class="text-[10px] font-medium tracking-wide">No keywords</p>
                 </div>
              </div>

              <transition-group name="list" tag="div" class="space-y-2">
                <div
                    v-for="kw in keywords"
                    :key="kw.id"
                    class="glass-panel p-4 rounded-[24px] flex justify-between items-center group transition-all duration-300 hover:scale-[1.02]"
                >
                    <div class="flex items-center gap-2 min-w-0">
                        <div class="w-8 h-8 glass-pill rounded-full shadow-sm flex items-center justify-center text-tg-hint group-hover:text-tg-button transition-all shrink-0">
                            <HashIcon :size="14" />
                        </div>
                        <div class="flex flex-col min-w-0">
                            <span class="font-bold text-xs text-tg-text tracking-tight truncate">{{ kw.word }}</span>
                            <span class="text-[8px] text-tg-hint font-bold uppercase tracking-wider mt-0.5 soft-glow-text group-hover:text-tg-button">Active</span>
                        </div>
                    </div>
                    <button
                        @click="deleteKeyword(kw.id)"
                        class="w-8 h-8 rounded-full flex items-center justify-center text-tg-hint opacity-50 hover:opacity-100 hover:text-red-500 hover:bg-red-500/10 transition-all active:scale-95 shrink-0 cursor-pointer"
                    >
                        <Trash2Icon :size="14" />
                    </button>
                </div>
              </transition-group>
            </div>
        </div>

        <!-- Right Column: Stop Words to Exclude -->
        <div class="space-y-4">
            <div class="flex flex-col px-1">
                <h3 class="text-sm font-bold text-tg-text tracking-tight uppercase tracking-wider">Stop Words</h3>
                <p class="text-[10px] text-tg-hint font-medium leading-tight mt-0.5">Vacancies containing these will ignore</p>
            </div>

            <!-- Input Area for Stop Words -->
            <div class="glass-panel p-6 rounded-[32px] flex flex-col gap-3 relative overflow-hidden transition-all duration-300">

              <div class="relative group z-10">
                  <input
                    v-model="newBannedWord"
                    @keyup.enter="addBannedKeyword"
                    :disabled="loadingBanned"
                    type="text"
                    placeholder="e.g. junior, casino"
                    class="w-full glass-pill rounded-full pl-10 pr-3 py-3 text-xs font-bold placeholder:text-tg-hint/40 focus:border-orange-500 transition-all disabled:opacity-50 outline-none text-tg-text"
                  >
                  <div class="absolute left-3.5 top-1/2 -translate-y-1/2 text-tg-hint group-focus-within:text-orange-500 group-focus-within:scale-110 transition-all duration-300">
                    <BanIcon :size="16" stroke-width="2.5" />
                  </div>
              </div>

              <button
                @click="addBannedKeyword"
                :disabled="loadingBanned || !newBannedWord.trim()"
                class="relative z-10 w-full bg-gradient-to-r from-orange-400 to-amber-500 text-white py-3.5 text-[10px] font-bold uppercase tracking-widest rounded-full shadow-md active:scale-95 transition-all duration-300 disabled:opacity-50 flex items-center justify-center gap-2 hover:brightness-110 cursor-pointer"
              >
                <PlusIcon v-if="!loadingBanned" :size="14" stroke-width="2.5" />
                <Loader2Icon v-else class="animate-spin" :size="14" stroke-width="2.5" />
                {{ loadingBanned ? 'Saving...' : 'Add Stop Word' }}
              </button>
            </div>

            <!-- Error Toast for Stop Words -->
            <transition name="fade">
                <div v-if="errorBanned" class="glass-panel border-red-500/20 bg-red-500/10 text-red-500 p-3 rounded-2xl shadow-sm text-[10px] font-bold flex items-center gap-2">
                    <AlertCircleIcon :size="14" class="shrink-0" />
                    <span class="leading-tight">{{ errorBanned }}</span>
                </div>
            </transition>

            <!-- Stop Words List -->
            <div class="space-y-2 relative z-10">
              <div v-if="initialLoading" class="flex justify-center py-6">
                 <Loader2Icon class="animate-spin text-orange-500" :size="24" />
              </div>

              <div v-if="bannedKeywords.length === 0 && !initialLoading" class="p-8 text-center text-tg-hint space-y-3 glass-panel rounded-[32px] border-dashed">
                 <div class="flex justify-center flex-col items-center gap-3">
                    <BanIcon :size="24" class="opacity-40" stroke-width="1.5" />
                    <p class="text-[10px] font-medium tracking-wide">No stop words</p>
                 </div>
              </div>

              <transition-group name="list" tag="div" class="space-y-2">
                <div
                    v-for="kw in bannedKeywords"
                    :key="kw.id"
                    class="glass-panel p-4 rounded-[24px] flex justify-between items-center group transition-all duration-300 hover:scale-[1.02]"
                >
                    <div class="flex items-center gap-2 min-w-0">
                        <div class="w-8 h-8 glass-pill rounded-full shadow-sm flex items-center justify-center text-tg-hint group-hover:text-orange-500 transition-all shrink-0">
                            <BanIcon :size="14" />
                        </div>
                        <div class="flex flex-col min-w-0">
                            <span class="font-bold text-xs text-tg-text tracking-tight truncate">{{ kw.word }}</span>
                            <span class="text-[8px] text-tg-hint font-bold uppercase tracking-wider mt-0.5 text-orange-500/70 group-hover:text-orange-500 soft-glow-text">Ignore</span>
                        </div>
                    </div>
                    <button
                        @click="deleteBannedKeyword(kw.id)"
                        class="w-8 h-8 rounded-full flex items-center justify-center text-tg-hint opacity-50 hover:opacity-100 hover:text-red-500 hover:bg-red-500/10 transition-all active:scale-95 shrink-0 cursor-pointer"
                    >
                        <Trash2Icon :size="14" />
                    </button>
                </div>
              </transition-group>
            </div>
        </div>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import apiClient from '../api/client'
import { 
  PlusIcon, 
  Trash2Icon, 
  Loader2Icon, 
  AlertCircleIcon, 
  SearchIcon,
  KeyIcon,
  HashIcon,
  BanIcon
} from 'lucide-vue-next'

// Keywords state
const keywords = ref([])
const newWord = ref('')
const loading = ref(false)
const error = ref(null)

// Stop words (banned keywords) state
const bannedKeywords = ref([])
const newBannedWord = ref('')
const loadingBanned = ref(false)
const errorBanned = ref(null)

// Shared state
const initialLoading = ref(true)

const triggerHaptic = (type = 'light') => {
    if (window.Telegram?.WebApp?.HapticFeedback) {
        if (type === 'error') window.Telegram.WebApp.HapticFeedback.notificationOccurred('error')
        else if (type === 'success') window.Telegram.WebApp.HapticFeedback.notificationOccurred('success')
        else window.Telegram.WebApp.HapticFeedback.impactOccurred(type)
    }
}

const showAutoHidingError = (targetRef, message, durationMs = 4000) => {
    targetRef.value = message
    setTimeout(() => {
        if (targetRef.value === message) {
            targetRef.value = null
        }
    }, durationMs)
}

const fetchAll = async () => {
    try {
        const [keywordsRes, bannedKeywordsRes] = await Promise.all([
            apiClient.get('/user/keywords'),
            apiClient.get('/user/banned_keywords')
        ])
        keywords.value = keywordsRes.data.data
        bannedKeywords.value = bannedKeywordsRes.data.data
    } catch (e) {
        error.value = 'Failed to load filters list'
    } finally {
        initialLoading.value = false
    }
}

// Keyword operations
const addKeyword = async () => {
    if (!newWord.value.trim() || loading.value) return
    
    loading.value = true
    error.value = null

    try {
        const { data } = await apiClient.post('/user/keywords', { word: newWord.value })
        keywords.value.unshift(data.data)
        newWord.value = ''
        triggerHaptic('success')
    } catch (e) {
        const firstError = e.response?.data?.errors?.[0]
        showAutoHidingError(error, firstError?.title || firstError?.detail || 'An error occurred')
        triggerHaptic('error')
    } finally {
        loading.value = false
    }
}

const deleteKeyword = async (id) => {
    if (!confirm('Remove this filter?')) return
    
    try {
        await apiClient.delete(`/user/keywords/${id}`)
        keywords.value = keywords.value.filter(k => k.id !== id)
        triggerHaptic('light')
    } catch (e) {
        triggerHaptic('error')
    }
}

// Banned keywords operations
const addBannedKeyword = async () => {
    if (!newBannedWord.value.trim() || loadingBanned.value) return
    
    loadingBanned.value = true
    errorBanned.value = null
    
    try {
        const { data } = await apiClient.post('/user/banned_keywords', { word: newBannedWord.value })
        bannedKeywords.value.unshift(data.data)
        newBannedWord.value = ''
        triggerHaptic('success')
    } catch (e) {
        const firstError = e.response?.data?.errors?.[0]
        showAutoHidingError(errorBanned, firstError?.title || firstError?.detail || 'An error occurred')
        triggerHaptic('error')
    } finally {
        loadingBanned.value = false
    }
}

const deleteBannedKeyword = async (id) => {
    if (!confirm('Remove this stop word?')) return
    
    try {
        await apiClient.delete(`/user/banned_keywords/${id}`)
        bannedKeywords.value = bannedKeywords.value.filter(k => k.id !== id)
        triggerHaptic('light')
    } catch (e) {
        triggerHaptic('error')
    }
}

onMounted(fetchAll)
</script>

<style scoped>
.list-enter-active, .list-leave-active { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
.list-enter-from { opacity: 0; transform: translateX(-30px) scale(0.95); }
.list-leave-to { opacity: 0; transform: scale(0.9); }

.fade-enter-active, .fade-leave-active { transition: opacity 0.5s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
