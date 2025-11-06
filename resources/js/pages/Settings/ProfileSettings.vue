<template>
  <MainLayout>
    <div class="settings-page">
      <div class="max-w-5xl mx-auto px-4 py-8">
        <!-- Page Header -->
        <div class="page-header">
          <div class="page-header-content">
            <h1>Profile Settings</h1>
            <p>Manage your account information and preferences</p>
          </div>
        </div>

        <!-- Settings Tabs -->
        <div class="tabs-container">
          <div class="tabs">
            <button
              @click="activeTab = 'general'"
              :class="['tab', { 'tab-active': activeTab === 'general' }]"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
              General
            </button>
            <button
              @click="activeTab = 'security'"
              :class="['tab', { 'tab-active': activeTab === 'security' }]"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
              </svg>
              Security
            </button>
            <button
              v-if="user.role === 'nurse' || user.role === 'doctor'"
              @click="activeTab = 'professional'"
              :class="['tab', { 'tab-active': activeTab === 'professional' }]"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
              Professional
            </button>
          </div>
        </div>

        <!-- General Information Tab -->
        <div v-show="activeTab === 'general'" class="tab-content">
          <div class="settings-card">
            <div class="settings-card-header">
              <h3>Personal Information</h3>
              <p>Update your personal details and contact information</p>
            </div>
            <form @submit.prevent="updateGeneralInfo" class="settings-card-body">
              <!-- Profile Picture -->
              <div class="profile-picture-section">
                <div class="profile-picture">
                  <img :src="user.avatar_url" :alt="user.first_name" />
                </div>
                <div class="profile-picture-actions">
                  <label for="avatar-upload" class="btn btn-secondary btn-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Change Photo
                  </label>
                  <input
                    id="avatar-upload"
                    type="file"
                    accept="image/*"
                    @change="handleAvatarUpload"
                    class="hidden"
                  />
                  <p class="text-sm text-muted">JPG, PNG or GIF (max. 2MB)</p>
                </div>
              </div>

              <div class="form-grid">
                <div class="form-group">
                  <label>First Name</label>
                  <input
                    type="text"
                    v-model="generalForm.first_name"
                    required
                    class="form-control"
                  />
                </div>

                <div class="form-group">
                  <label>Last Name</label>
                  <input
                    type="text"
                    v-model="generalForm.last_name"
                    required
                    class="form-control"
                  />
                </div>

                <div class="form-group">
                  <label>Email</label>
                  <input
                    type="email"
                    v-model="generalForm.email"
                    required
                    class="form-control"
                  />
                </div>

                <div class="form-group">
                  <label>Phone</label>
                  <input
                    type="tel"
                    v-model="generalForm.phone"
                    required
                    class="form-control"
                  />
                </div>

                <div class="form-group">
                  <label>Date of Birth</label>
                  <input
                    type="date"
                    v-model="generalForm.date_of_birth"
                    required
                    class="form-control"
                  />
                </div>

                <div class="form-group">
                  <label>Gender</label>
                  <SearchableSelect
                    v-model="generalForm.gender"
                    :options="genderOptions"
                    placeholder="Select gender..."
                    required
                  />
                </div>

                <div class="form-group form-grid-full">
                  <label>Ghana Card Number</label>
                  <input
                    type="text"
                    v-model="generalForm.ghana_card_number"
                    disabled
                    class="form-control"
                  />
                  <p class="form-help">Ghana Card Number cannot be changed. Contact support if you need to update this.</p>
                </div>

                <!-- Emergency Contact for Patients -->
                <template v-if="user.role === 'patient'">
                  <div class="form-section-header form-grid-full">
                    <h4>Emergency Contact</h4>
                  </div>

                  <div class="form-group">
                    <label>Emergency Contact Name</label>
                    <input
                      type="text"
                      v-model="generalForm.emergency_contact_name"
                      class="form-control"
                    />
                  </div>

                  <div class="form-group">
                    <label>Emergency Contact Phone</label>
                    <input
                      type="tel"
                      v-model="generalForm.emergency_contact_phone"
                      class="form-control"
                    />
                  </div>
                </template>
              </div>

              <div class="form-actions">
                <button type="button" @click="loadUserData" class="btn btn-secondary">
                  Cancel
                </button>
                <button type="submit" :disabled="isSaving" class="btn btn-primary">
                  <div v-if="isSaving" class="spinner spinner-sm"></div>
                  Save Changes
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Security Tab -->
        <div v-show="activeTab === 'security'" class="tab-content">
          <!-- Change Password Section -->
          <div class="settings-card">
            <div class="settings-card-header">
              <h3>Change Password</h3>
              <p>Update your password to keep your account secure</p>
            </div>
            <form @submit.prevent="updatePasswordHandler" class="settings-card-body">
              <div class="form-grid">
                <div class="form-group form-grid-full">
                  <label>Current Password</label>
                  <input
                    type="password"
                    v-model="passwordForm.current_password"
                    required
                    class="form-control"
                  />
                </div>

                <div class="form-group">
                  <label>New Password</label>
                  <input
                    type="password"
                    v-model="passwordForm.new_password"
                    required
                    minlength="8"
                    class="form-control"
                  />
                  <p class="form-help">Minimum 8 characters</p>
                </div>

                <div class="form-group">
                  <label>Confirm New Password</label>
                  <input
                    type="password"
                    v-model="passwordForm.new_password_confirmation"
                    required
                    class="form-control"
                  />
                </div>
              </div>

              <div class="form-actions">
                <button type="submit" :disabled="isSavingPassword" class="btn btn-primary">
                  <div v-if="isSavingPassword" class="spinner spinner-sm"></div>
                  Update Password
                </button>
              </div>
            </form>
          </div>

          <!-- Two-Factor Authentication Section -->
          <div class="settings-card">
            <div class="settings-card-header">
              <h3>Two-Factor Authentication</h3>
              <p>Add an extra layer of security to your account</p>
            </div>
            <div class="settings-card-body">
              <div class="two-factor-status">
                <div class="two-factor-info">
                  <div class="two-factor-icon" :class="user.two_factor_enabled ? 'enabled' : 'disabled'">
                    <svg v-if="user.two_factor_enabled" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                  </div>
                  <div>
                    <h4>{{ user.two_factor_enabled ? 'Enabled' : 'Disabled' }}</h4>
                    <p v-if="user.two_factor_enabled">
                      Two-factor authentication is currently enabled via {{ formatTwoFactorMethod(user.two_factor_method) }}
                    </p>
                    <p v-else>
                      Two-factor authentication adds an extra layer of security to your account
                    </p>
                  </div>
                </div>
                <div class="two-factor-actions">
                  <button
                    v-if="!user.two_factor_enabled"
                    @click="showEnableTwoFactorModal = true"
                    class="btn btn-primary"
                  >
                    Enable 2FA
                  </button>
                  <button
                    v-else
                    @click="showDisableTwoFactorModal = true"
                    class="btn btn-danger"
                  >
                    Disable 2FA
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Account Status -->
          <div class="settings-card">
            <div class="settings-card-header">
              <h3>Account Status</h3>
              <p>View your account verification and activity status</p>
            </div>
            <div class="settings-card-body">
              <div class="status-grid">
                <div class="status-item">
                  <label>Verification Status</label>
                  <span :class="'badge ' + getVerificationBadgeClass(user.verification_status)">
                    {{ capitalizeFirst(user.verification_status) }}
                  </span>
                </div>
                <div class="status-item">
                  <label>Account Status</label>
                  <span :class="'badge ' + (user.is_active ? 'badge-success' : 'badge-danger')">
                    {{ user.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </div>
                <div class="status-item">
                  <label>Email Verified</label>
                  <span :class="'badge ' + (user.email_verified_at ? 'badge-success' : 'badge-warning')">
                    {{ user.email_verified_at ? 'Verified' : 'Not Verified' }}
                  </span>
                </div>
                <div class="status-item">
                  <label>Last Login</label>
                  <p>{{ formatDate(user.last_login_at) }}</p>
                </div>
                <div class="status-item">
                  <label>Member Since</label>
                  <p>{{ formatDate(user.created_at) }}</p>
                </div>
                <div class="status-item">
                  <label>Role</label>
                  <span :class="'badge ' + getRoleBadgeClass(user.role)">
                    {{ capitalizeFirst(user.role) }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Professional Information Tab -->
        <div v-show="activeTab === 'professional'" class="tab-content">
          <div class="settings-card">
            <div class="settings-card-header">
              <h3>Professional Information</h3>
              <p>Manage your professional credentials and experience</p>
            </div>
            <form @submit.prevent="updateProfessionalInfoHandler" class="settings-card-body">
              <div class="form-grid">
                <div class="form-group">
                  <label>License Number</label>
                  <input
                    type="text"
                    v-model="professionalForm.license_number"
                    class="form-control"
                  />
                  <p class="form-help">Your professional license number</p>
                </div>

                <div class="form-group">
                  <label>Specialization</label>
                  <input
                    type="text"
                    v-model="professionalForm.specialization"
                    class="form-control"
                  />
                </div>

                <div class="form-group form-grid-full">
                  <label>Years of Experience</label>
                  <input
                    type="number"
                    v-model="professionalForm.years_experience"
                    min="0"
                    class="form-control"
                  />
                </div>
              </div>

              <div class="form-actions">
                <button type="button" @click="loadUserData" class="btn btn-secondary">
                  Cancel
                </button>
                <button type="submit" :disabled="isSavingProfessional" class="btn btn-primary">
                  <div v-if="isSavingProfessional" class="spinner spinner-sm"></div>
                  Save Changes
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Enable Two-Factor Modal -->
    <div v-if="showEnableTwoFactorModal" class="modal-overlay">
      <div class="modal modal-sm">
        <div class="modal-header">
          <h3 class="modal-title">Enable Two-Factor Authentication</h3>
          <button @click="showEnableTwoFactorModal = false" class="modal-close">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <form @submit.prevent="enableTwoFactorHandler">
          <div class="modal-body">
            <div class="form-group">
              <label>Choose Verification Method</label>
              <select v-model="twoFactorMethod" required class="form-control">
                <option value="email">Email</option>
                <option value="sms">SMS</option>
              </select>
              <p class="form-help">You will receive a verification code via {{ twoFactorMethod === 'email' ? 'email' : 'SMS' }}</p>
            </div>
          </div>

          <div class="modal-actions">
            <button type="button" @click="showEnableTwoFactorModal = false" class="btn btn-secondary">
              Cancel
            </button>
            <button type="submit" :disabled="isEnablingTwoFactor" class="btn btn-primary">
              <div v-if="isEnablingTwoFactor" class="spinner spinner-sm"></div>
              Enable
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Disable Two-Factor Confirmation Modal -->
    <div v-if="showDisableTwoFactorModal" class="modal-overlay">
      <div class="modal modal-sm">
        <div class="modal-header modal-header-danger">
          <h3 class="modal-title">
            <svg class="modal-icon modal-icon-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
            Disable Two-Factor Authentication
          </h3>
          <button @click="showDisableTwoFactorModal = false" class="modal-close">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="modal-body">
          <p>Are you sure you want to disable two-factor authentication?</p>
          <p class="text-sm text-muted" style="margin-top: 0.75rem;">
            This will make your account less secure. You will only need your password to log in.
          </p>
        </div>

        <div class="modal-actions">
          <button @click="showDisableTwoFactorModal = false" class="btn btn-secondary">
            Cancel
          </button>
          <button @click="disableTwoFactorHandler" :disabled="isDisablingTwoFactor" class="btn btn-danger">
            <div v-if="isDisablingTwoFactor" class="spinner spinner-sm"></div>
            Disable 2FA
          </button>
        </div>
      </div>
    </div>

    <!-- Toast Component -->
    <Toast />
  </MainLayout>
</template>

<script setup>
import { ref, reactive, onMounted, inject } from 'vue'
import { useRouter } from 'vue-router'
import MainLayout from '../../layout/MainLayout.vue'
import Toast from '../../common/components/Toast.vue'
import SearchableSelect from '../../common/components/SearchableSelect.vue'
import { 
  getProfile, 
  updateProfile, 
  updatePassword,
  updateProfessionalInfo,
  updateAvatar,
  enableTwoFactor,
  disableTwoFactor
} from '../../services/profileService'

const router = useRouter()
const toast = inject('toast')

// Reactive data
const activeTab = ref('general')
const isSaving = ref(false)
const isSavingPassword = ref(false)
const isSavingProfessional = ref(false)
const isEnablingTwoFactor = ref(false)
const isDisablingTwoFactor = ref(false)
const showEnableTwoFactorModal = ref(false)
const showDisableTwoFactorModal = ref(false)
const twoFactorMethod = ref('email')

// Gender options for searchable select
const genderOptions = [
  { value: 'male', label: 'Male' },
  { value: 'female', label: 'Female' },
  { value: 'other', label: 'Other' }
]

// User data
const user = ref({
  id: null,
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  role: '',
  date_of_birth: '',
  gender: '',
  ghana_card_number: '',
  avatar_url: '',
  is_active: false,
  verification_status: '',
  email_verified_at: null,
  two_factor_enabled: false,
  two_factor_method: null,
  license_number: '',
  specialization: '',
  years_experience: 0,
  emergency_contact_name: '',
  emergency_contact_phone: '',
  last_login_at: null,
  created_at: null
})

// Form data
const generalForm = reactive({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  date_of_birth: '',
  gender: '',
  ghana_card_number: '',
  emergency_contact_name: '',
  emergency_contact_phone: ''
})

const passwordForm = reactive({
  current_password: '',
  new_password: '',
  new_password_confirmation: ''
})

const professionalForm = reactive({
  license_number: '',
  specialization: '',
  years_experience: 0
})

// Methods
const loadUserData = async () => {
  try {
    const data = await getProfile()
    
    // Update user object
    Object.assign(user.value, data)
    
    // Set avatar URL
    user.value.avatar_url = data.avatar || `https://ui-avatars.com/api/?name=${data.first_name}+${data.last_name}&color=667eea&background=f8f9fa&size=200&font-size=0.6`
    
    // Populate forms
    Object.assign(generalForm, {
      first_name: data.first_name,
      last_name: data.last_name,
      email: data.email,
      phone: data.phone,
      date_of_birth: data.date_of_birth ? data.date_of_birth.split('T')[0] : '',
      gender: data.gender || '',
      ghana_card_number: data.ghana_card_number,
      emergency_contact_name: data.emergency_contact_name || '',
      emergency_contact_phone: data.emergency_contact_phone || ''
    })
    
    Object.assign(professionalForm, {
      license_number: data.license_number || '',
      specialization: data.specialization || '',
      years_experience: data.years_experience || 0
    })
  } catch (error) {
    console.error('Error loading user data:', error)
    toast.showError(error.message || 'An error occurred while loading your profile')
  }
}

const updateGeneralInfo = async () => {
  isSaving.value = true
  
  try {
    await updateProfile(generalForm)
    await loadUserData()
    toast.showSuccess('Profile updated successfully!')
  } catch (error) {
    console.error('Error updating profile:', error)
    toast.showError(error.message || 'Failed to update profile')
  } finally {
    isSaving.value = false
  }
}

const updatePasswordHandler = async () => {
  if (passwordForm.new_password !== passwordForm.new_password_confirmation) {
    toast.showError('New passwords do not match')
    return
  }
  
  isSavingPassword.value = true
  
  try {
    await updatePassword(passwordForm)
    toast.showSuccess('Password updated successfully!')
    // Clear form
    passwordForm.current_password = ''
    passwordForm.new_password = ''
    passwordForm.new_password_confirmation = ''
  } catch (error) {
    console.error('Error updating password:', error)
    toast.showError(error.message || 'Failed to update password')
  } finally {
    isSavingPassword.value = false
  }
}

const updateProfessionalInfoHandler = async () => {
  isSavingProfessional.value = true
  
  try {
    await updateProfessionalInfo(professionalForm)
    await loadUserData()
    toast.showSuccess('Professional information updated successfully!')
  } catch (error) {
    console.error('Error updating professional info:', error)
    toast.showError(error.message || 'Failed to update professional information')
  } finally {
    isSavingProfessional.value = false
  }
}

const handleAvatarUpload = async (event) => {
  const file = event.target.files[0]
  
  if (!file) return
  
  // Validate file size (max 2MB)
  if (file.size > 2 * 1024 * 1024) {
    toast.showError('File size must be less than 2MB')
    return
  }
  
  // Validate file type
  if (!file.type.match(/^image\/(jpeg|jpg|png|gif)$/)) {
    toast.showError('Only JPG, PNG, and GIF files are allowed')
    return
  }
  
  try {
    await updateAvatar(file)
    await loadUserData()
    toast.showSuccess('Profile picture updated successfully!')
  } catch (error) {
    console.error('Error uploading avatar:', error)
    toast.showError(error.message || 'An error occurred while uploading your profile picture')
  }
}

const enableTwoFactorHandler = async () => {
  isEnablingTwoFactor.value = true
  
  try {
    await enableTwoFactor({ method: twoFactorMethod.value })
    await loadUserData()
    showEnableTwoFactorModal.value = false
    toast.showSuccess('Two-factor authentication enabled successfully!')
  } catch (error) {
    console.error('Error enabling 2FA:', error)
    toast.showError(error.message || 'Failed to enable two-factor authentication')
  } finally {
    isEnablingTwoFactor.value = false
  }
}

const disableTwoFactorHandler = async () => {
  isDisablingTwoFactor.value = true
  
  try {
    await disableTwoFactor()
    await loadUserData()
    showDisableTwoFactorModal.value = false
    toast.showSuccess('Two-factor authentication disabled')
  } catch (error) {
    console.error('Error disabling 2FA:', error)
    toast.showError(error.message || 'Failed to disable two-factor authentication')
  } finally {
    isDisablingTwoFactor.value = false
  }
}

// Utility functions
const capitalizeFirst = (str) => {
  if (!str) return ''
  return str.charAt(0).toUpperCase() + str.slice(1)
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const formatTwoFactorMethod = (method) => {
  if (!method) return ''
  return method === 'sms' ? 'SMS' : capitalizeFirst(method)
}

const getVerificationBadgeClass = (status) => {
  const colorMap = {
    'verified': 'badge-success',
    'pending': 'badge-warning',
    'rejected': 'badge-danger',
    'suspended': 'badge-danger'
  }
  return colorMap[status] || 'badge-secondary'
}

const getRoleBadgeClass = (role) => {
  const colorMap = {
    'doctor': 'badge-primary',
    'nurse': 'badge-success',
    'admin': 'badge-warning',
    'superadmin': 'badge-danger',
    'patient': 'badge-secondary'
  }
  return colorMap[role] || 'badge-secondary'
}

// Lifecycle
onMounted(() => {
  loadUserData()
})
</script>

<style scoped>
.settings-page {
  min-height: 100vh;
  background: #f8f9fa;
}

.page-header {
  margin-bottom: 2rem;
}

.page-header-content h1 {
  font-size: 2rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0 0 0.5rem 0;
}

.page-header-content p {
  color: #6b7280;
  margin: 0;
}

.tabs-container {
  border-bottom: 1px solid #e5e7eb;
  margin-bottom: 2rem;
}

.tabs {
  display: flex;
  gap: 2rem;
  overflow-x: auto;
  scrollbar-width: none;
  -ms-overflow-style: none;
}

.tabs::-webkit-scrollbar {
  display: none;
}

.tab {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 1rem 0;
  background: none;
  border: none;
  font-size: 1rem;
  font-weight: 500;
  color: #6b7280;
  cursor: pointer;
  border-bottom: 2px solid transparent;
  transition: all 0.2s;
  white-space: nowrap;
}

.tab.tab-active {
  color: #3b82f6;
  border-bottom-color: #3b82f6;
}

.tab:hover:not(.tab-active) {
  color: #374151;
}

.tab svg {
  width: 18px;
  height: 18px;
  flex-shrink: 0;
}

.tab-content {
  animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.settings-card {
  background: white;
  border-radius: 0.75rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  border: 1px solid #e5e7eb;
  margin-bottom: 2rem;
}

.settings-card-header {
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.settings-card-header h3 {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1f2937;
  margin: 0 0 0.5rem 0;
}

.settings-card-header p {
  color: #6b7280;
  margin: 0;
  font-size: 0.875rem;
}

.settings-card-body {
  padding: 1.5rem;
}

.profile-picture-section {
  display: flex;
  align-items: center;
  gap: 2rem;
  margin-bottom: 2rem;
  padding-bottom: 2rem;
  border-bottom: 1px solid #e5e7eb;
}

.profile-picture img {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  object-fit: cover;
  border: 4px solid #f3f4f6;
}

.profile-picture-actions {
  flex: 1;
}

.hidden {
  display: none;
}

.text-sm {
  font-size: 0.875rem;
}

.text-muted {
  color: #6b7280;
  margin: 0.5rem 0 0 0;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1.5rem;
}

@media (min-width: 768px) {
  .form-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

.form-grid-full {
  grid-column: 1 / -1;
}

.form-section-header {
  margin-top: 1.5rem;
}

.form-section-header:first-child {
  margin-top: 0;
}

.form-section-header h4 {
  font-size: 1rem;
  font-weight: 600;
  color: #1f2937;
  margin: 0;
}

.form-group {
  margin-bottom: 0;
}

.form-group label {
  display: block;
  font-weight: 500;
  color: #374151;
  margin-bottom: 0.5rem;
  font-size: 0.875rem;
}

.form-control {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.form-control:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-control:disabled {
  background: #f9fafb;
  color: #6b7280;
  cursor: not-allowed;
}

.form-help {
  color: #6b7280;
  font-size: 0.8125rem;
  margin-top: 0.25rem;
  display: block;
}

.form-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  margin-top: 2rem;
  padding-top: 2rem;
  border-top: 1px solid #e5e7eb;
}

.two-factor-status {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 2rem;
}

.two-factor-info {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  flex: 1;
}

.two-factor-icon {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.two-factor-icon.enabled {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
}

.two-factor-icon.disabled {
  background: #f3f4f6;
  color: #6b7280;
}

.two-factor-info h4 {
  font-size: 1rem;
  font-weight: 600;
  color: #1f2937;
  margin: 0 0 0.25rem 0;
}

.two-factor-info p {
  color: #6b7280;
  margin: 0;
  font-size: 0.875rem;
}

.status-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
}

.status-item label {
  display: block;
  font-weight: 500;
  color: #6b7280;
  margin-bottom: 0.5rem;
  font-size: 0.875rem;
}

.status-item p {
  color: #1f2937;
  margin: 0;
  font-size: 0.875rem;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.625rem 1.25rem;
  border: none;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-primary {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
  transform: translateY(-1px);
}

.btn-secondary {
  background: #f3f4f6;
  color: #374151;
}

.btn-secondary:hover:not(:disabled) {
  background: #e5e7eb;
}

.btn-danger {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  color: white;
}

.btn-danger:hover:not(:disabled) {
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
  transform: translateY(-1px);
}

.btn-sm {
  padding: 0.5rem 1rem;
  font-size: 0.8125rem;
}

.spinner {
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  width: 16px;
  height: 16px;
  animation: spin 0.6s linear infinite;
}

.spinner-sm {
  width: 14px;
  height: 14px;
  border-width: 2px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.badge {
  display: inline-flex;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 500;
}

.badge-success {
  background: #d1fae5;
  color: #065f46;
}

.badge-warning {
  background: #fef3c7;
  color: #92400e;
}

.badge-danger {
  background: #fee2e2;
  color: #991b1b;
}

.badge-primary {
  background: #dbeafe;
  color: #1e40af;
}

.badge-secondary {
  background: #f3f4f6;
  color: #4b5563;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}

.modal {
  background: white;
  border-radius: 0.75rem;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
  max-width: 100%;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-sm {
  max-width: 500px;
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.modal-header-danger {
  background: #fef2f2;
  border-bottom-color: #fecaca;
}

.modal-title {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 1.25rem;
  font-weight: 600;
  color: #1f2937;
  margin: 0;
}

.modal-icon {
  width: 24px;
  height: 24px;
  flex-shrink: 0;
}

.modal-icon-danger {
  color: #dc2626;
}

.modal-close {
  background: none;
  border: none;
  color: #6b7280;
  cursor: pointer;
  padding: 0.25rem;
  border-radius: 0.375rem;
  transition: all 0.2s;
}

.modal-close:hover {
  background: #f3f4f6;
  color: #374151;
}

.modal-body {
  padding: 1.5rem;
}

.modal-body p {
  color: #374151;
  margin: 0;
}

.modal-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  padding: 1.5rem;
  border-top: 1px solid #e5e7eb;
}

@media (max-width: 768px) {
  .profile-picture-section {
    flex-direction: column;
    align-items: flex-start;
  }

  .two-factor-status {
    flex-direction: column;
    align-items: flex-start;
  }

  .two-factor-actions {
    width: 100%;
  }

  .two-factor-actions button {
    width: 100%;
  }

  .form-actions {
    flex-direction: column;
  }

  .form-actions button {
    width: 100%;
  }

  .modal-actions {
    flex-direction: column;
  }

  .modal-actions button {
    width: 100%;
  }
}
</style>