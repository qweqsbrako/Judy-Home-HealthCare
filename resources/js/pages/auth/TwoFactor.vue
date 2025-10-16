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
            <h1 class="auth-brand-title">Secure Access</h1>
            <p class="auth-brand-subtitle">Two-Factor Authentication</p>
            <div class="auth-features">
              <div class="auth-feature">
                <div class="auth-feature-icon">
                  <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 1L3 5v6c0 5.55 3.84 9.739 9 11 5.16-1.261 9-5.45 9-11V5l-9-4z"/>
                  </svg>
                </div>
                <span>Enhanced Security Protection</span>
              </div>
              <div class="auth-feature">
                <div class="auth-feature-icon">
                  <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                </div>
                <span>Verified Healthcare Access</span>
              </div>
              <div class="auth-feature">
                <div class="auth-feature-icon">
                  <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                  </svg>
                </div>
                <span>HIPAA Compliant Verification</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Side - 2FA Form -->
      <div class="auth-form-section">
        <div class="auth-form-container">
          
          <!-- Header -->
          <div class="auth-form-header">
            <h2 class="auth-form-title">Verify Your Identity</h2>
            <p class="auth-form-subtitle">
              We've sent a 6-digit verification code to 
              <strong>{{ maskedEmail }}</strong>
            </p>
          </div>

          <!-- 2FA Form -->
          <form @submit.prevent="handleVerification" class="auth-form">
            
            <!-- Verification Code Inputs -->
            <div class="auth-form-group">
              <label class="auth-form-label">Enter Verification Code</label>
              <div class="auth-verification-group">
                <input
                  v-for="(digit, index) in verificationCode"
                  :key="index"
                  v-model="verificationCode[index]"
                  :ref="`codeInput${index}`"
                  type="text"
                  maxlength="1"
                  class="auth-verification-input"
                  :class="{ 'filled': verificationCode[index], 'error': errors.code }"
                  @input="handleCodeInput(index, $event)"
                  @keydown="handleKeyDown(index, $event)"
                  @paste="handlePaste($event)"
                >
              </div>
              <span v-if="errors.code" class="auth-error-message">{{ errors.code }}</span>
            </div>

            <!-- Timer and Resend -->
            <div class="auth-form-group">
              <div class="resend-section">
                <p v-if="timeRemaining > 0" class="timer-text">
                  <svg viewBox="0 0 24 24" fill="currentColor" class="timer-icon">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                  </svg>
                  Code expires in {{ formatTime(timeRemaining) }}
                </p>
                <button
                  v-else
                  type="button"
                  class="auth-link resend-btn"
                  @click="resendCode"
                  :disabled="isResending"
                >
                  <svg v-if="isResending" class="auth-spinner" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-dasharray="31.416" stroke-dashoffset="31.416">
                      <animate attributeName="stroke-dasharray" dur="2s" values="0 31.416;15.708 15.708;0 31.416" repeatCount="indefinite"/>
                      <animate attributeName="stroke-dashoffset" dur="2s" values="0;-15.708;-31.416" repeatCount="indefinite"/>
                    </circle>
                  </svg>
                  {{ isResending ? 'Sending...' : 'Resend Code' }}
                </button>
              </div>
            </div>

            <!-- Verify Button -->
            <button 
              type="submit" 
              class="auth-btn"
              :class="{ 'loading': isLoading }"
              :disabled="isLoading || !isCodeComplete"
            >
              <span v-if="!isLoading" class="auth-btn-content">
                <svg viewBox="0 0 24 24" fill="currentColor">
                  <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Verify & Continue
              </span>
              <span v-else class="auth-btn-loading">
                <svg class="auth-spinner" viewBox="0 0 24 24">
                  <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-dasharray="31.416" stroke-dashoffset="31.416">
                    <animate attributeName="stroke-dasharray" dur="2s" values="0 31.416;15.708 15.708;0 31.416" repeatCount="indefinite"/>
                    <animate attributeName="stroke-dashoffset" dur="2s" values="0;-15.708;-31.416" repeatCount="indefinite"/>
                  </circle>
                </svg>
                Verifying...
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
              <p class="security-text">
                This additional security step helps protect your healthcare data and ensures only authorized users can access patient information.
              </p>
            </div>

            <!-- Alternative Methods -->
            <div class="alternative-methods">
              <p class="auth-bottom-text">Having trouble receiving the code?</p>
              <div class="method-buttons">
                <button 
                  type="button" 
                  class="auth-btn-secondary"
                  @click="useAlternativeMethod('sms')"
                >
                  Send via SMS
                </button>
                <button 
                  type="button" 
                  class="auth-btn-secondary"
                  @click="useAlternativeMethod('voice')"
                >
                  Call Me
                </button>
              </div>
            </div>
          </form>

          <!-- Back to Login -->
          <div class="auth-bottom-section">
            <p class="auth-bottom-text">
              Wrong email address?
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
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'

const router = useRouter()
const route = useRoute()

// Reactive data
const verificationCode = reactive(['', '', '', '', '', ''])
const errors = reactive({
  code: ''
})

const isLoading = ref(false)
const isResending = ref(false)
const timeRemaining = ref(300) // 5 minutes in seconds
const timerInterval = ref(null)

