<template>
  <div class="auth-page">
    <!-- Background Elements -->
    <div class="auth-bg-decoration">
      <div class="auth-bg-circle circle-1"></div>
      <div class="auth-bg-circle circle-2"></div>
      <div class="auth-bg-circle circle-3"></div>
    </div>

    <!-- Main Container -->
    <div class="auth-container">
      
      <!-- Left Side - Branding -->
      <div class="auth-branding-section">
        <div class="auth-branding-content">
          <!-- Logo -->
          <div class="auth-logo-container">
            <img 
              src="https://www.mnotify.com/assets/mnotify_04_black-BzCtWTKE.webp" 
              alt="Judy Home Care" 
              class="auth-logo"
            >
          </div>
          
          <!-- Branding Text -->
          <div class="branding-text">
            <h1 class="auth-brand-title">Create New Password</h1>
            <p class="auth-brand-subtitle">Secure Your Healthcare Account</p>
            <div class="auth-features">
              <div class="auth-feature">
                <div class="auth-feature-icon">
                  <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 1L3 5v6c0 5.55 3.84 9.739 9 11 5.16-1.261 9-5.45 9-11V5l-9-4z"/>
                  </svg>
                </div>
                <span>Strong Password Protection</span>
              </div>
              <div class="auth-feature">
                <div class="auth-feature-icon">
                  <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                </div>
                <span>Verified Reset Token</span>
              </div>
              <div class="auth-feature">
                <div class="auth-feature-icon">
                  <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                  </svg>
                </div>
                <span>Enhanced Security Standards</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Side - Reset Form -->
      <div class="auth-form-section">
        <div class="auth-form-container">

          <!-- Valid Token State -->
          <div v-if="!isTokenExpired && !isSuccess" class="auth-form-state">
            <div class="auth-form-header">
              <div class="auth-reset-icon">
                <svg viewBox="0 0 24 24" fill="currentColor">
                  <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
              </div>
              <h2 class="auth-form-title">Reset Your Password</h2>
              <p class="auth-form-subtitle">
                Create a new password for <strong>{{ userEmail }}</strong>
              </p>
            </div>

            <form @submit.prevent="handlePasswordReset" class="auth-form">
              
              <!-- New Password Field -->
              <div class="auth-form-group">
                <label for="password" class="auth-form-label">New Password</label>
                <div class="auth-input-wrapper">
                  <div class="auth-input-icon">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                      <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                  </div>
                  <input
                    id="password"
                    v-model="formData.password"
                    :type="showPassword ? 'text' : 'password'"
                    class="auth-form-input"
                    :class="{ 'error': errors.password }"
                    placeholder="Enter your new password"
                    required
                    @input="validatePassword"
                  >
                  <button
                    type="button"
                    class="auth-input-addon"
                    @click="showPassword = !showPassword"
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

                <!-- Password Strength Indicator -->
                <div class="auth-password-strength">
                  <div class="auth-strength-label">Password Strength:</div>
                  <div class="auth-strength-bar">
                    <div 
                      class="auth-strength-fill" 
                      :class="passwordStrength.class"
                      :style="{ width: passwordStrength.width }"
                    ></div>
                  </div>
                  <span class="auth-strength-text" :class="passwordStrength.class">
                    {{ passwordStrength.text }}
                  </span>
                </div>
              </div>

              <!-- Confirm Password Field -->
              <div class="auth-form-group">
                <label for="confirmPassword" class="auth-form-label">Confirm New Password</label>
                <div class="auth-input-wrapper">
                  <div class="auth-input-icon">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                      <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                  </div>
                  <input
                    id="confirmPassword"
                    v-model="formData.confirmPassword"
                    :type="showConfirmPassword ? 'text' : 'password'"
                    class="auth-form-input"
                    :class="{ 'error': errors.confirmPassword }"
                    placeholder="Confirm your new password"
                    required
                    @input="validateConfirmPassword"
                  >
                  <button
                    type="button"
                    class="auth-input-addon"
                    @click="showConfirmPassword = !showConfirmPassword"
                  >
                    <svg v-if="showConfirmPassword" viewBox="0 0 24 24" fill="currentColor">
                      <path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/>
                    </svg>
                    <svg v-else viewBox="0 0 24 24" fill="currentColor">
                      <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                    </svg>
                  </button>
                </div>
                <span v-if="errors.confirmPassword" class="auth-error-message">{{ errors.confirmPassword }}</span>
                <span v-if="isPasswordMatch && formData.confirmPassword" class="auth-success-message">
                  <svg viewBox="0 0 24 24" fill="currentColor" class="auth-match-icon">
                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                  Passwords match
                </span>
              </div>

              <!-- Password Requirements -->
              <div class="auth-password-requirements">
                <h4 class="auth-requirements-title">Password Requirements:</h4>
                <ul class="auth-requirements-list">
                  <li :class="{ 'met': requirements.length }">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                      <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    At least 8 characters long
                  </li>
                  <li :class="{ 'met': requirements.uppercase }">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                      <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Contains uppercase letter
                  </li>
                  <li :class="{ 'met': requirements.lowercase }">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                      <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Contains lowercase letter
                  </li>
                  <li :class="{ 'met': requirements.number }">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                      <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Contains a number
                  </li>
                  <li :class="{ 'met': requirements.special }">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                      <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Contains special character
                  </li>
                </ul>
              </div>

              <!-- Submit Button -->
              <button 
                type="submit" 
                class="auth-btn"
                :class="{ 'loading': isLoading }"
                :disabled="isLoading || !isFormValid"
              >
                <span v-if="!isLoading" class="auth-btn-content">
                  <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                  Update Password
                </span>
                <span v-else class="auth-btn-loading">
                  <svg class="auth-spinner" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-dasharray="31.416" stroke-dashoffset="31.416">
                      <animate attributeName="stroke-dasharray" dur="2s" values="0 31.416;15.708 15.708;0 31.416" repeatCount="indefinite"/>
                      <animate attributeName="stroke-dashoffset" dur="2s" values="0;-15.708;-31.416" repeatCount="indefinite"/>
                    </circle>
                  </svg>
                  Updating Password...
                </span>
              </button>

              <!-- Token Expiry Warning -->
              <div class="auth-info-box">
                <div class="auth-info-header">
                  <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>
                  </svg>
                  <span>Security Notice</span>
                </div>
                <p class="auth-security-text">
                  This reset link will expire in {{ formatTimeRemaining() }} for security purposes. 
                  Please complete your password reset promptly.
                </p>
              </div>
            </form>
          </div>

          <!-- Success State -->
          <div v-else-if="isSuccess" class="auth-form-state">
            <div class="auth-form-header">
              <div class="success-icon">
                <svg viewBox="0 0 24 24" fill="currentColor">
                  <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
              </div>
              <h2 class="auth-form-title">Password Updated Successfully!</h2>
              <p class="auth-form-subtitle">
                Your password has been updated for <strong>{{ userEmail }}</strong>
              </p>
            </div>

            <div class="auth-success-content">
              <div class="auth-success-message">
                <h4>What's Next?</h4>
                <p>You can now sign in to your Judy Home Care portal with your new password.</p>
              </div>

              <button 
                type="button" 
                class="auth-btn"
                @click="goToLogin"
              >
                <svg viewBox="0 0 24 24" fill="currentColor">
                  <path d="M10 17l5-5-5-5v3H3v4h7v3z"/>
                </svg>
                Sign In Now
              </button>

              <div class="auth-info-box">
                <div class="auth-info-header">
                  <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 1L3 5v6c0 5.55 3.84 9.739 9 11 5.16-1.261 9-5.45 9-11V5l-9-4z"/>
                  </svg>
                  <span>Security Tip</span>
                </div>
                <p class="auth-security-text">
                  Keep your password secure and don't share it with anyone. 
                  Consider using a password manager for enhanced security.
                </p>
              </div>
            </div>
          </div>

          <!-- Expired Token State -->
          <div v-else class="auth-form-state">
            <div class="auth-form-header">
              <div class="error-icon">
                <svg viewBox="0 0 24 24" fill="currentColor">
                  <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                </svg>
              </div>
              <h2 class="auth-form-title">Reset Link Expired</h2>
              <p class="auth-form-subtitle">
                This password reset link has expired or is invalid.
              </p>
            </div>

            <div class="auth-expired-content">
              <p class="auth-expired-message">
                For security reasons, password reset links expire after 15 minutes. 
                Please request a new password reset link.
              </p>

              <button 
                type="button" 
                class="auth-btn"
                @click="goToForgotPassword"
              >
                <svg viewBox="0 0 24 24" fill="currentColor">
                  <path d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                Request New Reset Link
              </button>

              <div class="auth-bottom-section">
                <p class="auth-bottom-text">
                  Remember your password?
                  <a href="#" class="auth-link" @click.prevent="goToLogin">
                    Back to Login
                  </a>
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'

