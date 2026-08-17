<template>
  <div class="space-y-8 pb-32 animate-in fade-in duration-500">
    <!-- Header -->
    <div class="text-center space-y-4 py-6">
        <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-amber-400 to-orange-400 text-white rounded-full shadow-[0_10px_30px_rgba(245,158,11,0.3)] mb-2 relative">
            <CrownIcon :size="36" stroke-width="2.5" class="relative z-10" />
        </div>
        <h2 class="text-3xl font-bold text-tg-text tracking-tight font-mono">Unlock Limitless</h2>
        <p class="text-sm text-tg-hint font-medium max-w-[280px] mx-auto leading-relaxed">
            Instantly track dozens of keywords across exclusive channels without restrictions. Find your dream job faster.
        </p>
    </div>

    <!-- Error/Success Toasts -->
    <transition name="fade">
        <div v-if="error" class="glass-panel border-red-500/20 bg-red-500/10 text-red-500 p-4 rounded-2xl shadow-sm text-xs font-bold text-center mx-2">
            {{ error }}
        </div>
    </transition>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center py-12">
        <Loader2Icon class="animate-spin text-amber-500" :size="32" />
    </div>

    <!-- Plans Grid -->
    <div v-else class="space-y-5 px-2">
        <div 
            v-for="plan in plans" 
            :key="plan.id"
            class="relative glass-panel p-8 rounded-[32px] transition-all duration-300 group overflow-hidden border"
            :class="isBestValue(plan) ? 'border-amber-400 border-2 scale-[1.02] shadow-[0_20px_40px_rgba(245,158,11,0.1)]' : 'border-glass-border hover:border-tg-button/50 hover:scale-[1.02]'"
        >
            <!-- Ambient Glow for Best Value -->
            <div v-if="isBestValue(plan)" class="absolute -right-10 -top-10 w-40 h-40 bg-amber-500/10 blur-[40px] rounded-full pointer-events-none group-hover:scale-125 transition-transform duration-700"></div>
            
            <!-- Best Value Badge -->
            <div v-if="isBestValue(plan)" class="absolute -top-3 left-1/2 -translate-x-1/2 bg-gradient-to-r from-amber-400 to-orange-500 text-white text-[9px] font-bold uppercase tracking-widest px-4 py-1.5 rounded-full shadow-md z-20">
                Best Value
            </div>

            <div class="flex justify-between items-start mb-6">
                <div class="space-y-1 relative z-10">
                    <h3 class="text-xl font-bold text-tg-text" :class="isBestValue(plan) ? 'text-amber-500 soft-glow-text' : ''">{{ plan.name }}</h3>
                    <p class="text-xs text-tg-hint font-medium max-w-[200px]">{{ getPlanDescription(plan.id) }}</p>
                </div>
                <div class="text-right flex flex-col items-end relative z-10">
                    <span class="text-3xl font-bold text-tg-text tracking-tighter">{{ plan.price }}<span class="text-sm text-tg-hint font-medium ml-1">₽</span></span>
                </div>
            </div>

            <button 
                @click="purchase(plan.id)"
                :disabled="purchasingId === plan.id"
                class="w-full mt-2 py-4 text-[11px] font-bold uppercase tracking-widest transition-all duration-300 active:scale-95 flex justify-center items-center gap-2 cursor-pointer relative overflow-hidden group/btn z-10 rounded-full"
                :class="isBestValue(plan) ? 'bg-gradient-to-r from-amber-400 to-orange-500 text-white shadow-md' : 'glass-panel border-glass-border shadow-sm hover:bg-tg-button hover:text-white hover:border-tg-button'"
            >
                <!-- Shimmer Effect -->
                <div v-if="isBestValue(plan)" class="absolute inset-0 -translate-x-full w-12 bg-white/30 skew-x-12 group-hover/btn:animate-[shimmer_1.5s_infinite]"></div>

                <Loader2Icon v-if="purchasingId === plan.id" class="animate-spin relative z-10" :size="16" />
                <span class="relative z-10">{{ purchasingId === plan.id ? 'Processing...' : 'Purchase Plan' }}</span>
                <ChevronRightIcon v-if="purchasingId !== plan.id" :size="16" class="relative z-10" />
            </button>
        </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import apiClient from '../api/client'
import { CrownIcon, Loader2Icon, ChevronRightIcon } from 'lucide-vue-next'

const plans = ref([])
const loading = ref(true)
const purchasingId = ref(null)
const error = ref(null)

const descriptions = {
    '1 Week': 'Perfect for a quick job search sprint.',
    '2 Weeks': 'Solid choice for active job hunting.',
    '1 Month': 'Maximum value. Full access for a whole month.'
}

const getPlanDescription = (id) => {
    const plan = plans.value.find(p => p.id === id)
    return plan ? descriptions[plan.name] || 'Unlock premium features.' : ''
}

const isBestValue = (plan) => plan.name === '1 Month'

const triggerHaptic = (type = 'light') => {
    if (window.Telegram?.WebApp?.HapticFeedback) {
        if (type === 'error') window.Telegram.WebApp.HapticFeedback.notificationOccurred('error')
        else window.Telegram.WebApp.HapticFeedback.impactOccurred(type)
    }
}

const fetchPlans = async () => {
    try {
        const { data } = await apiClient.get('/user/plans')
        plans.value = data.data
    } catch (e) {
        error.value = 'Failed to load subscription plans. Please try again.'
    } finally {
        loading.value = false
    }
}

const purchase = async (planId) => {
    if (purchasingId.value) return
    purchasingId.value = planId
    error.value = null
    triggerHaptic('light')

    try {
        const { data } = await apiClient.post(`/user/plans/${planId}/purchase`)
        
        if (data.data?.payment_url) {
            triggerHaptic('heavy')
            if (window.Telegram?.WebApp) {
                window.Telegram.WebApp.openLink(data.data.payment_url)
            } else {
                window.open(data.data.payment_url, '_blank')
            }
        }
    } catch (e) {
        error.value = e.response?.data?.message || 'Failed to initialize payment. Please try again later.'
        triggerHaptic('error')
    } finally {
        purchasingId.value = null
    }
}

onMounted(fetchPlans)
</script>

<style scoped>
@keyframes fade-in {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes shimmer {
  100% { transform: translateX(150%); }
}

.animate-in {
  animation: fade-in 0.6s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
}

.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