// Computed properties
const maskedEmail = computed(() => {
  const email = route.query.email || 'user@example.com'
  const [username, domain] = email.split('@')
  const maskedUsername = username.charAt(0) + '*'.repeat(username.length - 2) + username.charAt(username.length - 1)
  return `${maskedUsername}@${domain}`
})

const isCodeComplete = computed(() => {
  return verificationCode.every(digit => digit.length === 1)
})

const fullCode = computed(() => {
  return verificationCode.join('')
})

// Methods
const handleCodeInput = (index, event) => {
  const value = event.target.value.replace(/\D/g, '') // Only allow digits
  
  if (value.length > 0) {
    verificationCode[index] = value.charAt(0)
    
    // Auto-focus next input
    if (index < 5) {
      const nextInput = document.querySelector(`input[ref="codeInput${index + 1}"]`)
      if (nextInput) {
        nextInput.focus()
      }
    }
  }
  
  // Clear any previous errors
  errors.code = ''
}

const handleKeyDown = (index, event) => {
  // Handle backspace
  if (event.key === 'Backspace') {
    if (verificationCode[index] === '' && index > 0) {
      // Move to previous input if current is empty
      const prevInput = document.querySelector(`input[ref="codeInput${index - 1}"]`)
      if (prevInput) {
        prevInput.focus()
      }
    } else {
      verificationCode[index] = ''
    }
  }
  
  // Handle arrow keys
  if (event.key === 'ArrowLeft' && index > 0) {
    const prevInput = document.querySelector(`input[ref="codeInput${index - 1}"]`)
    if (prevInput) {
      prevInput.focus()
    }
  }
  
  if (event.key === 'ArrowRight' && index < 5) {
    const nextInput = document.querySelector(`input[ref="codeInput${index + 1}"]`)
    if (nextInput) {
      nextInput.focus()
    }
  }
}

const handlePaste = (event) => {
  event.preventDefault()
  const pastedData = event.clipboardData.getData('text').replace(/\D/g, '')
  
  for (let i = 0; i < Math.min(pastedData.length, 6); i++) {
    verificationCode[i] = pastedData.charAt(i)
  }
  
  // Focus the next empty input or the last one
  const nextEmptyIndex = verificationCode.findIndex(digit => digit === '')
  const focusIndex = nextEmptyIndex !== -1 ? nextEmptyIndex : 5
  const targetInput = document.querySelector(`input[ref="codeInput${focusIndex}"]`)
  if (targetInput) {
    targetInput.focus()
  }
}

const formatTime = (seconds) => {
  const minutes = Math.floor(seconds / 60)
  const remainingSeconds = seconds % 60
  return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`
}

const startTimer = () => {
  timerInterval.value = setInterval(() => {
    if (timeRemaining.value > 0) {
      timeRemaining.value--
    } else {
      clearInterval(timerInterval.value)
    }
  }, 1000)
}

const validateCode = () => {
  errors.code = ''
  
  if (!isCodeComplete.value) {
    errors.code = 'Please enter the complete 6-digit code'
    return false
  }
  
  // You can add more validation here
  return true
}

const handleVerification = async () => {
  if (!validateCode()) return
  
  isLoading.value = true
  
  try {
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 2000))
    
    // For demo purposes, accept any complete code
    console.log('2FA verification successful with code:', fullCode.value)
    
    // Redirect to dashboard
    router.push('/dashboard')
    
  } catch (error) {
    console.error('2FA verification failed:', error)
    errors.code = 'Invalid verification code. Please try again.'
  } finally {
    isLoading.value = false
  }
}

const resendCode = async () => {
  isResending.value = true
  
  try {
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 1500))
    
    // Reset timer
    timeRemaining.value = 300
    startTimer()
    
    console.log('Verification code resent')
    
  } catch (error) {
    console.error('Failed to resend code:', error)
  } finally {
    isResending.value = false
  }
}

const useAlternativeMethod = async (method) => {
  try {
    console.log(`Using alternative method: ${method}`)
    // Implement alternative verification methods
    
    // Reset timer
    timeRemaining.value = 300
    startTimer()
    
  } catch (error) {
    console.error(`Failed to use ${method} method:`, error)
  }
}

const goBackToLogin = () => {
  router.push('/login')
}

// Lifecycle
onMounted(() => {
  startTimer()
  
  // Focus first input
  const firstInput = document.querySelector(`input[ref="codeInput0"]`)
  if (firstInput) {
    firstInput.focus()
  }
})

onUnmounted(() => {
  if (timerInterval.value) {
    clearInterval(timerInterval.value)
  }
})
</script>

<style scoped>
.resend-section {
  text-align: center;
}

.timer-text {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  color: #6b7280;
  font-size: 0.875rem;
  margin: 0;
}

.timer-icon {
  width: 16px;
  height: 16px;
}

.resend-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: none;
  border: none;
  padding: 0;
  cursor: pointer;
}

.security-text {
  color: #6c757d;
  font-size: 0.875rem;
  margin: 0;
  line-height: 1.4;
}

.alternative-methods {
  margin-top: 1.5rem;
  text-align: center;
}

.method-buttons {
  display: flex;
  gap: 1rem;
  margin-top: 1rem;
}

.method-buttons .auth-btn-secondary {
  flex: 1;
  margin-bottom: 0;
  padding: 0.75rem 1rem;
  font-size: 0.875rem;
}

@media (max-width: 640px) {
  .method-buttons {
    flex-direction: column;
  }
}
</style>