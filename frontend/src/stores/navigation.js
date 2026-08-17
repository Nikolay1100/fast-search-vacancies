import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useNavigationStore = defineStore('navigation', () => {
      const currentTab = ref('dashboard')

      const tabs = [
            { id: 'dashboard', label: 'Dashboard', icon: 'LayoutDashboardIcon' },
            { id: 'archive', label: 'Archive', icon: 'HistoryIcon' },
            { id: 'keywords', label: 'Keywords', icon: 'KeyIcon' },
            { id: 'channels', label: 'Channels', icon: 'LayersIcon' },
            { id: 'plans', label: 'Premium', icon: 'CrownIcon' }
      ]

      function setTab(tabId) {
            currentTab.value = tabId
      }

      return {
            currentTab,
            tabs,
            setTab
      }
})
