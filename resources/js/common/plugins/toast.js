// common/plugins/toast.js
import { useToast } from '../composables/useToast.js'

export default {
  install(app) {
    const toast = useToast()
    
    // Provide toast functionality globally
    app.provide('toast', toast)
    
    // Also add to global properties for easier access
    app.config.globalProperties.$toast = toast
  }
}