import { defineStore } from 'pinia'
import { ref } from 'vue'
import apiClient from '../api/client'

export const useUserStore = defineStore('user', () => {
    const profile = ref(null)
    const loading = ref(false)
    const error = ref(null)

    const isPremium = () => {
        return profile.value?.is_premium === true
    }

    const fetchUser = async () => {
        if (loading.value) return
        loading.value = true
        error.value = null
        try {
            const { data } = await apiClient.get('/user')
            profile.value = data
        } catch (e) {
            error.value = 'Failed to load user profile'
            console.error(e)
        } finally {
            loading.value = false
        }
    }

    return {
        profile,
        loading,
        error,
        isPremium,
        fetchUser
    }
})
