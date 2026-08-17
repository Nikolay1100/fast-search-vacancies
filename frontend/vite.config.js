import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  server: {
    host: true,
    port: 5173,
    allowedHosts: true,
    watch: {
      usePolling: true,
    },
    hmr: {
      clientPort: 443,
    },
    proxy: {
      '/api': {
        target: 'http://backend',
        changeOrigin: true,
      }
    }
  }
})
