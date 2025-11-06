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
            <!-- <img 
              src="https://www.mnotify.com/assets/mnotify_04_black-BzCtWTKE.webp" 
              alt="Judy Home Care" 
              class="auth-logo"
            > -->
          </div>
          
          <!-- Branding Text -->
          <div class="branding-text">
            <h1 class="auth-brand-title">Account Recovery</h1>
            <p class="auth-brand-subtitle">Reset Your Password Securely</p>
            <div class="auth-features">
              <div class="auth-feature">
                <div class="auth-feature-icon">
                  <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2L2 7v10c0 5.55 3.84 9.739 9 11 5.16-1.261 9-5.45 9-11V7l-10-5z"/>
                  </svg>
                </div>
                <span>Secure Password Reset</span>
              </div>
              <div class="auth-feature">
                <div class="auth-feature-icon">
                  <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                  </svg>
                </div>
                <span>Email Verification Required</span>
              </div>
              <div class="auth-feature">
                <div class="auth-feature-icon">
                  <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                </div>
                <span>HIPAA Compliant Process</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Side - Reset Form -->
      <div class="auth-form-section">
        <div class="auth-form-container">
          
          <!-- Error Alert -->
          <div v-if="generalError" class="error-alert">
            <div class="alert-icon">
              <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/>
              </svg>
            </div>
            <div class="alert-content">
              <h4>Error</h4>
              <p>{{ generalError }}</p>
            </div>
          </div>

          <!-- Step 1: Request Reset -->
          <div v-if="currentStep === 'request'" class="step-content">
            <div class="auth-form-header">
              <h2 class="auth-form-title">Forgot Password?</h2>
              <p class="auth-form-subtitle">
                Enter your email address and we'll send you a link to reset your password.
              </p>
            </div>

            <form @submit.prevent="handlePasswordReset" class="auth-form">
              
              <!-- Email Field -->
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
                  >
                </div>
                <span v-if="errors.email" class="auth-error-message">{{ errors.email }}</span>
              </div>

              <!-- Submit Button -->
              <button 
                type="submit" 
                class="auth-btn"
                :class="{ 'loading': isLoading }"
                :disabled="isLoading"
              >
                <span v-if="!isLoading" class="auth-btn-content">
                  <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                  </svg>
                  Send Reset Link
                </span>
                <span v-else class="auth-btn-loading">
                  <svg class="auth-spinner" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-dasharray="31.416" stroke-dashoffset="31.416">
                      <animate attributeName="stroke-dasharray" dur="2s" values="0 31.416;15.708 15.708;0 31.416" repeatCount="indefinite"/>
                      <animate attributeName="stroke-dashoffset" dur="2s" values="0;-15.708;-31.416" repeatCount="indefinite"/>
                    </circle>
                  </svg>
                  Sending...
                </span>
              </button>

              <!-- Security Info -->
              <div class="auth-info-box">
                <div class="auth-info-header">
                  <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>
                  </svg>
                  <span>Security Notice</span>
                </div>
                <p class="auth-security-text">
                  For security reasons, we'll only send password reset instructions to verified email addresses. The reset link will expire in 15 minutes.
                </p>
              </div>
            </form>
          </div>

          <!-- Step 2: Email Sent Confirmation -->
          <div v-else-if="currentStep === 'sent'" class="step-content">
            <div class="auth-form-header">
              <div class="success-icon">
                <svg viewBox="0 0 24 24" fill="currentColor">
                  <path d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
              </div>
              <h2 class="auth-form-title">Check Your Email</h2>
              <p class="auth-form-subtitle">
                We've sent password reset instructions to 
                <strong>{{ maskedEmail }}</strong>
              </p>
            </div>

            <div class="auth-email-instructions">
              <div class="auth-instruction-item">
                <div class="auth-instruction-number">1</div>
                <div class="auth-instruction-text">
                  <h4>Check your inbox</h4>
                  <p>Look for an email from Judy Home Care with reset instructions.</p>
                </div>
              </div>
              
              <div class="auth-instruction-item">
                <div class="auth-instruction-number">2</div>
                <div class="auth-instruction-text">
                  <h4>Click the reset link</h4>
                  <p>The link will be valid for 15 minutes for security purposes.</p>
                </div>
              </div>
              
              <div class="auth-instruction-item">
                <div class="auth-instruction-number">3</div>
                <div class="auth-instruction-text">
                  <h4>Create new password</h4>
                  <p>Choose a strong password to protect your healthcare account.</p>
                </div>
              </div>
            </div>

            <!-- Resend Options -->
            <div class="auth-resend-section">
              <p class="auth-resend-text">Didn't receive the email?</p>
              <div class="auth-resend-actions">
                <button 
                  type="button" 
                  class="auth-link"
                  @click="resendEmail"
                  :disabled="isResending || cooldownActive"
                >
                  <svg v-if="isResending" class="auth-spinner small" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-dasharray="31.416" stroke-dashoffset="31.416">
                      <animate attributeName="stroke-dasharray" dur="2s" values="0 31.416;15.708 15.708;0 31.416" repeatCount="indefinite"/>
                      <animate attributeName="stroke-dashoffset" dur="2s" values="0;-15.708;-31.416" repeatCount="indefinite"/>
                    </circle>
                  </svg>
                  {{ 
                    isResending 
                      ? 'Sending...' 
                      : cooldownActive 
                        ? `Resend Email (${cooldownSeconds}s)` 
                        : 'Resend Email' 
                  }}
                </button>
                <span class="auth-separator">•</span>
                <button 
                  type="button" 
                  class="auth-link"
                  @click="changeEmail"
                >
                  Change Email Address
                </button>
              </div>
            </div>

            <!-- Support Contact -->
            <div class="auth-info-box">
              <div class="auth-info-header">
                <svg viewBox="0 0 24 24" fill="currentColor">
                  <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm-5 2.5l.5.5L11 11.5l-1.5-1.5.5-.5L11 8.5l4.5 4.5zm0 0"/>
                </svg>
                <span>Need Help?</span>
              </div>
              <p class="auth-security-text">
                If you continue to experience issues, please contact our IT support team at 
                <a href="mailto:support@judyhomecare.com" class="auth-link">support@judyhomecare.com</a> 
                or call (555) 123-4567.
              </p>
            </div>
          </div>

          <!-- Back to Login -->
          <div class="auth-bottom-section">
            <p class="auth-bottom-text">
              Remember your password?
              <a href="#" class="auth-link" @click.prevent="goBackToLogin">
                Back to Login
              </a>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