const router = useRouter()
const route = useRoute()

// Reactive data
const formData = reactive({
  password: '',
  confirmPassword: ''
})

const errors = reactive({
  password: '',
  confirmPassword: ''
})

const isLoading = ref(false)
const isSuccess = ref(false)
const isTokenExpired = ref(false)
const showPassword = ref(false)
const showConfirmPassword = ref(false)
const tokenExpiresAt = ref(Date.now() + 15 * 60 * 1000) // 15 minutes from now
const userEmail = ref('')

// Password requirements tracking
const requirements = reactive({
  length: false,
  uppercase: false,
  lowercase: false,
  number: false,
  special: false
})

// Computed properties
const passwordStrength = computed(() => {
  const metRequirements = Object.values(requirements).filter(Boolean).length
  
  if (metRequirements === 0) return { width: '0%', class: '', text: '' }
  if (metRequirements <= 2) return { width: '25%', class: 'weak', text: 'Weak' }
  if (metRequirements <= 3) return { width: '50%', class: 'fair', text: 'Fair' }
  if (metRequirements <= 4) return { width: '75%', class: 'good', text: 'Good' }
  return { width: '100%', class: 'strong', text: 'Strong' }
})

const isPasswordMatch = computed(() => {
  return formData.password && formData.confirmPassword && formData.password === formData.confirmPassword
})

