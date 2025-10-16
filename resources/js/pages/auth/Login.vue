<template>
  <div class="auth-page">
    <div class="auth-bg-decoration">
      <div class="auth-bg-circle circle-1"></div>
      <div class="auth-bg-circle circle-2"></div>
      <div class="auth-bg-circle circle-3"></div>
    </div>

    <div class="auth-container">
      <div class="auth-branding-section">
        <div class="auth-branding-content">
          <div class="branding-text">
            <h1 class="auth-brand-title">Judy Home HealthCare</h1>
            <p class="auth-brand-subtitle">Compassionate Care at Home</p>
            <div class="auth-features">
              <div class="auth-feature">
                <div class="auth-feature-icon">
                  <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2L2 7v10c0 5.55 3.84 9.739 9 11 5.16-1.261 9-5.45 9-11V7l-10-5z"/>
                  </svg>
                </div>
                <span>Secure & HIPAA Compliant</span>
              </div>
              <div class="auth-feature">
                <div class="auth-feature-icon">
                  <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                  </svg>
                </div>
                <span>Professional Healthcare Portal</span>
              </div>
              <div class="auth-feature">
                <div class="auth-feature-icon">
                  <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                  </svg>
                </div>
                <span>24/7 Support Available</span>
              </div>
              <div class="auth-feature">
                <div class="auth-feature-icon">
                  <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                  </svg>
                </div>
                <span>Quality Care Guaranteed</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="auth-form-section">
        <div class="auth-form-container">
          <div class="auth-form-header">
            <div class="auth-form-logo-container">
              <img 
                src="../images/judy_logo.jpg" 
                alt="Judy Home Care" 
                class="auth-logo"
              >
            </div>
            <h2 class="auth-form-title">Welcome Back</h2>
            <p class="auth-form-subtitle">Sign in to access your healthcare portal</p>
          </div>

          <div v-if="generalError" class="error-alert">
            <div class="alert-icon">
              <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/>
              </svg>
            </div>
            <div class="alert-content">
              <h4>Login Failed</h4>
              <p>{{ generalError }}</p>
            </div>
          </div>

          <form @submit.prevent="handleLogin" class="auth-form">
            <div class="auth-form-group">
              <label for="email" class="auth-form-label">Email Address</label>
              <div class="auth-input-wrapper">
                <div class="auth-input-icon">
                  <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                  </svg>
                </div>
                <input
                  id="email"
                  v-model="formData.email"
                  type="email"
                  class="auth-form-input"
                  :class="{ 'error': errors.email }"
                  placeholder="Enter your email address"
                  required
                  :disabled="isLoading"
                  autocomplete="email"
                />
              </div>
              <span v-if="errors.email" class="auth-error-message">{{ errors.email }}</span>
            </div>

            <div class="auth-form-group">
              <label for="password" class="auth-form-label">Password</label>
              <div class="auth-input-wrapper">
                <div class="auth-input-icon">
                  <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>
                  </svg>
                </div>
                <input
                  id="password"
                  v-model="formData.password"
                  :type="showPassword ? 'text' : 'password'"
                  class="auth-form-input"
                  :class="{ 'error': errors.password }"
                  placeholder="Enter your password"
                  required
                  :disabled="isLoading"
                  autocomplete="current-password"
                />
                <button
                  type="button"
                  class="auth-input-addon"
                  @click="showPassword = !showPassword"
                  :disabled="isLoading"
                >
                  <svg v-if="showPassword" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/>
                  </svg>
                  <svg v-else viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                  </svg>
                </button>
              </div>
              <span v-if="errors.password" class="auth-error-message">{{ errors.password }}</span>
            </div>

            <div class="auth-form-options">
              <label class="auth-checkbox-wrapper">
                <input type="checkbox" v-model="formData.rememberMe" :disabled="isLoading" />
                <span class="checkbox-label">Remember me for 30 days</span>
              </label>
              <a href="#" class="auth-link" @click.prevent="handleForgotPassword">
                Forgot Password?
              </a>
            </div>

            <button 
              type="submit" 
              class="auth-btn"
              :disabled="isLoading"
            >
              <span v-if="!isLoading" class="auth-btn-content">
                <svg viewBox="0 0 24 24" fill="currentColor">
                  <path d="M10 17l5-5-5-5v3H3v4h7v3z"/>
                </svg>
                Sign In to Portal
              </span>
              <span v-else class="auth-btn-loading">
                <svg class="auth-spinner" viewBox="0 0 24 24">
                  <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"/>
                </svg>
                Signing In...
              </span>
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