// Reactive data
const currentStep = ref('request') // 'request' or 'sent'
const formData = reactive({
  email: ''
})

const errors = reactive({
  email: ''
})

const isLoading = ref(false)
const isResending = ref(false)
const generalError = ref('')
const cooldownActive = ref(false)
const cooldownSeconds = ref(60)
let cooldownInterval = null

// Computed properties
const maskedEmail = computed(() => {
  if (!formData.email) return ''
  const [username, domain] = formData.email.split('@')
  if (!username || !domain) return formData.email
  const maskedUsername = username.charAt(0) + '*'.repeat(Math.max(0, username.length - 2)) + username.charAt(username.length - 1)
  return `${maskedUsername}@${domain}`
})

// Methods
const validateForm = () => {
  // Clear previous errors
  errors.email = ''
  generalError.value = ''
  
  let isValid = true
  
  // Email validation
  if (!formData.email) {
    errors.email = 'Email is required'
    isValid = false
  } else if (!/\S+@\S+\.\S+/.test(formData.email)) {
    errors.email = 'Please enter a valid email address'
    isValid = false
  }
  
  return isValid
}

const handlePasswordReset = async () => {
  if (!validateForm()) return
  
  isLoading.value = true
  generalError.value = ''
  
  try {
    const response = await fetch('/api/auth/forgot-password', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        email: formData.email
      })
    })

    const data = await response.json()

    if (!response.ok) {
      if (response.status === 422 && data.errors) {
        // Validation errors
        if (data.errors.email) {
          errors.email = Array.isArray(data.errors.email) ? data.errors.email[0] : data.errors.email
        }
      } else if (response.status === 429) {
        generalError.value = 'Too many attempts. Please wait before trying again.'
      } else {
        generalError.value = data.message || 'Failed to send reset email. Please try again.'
      }
      return
    }

    if (data.success) {
      currentStep.value = 'sent'
      startCooldown()
      
      // Show success notification if available
      if (window.showSuccess) {
        window.showSuccess('Password reset email sent successfully!')
      }
    } else {
      generalError.value = data.message || 'Failed to send reset email. Please try again.'
    }
    
  } catch (error) {
    console.error('Password reset error:', error)
    generalError.value = 'Network error. Please check your connection and try again.'
  } finally {
    isLoading.value = false
  }
}

const resendEmail = async () => {
  if (cooldownActive.value || isResending.value) return
  
  isResending.value = true
  generalError.value = ''
  
  try {
    const response = await fetch('/api/auth/forgot-password', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        email: formData.email
      })
    })

    const data = await response.json()

    if (response.ok && data.success) {
      startCooldown()
      
      // Show success notification if available
      if (window.showSuccess) {
        window.showSuccess('Password reset email sent again!')
      }
    } else {
      generalError.value = data.message || 'Failed to resend email. Please try again.'
    }
    
  } catch (error) {
    console.error('Resend email error:', error)
    generalError.value = 'Network error. Please check your connection and try again.'
  } finally {
    isResending.value = false
  }
}

const startCooldown = () => {
  cooldownActive.value = true
  cooldownSeconds.value = 60
  
  cooldownInterval = setInterval(() => {
    cooldownSeconds.value--
    if (cooldownSeconds.value <= 0) {
      stopCooldown()
    }
  }, 1000)
}

const stopCooldown = () => {
  cooldownActive.value = false
  if (cooldownInterval) {
    clearInterval(cooldownInterval)
    cooldownInterval = null
  }
}

const changeEmail = () => {
  currentStep.value = 'request'
  formData.email = ''
  errors.email = ''
  generalError.value = ''
  stopCooldown()
}


const goBackToLogin = () => {
  router.push('/login')
}

// Cleanup on unmount
onUnmounted(() => {
  stopCooldown()
})
</script>

<style scoped>

.step-content {
  animation: fadeIn 0.4s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}


</style>