const isFormValid = computed(() => {
  return Object.values(requirements).every(Boolean) && 
         isPasswordMatch.value && 
         !errors.password && 
         !errors.confirmPassword
})

// Methods
const validatePassword = () => {
  const password = formData.password
  
  // Update requirements
  requirements.length = password.length >= 8
  requirements.uppercase = /[A-Z]/.test(password)
  requirements.lowercase = /[a-z]/.test(password)
  requirements.number = /\d/.test(password)
  requirements.special = /[!@#$%^&*(),.?":{}|<>]/.test(password)
  
  // Clear previous errors
  errors.password = ''
  
  // Validate confirm password if it exists
  if (formData.confirmPassword) {
    validateConfirmPassword()
  }
}

const validateConfirmPassword = () => {
  errors.confirmPassword = ''
  
  if (formData.confirmPassword && formData.password !== formData.confirmPassword) {
    errors.confirmPassword = 'Passwords do not match'
  }
}

const validateResetToken = () => {
  // Check if token exists in URL
  const token = route.query.token
  const email = route.query.email
  
  if (!token || !email) {
    isTokenExpired.value = true
    return false
  }
  
  // Set user email
  userEmail.value = email
  
  // Check if token is expired (simulate)
  // In real app, you'd validate this with your backend
  const tokenAge = Date.now() - (parseInt(route.query.timestamp) || Date.now())
  if (tokenAge > 15 * 60 * 1000) { // 15 minutes
    isTokenExpired.value = true
    return false
  }
  
  return true
}

const formatTimeRemaining = () => {
  const now = Date.now()
  const remaining = Math.max(0, tokenExpiresAt.value - now)
  const minutes = Math.floor(remaining / 60000)
  const seconds = Math.floor((remaining % 60000) / 1000)
  
  if (minutes > 0) {
    return `${minutes} minute${minutes > 1 ? 's' : ''}`
  }
  return `${seconds} second${seconds > 1 ? 's' : ''}`
}

const handlePasswordReset = async () => {
  if (!isFormValid.value) return
  
  isLoading.value = true
  
  try {
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 2000))
    
    console.log('Password reset successful for:', userEmail.value)
    isSuccess.value = true
    
  } catch (error) {
    console.error('Password reset failed:', error)
    errors.password = 'Failed to update password. Please try again.'
  } finally {
    isLoading.value = false
  }
}

const goToLogin = () => {
  router.push('/login')
}

const goToForgotPassword = () => {
  router.push('/forgot-password')
}

// Lifecycle
onMounted(() => {
  validateResetToken()
  
  // Start timer to check token expiry
  const interval = setInterval(() => {
    if (Date.now() >= tokenExpiresAt.value) {
      isTokenExpired.value = true
      clearInterval(interval)
    }
  }, 1000)
})
</script>
