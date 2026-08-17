import { createApp } from 'vue'
import { createPinia } from 'pinia'
import PrimeVue from 'primevue/config'
import Aura from '@primeuix/themes/aura'
import './style.css'
import App from './App.vue'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(PrimeVue, {
      theme: {
            preset: Aura,
            options: {
                  prefix: 'p',
                  darkModeSelector: 'system',
                  cssLayer: false
            }
      }
})

app.mount('#app')
