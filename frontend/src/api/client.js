import axios from 'axios'

const apiClient = axios.create({
      baseURL: '/api/v1',
      headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
      }
})

// Request interceptor to add Telegram InitData
apiClient.interceptors.request.use(config => {
      const initData = window.Telegram?.WebApp?.initData

      if (initData) {
            config.headers['X-Telegram-Init-Data'] = initData
      }

      return config
})

// Response interceptor for error handling
apiClient.interceptors.response.use(
      response => response,
      error => {
            console.error('API Error:', error.response?.data || error.message)
            return Promise.reject(error)
      }
)

export default apiClient