const formData = reactive({
  email: '',
  password: '',
  rememberMe: false
})

const errors = reactive({
  email: '',
  password: ''
})

const isLoading = ref(false)
const showPassword = ref(false)
const generalError = ref('')

const validateForm = () => {
  errors.email = ''
  errors.password = ''
  generalError.value = ''
  
  let isValid = true
  
  if (!formData.email) {
    errors.email = 'Email is required'
    isValid = false
  } else if (!/\S+@\S+\.\S+/.test(formData.email)) {
    errors.email = 'Please enter a valid email address'
    isValid = false
  }
  
  if (!formData.password) {
    errors.password = 'Password is required'
    isValid = false
  } else if (formData.password.length < 6) {
    errors.password = 'Password must be at least 6 characters'
    isValid = false
  }
  
  return isValid
}

// Helper function to get cookie value by name
const getCookie = (name) => {
  const value = `; ${document.cookie}`;
  const parts = value.split(`; ${name}=`);
  if (parts.length === 2) {
    return parts.pop().split(';').shift();
  }
  return null;
}

const handleLogin = async () => {
  console.log('Login attempt started')
  
  if (!validateForm()) {
    console.log('Validation failed')
    return
  }
  
  isLoading.value = true
  generalError.value = ''
  
  try {
    // Step 1: Get CSRF cookie
    console.log('Getting CSRF token...')
    await fetch('/sanctum/csrf-cookie', {
      method: 'GET',
      credentials: 'include',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      }
    })
    console.log('CSRF token obtained')

    // Step 2: Read the XSRF-TOKEN cookie
    const csrfToken = getCookie('XSRF-TOKEN');
    console.log('CSRF Token from cookie:', csrfToken ? 'Found' : 'Not found');

    if (!csrfToken) {
      console.error('CSRF token not found in cookies')
      generalError.value = 'Security token not found. Please refresh the page and try again.'
      isLoading.value = false
      return
    }

    // Step 3: Send login request with CSRF token in header
    console.log('Sending login request with CSRF token...')
    const response = await fetch('/auth/login', {
      method: 'POST',
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-Client-Type': 'web',
        'X-XSRF-TOKEN': decodeURIComponent(csrfToken), // CRITICAL: Send decoded CSRF token
      },
      body: JSON.stringify({
        email: formData.email,
        password: formData.password,
        remember_me: formData.rememberMe
      })
    })

    console.log('Response status:', response.status)
    const data = await response.json()
    console.log('Response data:', data)

    if (!response.ok) {
      if (response.status === 422 && data.errors) {
        if (data.errors.email) {
          errors.email = Array.isArray(data.errors.email) ? data.errors.email[0] : data.errors.email
        }
        if (data.errors.password) {
          errors.password = Array.isArray(data.errors.password) ? data.errors.password[0] : data.errors.password
        }
      } else if (response.status === 419) {
        // CSRF token mismatch
        generalError.value = 'Session expired. Please refresh the page and try again.'
      } else {
        generalError.value = data.message || 'Login failed. Please check your credentials.'
      }
      isLoading.value = false
      return
    }

    if (data.success && data.data) {
      console.log('Login successful, redirecting...')
      // Small delay to ensure session is fully established
      setTimeout(() => {
        router.push(data.data.redirect_to || '/dashboard')
      }, 100)
    } else {
      generalError.value = data.message || 'Login failed. Please try again.'
      isLoading.value = false
    }
    
  } catch (error) {
    console.error('Login error:', error)
    generalError.value = 'Network error. Please check your connection and try again.'
    isLoading.value = false
  }
}

const handleForgotPassword = () => {
  if (isLoading.value) return
  router.push('/forgot-password')
}
</script>

<style scoped>
.auth-form-options {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  font-size: 0.875rem;
}

.auth-checkbox-wrapper {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.checkbox-label {
  color: #6b7280;
}

.auth-link {
  color: #667eea;
  text-decoration: none;
  font-weight: 500;
}

.auth-link:hover {
  text-decoration: underline;
}

/* Form logo styling */
.auth-form-logo-container {
  text-align: center;
  margin-bottom: 1rem;
  display: flex;
  justify-content: center;
  align-items: center;
}

.auth-logo {
  height: 160px;
  max-width: 200px;
  object-fit: contain;
  filter: none;
}
</style>