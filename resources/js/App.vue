<template>
  <div id="app">
    <!-- Router View - This will render the current page component -->
    <router-view />
    
    <!-- Global Loading Spinner -->
    <!-- <div v-if="isLoading" class="global-loading">
      <div class="loading-spinner">
        <div class="spinner"></div>
        <p>Loading...</p>
      </div>
    </div> -->

    <!-- New Toast Component -->
    <Toast />
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import Toast from './common/components/Toast.vue'

const router = useRouter()

// Global loading state
const isLoading = ref(false)

// Watch for route changes to show loading
watch(() => router.currentRoute.value, (to, from) => {
  if (to.name !== from.name) {
    isLoading.value = true
    // Hide loading after a short delay (you can remove this if handling loading in components)
    setTimeout(() => {
      isLoading.value = false
    }, 800)
  }
})

// Global error handling
const handleGlobalError = (error) => {
  console.error('Global error:', error)
  // You can now use the global toast system for errors if needed
}

// Setup global error handlers
onMounted(() => {
  // Handle unhandled promise rejections
  window.addEventListener('unhandledrejection', (event) => {
    handleGlobalError(event.reason)
  })

  // Handle JavaScript errors
  window.addEventListener('error', (event) => {
    handleGlobalError(event.error)
  })

  // Check authentication on app startup
  checkAuthStatus()
})

const checkAuthStatus = () => {
  const token = localStorage.getItem('auth_token')
  const currentRoute = router.currentRoute.value

  // If no token and on a protected route, redirect to login
  if (!token && currentRoute.meta.requiresAuth) {
    router.push('/login')
  }
  // If token exists but no user data, fetch it
  else if (token && !localStorage.getItem('user_data')) {
    fetchUserData()
  }
}

const fetchUserData = async () => {
  try {
    // Replace with your actual API endpoint
    const response = await fetch('/api/user/profile', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Content-Type': 'application/json'
      }
    })

    if (response.ok) {
      const userData = await response.json()
      localStorage.setItem('user_data', JSON.stringify(userData))
    } else {
      // Invalid token, redirect to login
      localStorage.removeItem('auth_token')
      localStorage.removeItem('user_data')
      router.push('/login')
    }
  } catch (error) {
    console.error('Failed to fetch user data:', error)
  }
}
</script>

<style>
/* Global Styles */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

html, body {
  height: 100%;
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', sans-serif;
  background: #f8f9fa;
  color: #1f2937;
  line-height: 1.6;
}

#app {
  height: 100vh;
  overflow: hidden;
}

/* Global Loading Spinner */
.global-loading {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.9);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  backdrop-filter: blur(4px);
}

.loading-spinner {
  text-align: center;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 4px solid #e5e7eb;
  border-top: 4px solid #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem auto;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.loading-spinner p {
  color: #6b7280;
  font-weight: 500;
}

/* Utility Classes */
.text-center {
  text-align: center;
}

.text-left {
  text-align: left;
}

.text-right {
  text-align: right;
}

.font-bold {
  font-weight: 700;
}

.font-semibold {
  font-weight: 600;
}

.font-medium {
  font-weight: 500;
}

.text-sm {
  font-size: 0.875rem;
}

.text-xs {
  font-size: 0.75rem;
}

.text-lg {
  font-size: 1.125rem;
}

.text-xl {
  font-size: 1.25rem;
}

.text-2xl {
  font-size: 1.5rem;
}

.mb-0 { margin-bottom: 0; }
.mb-1 { margin-bottom: 0.25rem; }
.mb-2 { margin-bottom: 0.5rem; }
.mb-3 { margin-bottom: 0.75rem; }
.mb-4 { margin-bottom: 1rem; }
.mb-6 { margin-bottom: 1.5rem; }
.mb-8 { margin-bottom: 2rem; }

.mt-0 { margin-top: 0; }
.mt-1 { margin-top: 0.25rem; }
.mt-2 { margin-top: 0.5rem; }
.mt-3 { margin-top: 0.75rem; }
.mt-4 { margin-top: 1rem; }
.mt-6 { margin-top: 1.5rem; }
.mt-8 { margin-top: 2rem; }

.p-0 { padding: 0; }
.p-1 { padding: 0.25rem; }
.p-2 { padding: 0.5rem; }
.p-3 { padding: 0.75rem; }
.p-4 { padding: 1rem; }
.p-6 { padding: 1.5rem; }
.p-8 { padding: 2rem; }

/* Responsive Design */
@media (max-width: 640px) {
  /* Your responsive styles here */
}

/* Focus styles for accessibility */
button:focus,
input:focus,
select:focus,
textarea:focus,
a:focus {
  outline: 2px solid #667eea;
  outline-offset: 2px;
}

/* Smooth transitions */
* {
  transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out;
}
</style>