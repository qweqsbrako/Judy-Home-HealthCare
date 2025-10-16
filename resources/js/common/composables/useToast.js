// common/composables/useToast.js
import { ref, reactive } from 'vue'

// Global toast state
const toasts = ref([])
let toastIdCounter = 0

// Default configurations for different toast types
const defaultConfig = {
  success: {
    duration: 4000,
    title: 'Success'
  },
  error: {
    duration: 6000,
    title: 'Error'
  },
  warning: {
    duration: 5000,
    title: 'Warning'
  },
  info: {
    duration: 4000,
    title: 'Info'
  }
}

export function useToast() {
  const addToast = (options) => {
    const id = ++toastIdCounter
    const config = defaultConfig[options.type] || defaultConfig.info
    
    const toast = {
      id,
      type: options.type || 'info',
      title: options.title || config.title,
      message: options.message || '',
      duration: options.duration !== undefined ? options.duration : config.duration,
      dismissing: false,
      createdAt: Date.now()
    }

    toasts.value.push(toast)

    // Auto-dismiss if duration is set
    if (toast.duration && toast.duration > 0) {
      setTimeout(() => {
        dismissToast(id)
      }, toast.duration)
    }

    return id
  }

  const dismissToast = (id) => {
    const index = toasts.value.findIndex(toast => toast.id === id)
    if (index > -1) {
      // Mark as dismissing for animation
      toasts.value[index].dismissing = true
      
      // Remove after animation completes
      setTimeout(() => {
        const currentIndex = toasts.value.findIndex(toast => toast.id === id)
        if (currentIndex > -1) {
          toasts.value.splice(currentIndex, 1)
        }
      }, 300) // Match the CSS transition duration
    }
  }

  const clearAllToasts = () => {
    toasts.value.forEach(toast => {
      dismissToast(toast.id)
    })
  }

  // Convenience methods for different toast types
  const showSuccess = (message, options = {}) => {
    return addToast({
      type: 'success',
      message,
      ...options
    })
  }

  const showError = (message, options = {}) => {
    return addToast({
      type: 'error',
      message,
      ...options
    })
  }

  const showWarning = (message, options = {}) => {
    return addToast({
      type: 'warning',
      message,
      ...options
    })
  }

  const showInfo = (message, options = {}) => {
    return addToast({
      type: 'info',
      message,
      ...options
    })
  }

  // Method to show a toast for API responses
  const showApiResponse = (response, successMessage = 'Operation completed successfully') => {
    if (response?.success) {
      showSuccess(response.message || successMessage)
    } else {
      showError(response?.message || 'An error occurred. Please try again.')
    }
  }

  // Method to show error for caught exceptions
  const showApiError = (error, fallbackMessage = 'An unexpected error occurred') => {
    console.error('API Error:', error)
    
    let message = fallbackMessage
    
    if (error?.response?.data?.message) {
      message = error.response.data.message
    } else if (error?.message) {
      message = error.message
    }

    showError(message, {
      duration: 6000 // Longer duration for errors
    })
  }

  return {
    // State
    toasts,
    
    // Methods
    addToast,
    dismissToast,
    clearAllToasts,
    
    // Convenience methods
    showSuccess,
    showError,
    showWarning,
    showInfo,
    
    // API helper methods
    showApiResponse,
    showApiError
  }
}