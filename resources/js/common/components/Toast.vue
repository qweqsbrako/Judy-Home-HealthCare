<template>
  <Teleport to="body">
    <div class="toast-container">
      <TransitionGroup name="toast" tag="div" class="toast-wrapper">
        <div
          v-for="toast in toasts"
          :key="toast.id"
          class="toast"
          :class="[`toast-${toast.type}`, { 'toast-dismissing': toast.dismissing }]"
        >
          <div class="toast-icon">
            <!-- Success Icon -->
            <svg v-if="toast.type === 'success'" viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
            </svg>
            
            <!-- Error Icon -->
            <svg v-else-if="toast.type === 'error'" viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z"/>
            </svg>
            
            <!-- Warning Icon -->
            <svg v-else-if="toast.type === 'warning'" viewBox="0 0 24 24" fill="currentColor">
              <path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/>
            </svg>
            
            <!-- Info Icon -->
            <svg v-else-if="toast.type === 'info'" viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>
            </svg>
          </div>

          <div class="toast-content">
            <div class="toast-title" v-if="toast.title">
              {{ toast.title }}
            </div>
            <div class="toast-message">
              {{ toast.message }}
            </div>
          </div>

          <button 
            class="toast-close" 
            @click="dismissToast(toast.id)"
            :aria-label="'Dismiss notification'"
          >
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
            </svg>
          </button>

          <!-- Progress bar for auto-dismiss -->
          <div 
            v-if="toast.duration && toast.duration > 0" 
            class="toast-progress"
            :style="{ animationDuration: `${toast.duration}ms` }"
          ></div>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup>
import { inject } from 'vue'

// Get toast state and methods from the composable
const toast = inject('toast')

// Handle case where toast isn't injected yet (fallback)
const toasts = toast?.toasts || { value: [] }
const dismissToast = toast?.dismissToast || (() => {})
</script>

<style scoped>
.toast-container {
  position: fixed;
  top: 1rem;
  right: 1rem;
  z-index: 9999;
  pointer-events: none;
}

.toast-wrapper {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  max-width: 400px;
}

.toast {
  position: relative;
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 1rem;
  background: white;
  border-radius: 0.75rem;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1), 0 4px 6px rgba(0, 0, 0, 0.05);
  border-left: 4px solid;
  pointer-events: auto;
  overflow: hidden;
  min-width: 320px;
  max-width: 400px;
}

.toast-success {
  border-left-color: #10b981;
  background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
}

.toast-error {
  border-left-color: #ef4444;
  background: linear-gradient(135deg, #fef2f2 0%, #fef2f2 100%);
}

.toast-warning {
  border-left-color: #D3D3D3;
  background: linear-gradient(135deg, #fffbeb 0%, #fefce8 100%);
}

.toast-info {
  border-left-color: #3b82f6;
  background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
}

.toast-icon {
  flex-shrink: 0;
  width: 24px;
  height: 24px;
  margin-top: 0.125rem;
}

.toast-success .toast-icon {
  color: #10b981;
}

.toast-error .toast-icon {
  color: #ef4444;
}

.toast-warning .toast-icon {
  color: #D3D3D3;
}

.toast-info .toast-icon {
  color: #3b82f6;
}

.toast-content {
  flex: 1;
  min-width: 0;
}

.toast-title {
  font-weight: 600;
  color: #1f2937;
  font-size: 0.875rem;
  margin-bottom: 0.25rem;
  line-height: 1.25;
}

.toast-message {
  color: #4b5563;
  font-size: 0.875rem;
  line-height: 1.4;
  word-wrap: break-word;
}

.toast-close {
  flex-shrink: 0;
  width: 20px;
  height: 20px;
  background: none;
  border: none;
  cursor: pointer;
  color: #9ca3af;
  border-radius: 0.25rem;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-top: 0.125rem;
}

.toast-close:hover {
  color: #6b7280;
  background: rgba(0, 0, 0, 0.05);
}

.toast-close svg {
  width: 16px;
  height: 16px;
}

.toast-progress {
  position: absolute;
  bottom: 0;
  left: 0;
  height: 3px;
  background: currentColor;
  opacity: 0.7;
  animation: toast-progress linear;
  transform-origin: left;
}

.toast-success .toast-progress {
  color: #10b981;
}

.toast-error .toast-progress {
  color: #ef4444;
}

.toast-warning .toast-progress {
  color: #D3D3D3;
}

.toast-info .toast-progress {
  color: #3b82f6;
}

@keyframes toast-progress {
  from {
    transform: scaleX(1);
  }
  to {
    transform: scaleX(0);
  }
}

/* Toast animations */
.toast-enter-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.toast-leave-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.toast-enter-from {
  opacity: 0;
  transform: translateX(100%) scale(0.95);
}

.toast-leave-to {
  opacity: 0;
  transform: translateX(100%) scale(0.95);
}

.toast-move {
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Responsive */
@media (max-width: 640px) {
  .toast-container {
    top: 1rem;
    left: 1rem;
    right: 1rem;
  }

  .toast {
    min-width: auto;
    max-width: none;
  }

  .toast-enter-from,
  .toast-leave-to {
    transform: translateY(-100%) scale(0.95);
  }
}
</style>