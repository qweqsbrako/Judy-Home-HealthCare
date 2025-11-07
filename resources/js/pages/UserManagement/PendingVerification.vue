<template>
  <MainLayout>
    <div class="users-page">
      <!-- Page Header -->
      <div class="page-header">
        <div class="page-header-content">
          <h1>Pending Verifications</h1>
          <p>Review and verify pending user registrations</p>
        </div>
        <div class="page-header-actions">
          <button
            v-if="selectedUsers.length > 0"
            @click="openBulkVerifyModal"
            class="btn-modern btn-primary"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Verify ({{ selectedUsers.length }})
          </button>
          <button
            v-if="selectedUsers.length > 0"
            @click="openBulkRejectModal"
            class="btn-modern btn-danger"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
            Reject ({{ selectedUsers.length }})
          </button>
          <button @click="exportPendingUsers" class="btn-modern btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Export
          </button>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon yellow">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z"/>
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Total Pending</div>
            <div class="stat-value">{{ stats.total_pending }}</div>
            <div class="stat-change neutral">Awaiting verification</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon blue">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Pending Patients</div>
            <div class="stat-value">{{ stats.pending_patients }}</div>
            <div class="stat-change neutral">Patient registrations</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon green">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Pending Nurses</div>
            <div class="stat-value">{{ stats.pending_nurses }}</div>
            <div class="stat-change neutral">Nurse registrations</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon purple">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Pending Doctors</div>
            <div class="stat-value">{{ stats.pending_doctors }}</div>
            <div class="stat-change neutral">Doctor registrations</div>
          </div>
        </div>
      </div>

      <!-- Filters and Search -->
      <div class="filters-section">
        <div class="search-wrapper">
          <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <input
            type="text"
            placeholder="Search by name, email, phone, or ID number..."
            v-model="searchQuery"
            class="search-input"
          />
        </div>
        <div class="filters-group">
          <select v-model="roleFilter" class="filter-select">
            <option value="all">All Roles</option>
            <option value="patient">Patients</option>
            <option value="nurse">Nurses</option>
            <option value="doctor">Doctors</option>
          </select>
          <select v-model="sortBy" class="filter-select">
            <option value="created_at">Sort by Date</option>
            <option value="first_name">Sort by Name</option>
            <option value="role">Sort by Role</option>
          </select>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="loading-state">
        <div class="loading-spinner"></div>
        <p class="loading-text">Loading pending verifications...</p>
      </div>

      <!-- Pending Users Table -->
      <div v-else class="users-table-container">
        <div class="table-wrapper">
          <table class="modern-table">
            <thead>
              <tr>
                <th style="width: 50px;">
                  <input
                    type="checkbox"
                    :checked="allSelected"
                    @change="toggleSelectAll"
                    class="checkbox-input"
                  />
                </th>
                <th>User</th>
                <th>Contact</th>
                <th>Role</th>
                <th>Professional Info</th>
                <th>Registration Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="user in filteredUsers" :key="user.id">
                <td>
                  <input
                    type="checkbox"
                    :checked="selectedUsers.includes(user.id)"
                    @change="toggleUserSelection(user.id)"
                    class="checkbox-input"
                  />
                </td>
                <td>
                  <div class="user-cell">
                    <img :src="user.avatar_url" :alt="user.full_name" class="user-avatar-table" />
                    <div class="user-details-table">
                      <div class="user-name-table">
                        {{ user.role === 'doctor' ? 'Dr. ' : '' }}{{ user.first_name }} {{ user.last_name }}
                      </div>
                      <div class="user-id-table">{{ getIdNumber(user) }}</div>
                    </div>
                  </div>
                </td>
                <td>
                  <div class="contact-cell">
                    <div class="contact-primary">{{ user.email }}</div>
                    <div class="contact-secondary">{{ user.phone }}</div>
                  </div>
                </td>
                <td>
                  <span :class="'modern-badge ' + getRoleBadgeColor(user.role)">
                    {{ capitalizeFirst(user.role) }}
                  </span>
                </td>
                <td>
                  <div class="contact-cell">
                    <div v-if="user.role === 'patient'" class="contact-secondary">
                      Patient Care
                    </div>
                    <template v-else>
                      <div class="contact-primary">
                        {{ user.specialization ? formatSpecialization(user.specialization) : (user.role === 'nurse' ? 'General Nursing' : 'General Practice') }}
                      </div>
                      <div v-if="user.years_experience" class="contact-secondary">
                        {{ user.years_experience }} years exp.
                      </div>
                    </template>
                  </div>
                </td>
                <td class="text-secondary">
                  {{ formatTimeAgo(user.created_at) }}
                </td>
                <td>
                  <div class="action-buttons">
                    <button @click="openViewModal(user)" class="action-btn-icon" title="View Details">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                    </button>
                    <button @click="openVerifyModal(user)" class="action-btn-icon success" title="Verify">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                    </button>
                    <button @click="openRejectModal(user)" class="action-btn-icon danger" title="Reject">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <div v-if="filteredUsers.length === 0" class="empty-state">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <h3>No pending verifications</h3>
          <p>All user registrations have been processed.</p>
        </div>
      </div>

      <!-- View User Details Modal -->
      <div v-if="showViewModal && currentUser" class="modal-overlay" @click.self="closeViewModal">
        <div class="modal modal-xl">
          <div class="modal-header">
            <h2 class="modal-title">User Details</h2>
            <button @click="closeViewModal" class="modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="modal-body">
            <div class="user-view-grid">
              <!-- User Profile Section -->
              <div class="user-profile-section">
                <img
                  :src="currentUser.avatar_url"
                  :alt="currentUser.full_name"
                  class="profile-avatar-large"
                />
                <h3 class="profile-name-view">
                  {{ currentUser.role === 'doctor' ? 'Dr. ' : '' }}{{ currentUser.first_name }} {{ currentUser.last_name }}
                </h3>
                <span :class="'modern-badge ' + getRoleBadgeColor(currentUser.role)">
                  {{ capitalizeFirst(currentUser.role) }}
                </span>
                <div class="profile-contact-view">
                  <div class="contact-item-view">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <span>{{ currentUser.email }}</span>
                  </div>
                  <div class="contact-item-view">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                    <span>{{ currentUser.phone }}</span>
                  </div>
                </div>
              </div>

              <!-- Details Section -->
              <div class="details-section-view">
                <!-- Basic Information -->
                <div class="details-group">
                  <h4 class="details-header">Basic Information</h4>
                  <div class="details-grid-view">
                    <div class="detail-item-view">
                      <label>Full Name</label>
                      <p>{{ currentUser.first_name }} {{ currentUser.last_name }}</p>
                    </div>
                    <div class="detail-item-view">
                      <label>Gender</label>
                      <p style="text-transform: capitalize;">{{ currentUser.gender }}</p>
                    </div>
                    <div class="detail-item-view">
                      <label>Ghana Card</label>
                      <p>{{ currentUser.ghana_card_number }}</p>
                    </div>
                    <div class="detail-item-view">
                      <label>Date of Birth</label>
                      <p>{{ formatDate(currentUser.date_of_birth) }}</p>
                    </div>
                  </div>
                </div>

                <!-- Professional Information -->
                <div v-if="currentUser.role !== 'patient'" class="details-group">
                  <h4 class="details-header">Professional Information</h4>
                  <div class="details-grid-view">
                    <div class="detail-item-view">
                      <label>License Number</label>
                      <p>{{ currentUser.license_number || 'Not provided' }}</p>
                    </div>
                    <div class="detail-item-view">
                      <label>Specialization</label>
                      <p>{{ formatSpecialization(currentUser.specialization) || 'Not provided' }}</p>
                    </div>
                    <div class="detail-item-view">
                      <label>Years of Experience</label>
                      <p>{{ currentUser.years_experience || 0 }} years</p>
                    </div>
                  </div>
                </div>

                <!-- Registration Information -->
                <div class="details-group">
                  <h4 class="details-header">Registration Information</h4>
                  <div class="details-grid-view">
                    <div class="detail-item-view">
                      <label>Status</label>
                      <span class="modern-badge badge-warning">Pending Verification</span>
                    </div>
                    <div class="detail-item-view">
                      <label>Registered</label>
                      <p>{{ formatDate(currentUser.created_at) }}</p>
                    </div>
                    <div class="detail-item-view">
                      <label>Registration IP</label>
                      <p>{{ currentUser.registered_ip || 'Not recorded' }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="modal-actions">
            <button @click="closeViewModal" class="btn btn-secondary">
              Close
            </button>
            <button @click="verifyFromView" class="btn btn-primary">
              Verify User
            </button>
            <button @click="rejectFromView" class="btn btn-danger">
              Reject User
            </button>
          </div>
        </div>
      </div>

      <!-- Verify User Modal -->
      <div v-if="showVerifyModal && currentUser" class="modal-overlay" @click.self="closeVerifyModal">
        <div class="modal modal-sm">
          <div class="modal-header">
            <h3 class="modal-title">Verify User</h3>
            <button @click="closeVerifyModal" class="modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          
          <div class="modal-body">
            <p>
              Are you sure you want to verify <strong>{{ currentUser.role === 'doctor' ? 'Dr. ' : '' }}{{ currentUser.first_name }} {{ currentUser.last_name }}</strong> as a {{ currentUser.role }}?
            </p>
            
            <div class="form-group" style="margin-top: 20px;">
              <label>Verification Notes (Optional)</label>
              <textarea
                v-model="verificationNotes"
                placeholder="Add any notes about this verification..."
                rows="3"
                style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 10px; font-family: inherit;"
              ></textarea>
            </div>
          </div>

          <div class="modal-actions">
            <button @click="closeVerifyModal" class="btn btn-secondary">
              Cancel
            </button>
            <button @click="verifyUser" :disabled="isVerifying" class="btn btn-primary">
              <div v-if="isVerifying" class="spinner spinner-sm"></div>
              Verify User
            </button>
          </div>
        </div>
      </div>

      <!-- Reject User Modal -->
      <div v-if="showRejectModal && currentUser" class="modal-overlay" @click.self="closeRejectModal">
        <div class="modal modal-sm">
          <div class="modal-header">
            <h3 class="modal-title">Reject User</h3>
            <button @click="closeRejectModal" class="modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          
          <div class="modal-body">
            <p>
              Are you sure you want to reject <strong>{{ currentUser.role === 'doctor' ? 'Dr. ' : '' }}{{ currentUser.first_name }} {{ currentUser.last_name }}</strong>'s registration?
            </p>
            
            <div class="form-group" style="margin-top: 20px;">
              <label>Rejection Reason <span class="required">*</span></label>
              <textarea
                v-model="rejectionReason"
                placeholder="Please provide a reason for rejection..."
                rows="3"
                required
                style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 10px; font-family: inherit;"
              ></textarea>
              <p class="form-help" style="margin-top: 8px;">
                This reason will be sent to the user via email.
              </p>
            </div>
          </div>

          <div class="modal-actions">
            <button @click="closeRejectModal" class="btn btn-secondary">
              Cancel
            </button>
            <button @click="rejectUser" :disabled="isRejecting || !rejectionReason.trim()" class="btn btn-danger">
              <div v-if="isRejecting" class="spinner spinner-sm"></div>
              Reject User
            </button>
          </div>
        </div>
      </div>

      <!-- Bulk Verify Modal -->
      <div v-if="showBulkVerifyModal" class="modal-overlay" @click.self="closeBulkVerifyModal">
        <div class="modal modal-sm">
          <div class="modal-header">
            <h3 class="modal-title">Bulk Verify Users</h3>
            <button @click="closeBulkVerifyModal" class="modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          
          <div class="modal-body">
            <p>
              Are you sure you want to verify <strong>{{ selectedUsers.length }}</strong> selected users?
            </p>
            
            <div class="form-group" style="margin-top: 20px;">
              <label>Bulk Verification Notes (Optional)</label>
              <textarea
                v-model="bulkVerificationNotes"
                placeholder="Add notes for all selected verifications..."
                rows="3"
                style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 10px; font-family: inherit;"
              ></textarea>
            </div>
          </div>

          <div class="modal-actions">
            <button @click="closeBulkVerifyModal" class="btn btn-secondary">
              Cancel
            </button>
            <button @click="bulkVerifyUsers" :disabled="isBulkVerifying" class="btn btn-primary">
              <div v-if="isBulkVerifying" class="spinner spinner-sm"></div>
              Verify {{ selectedUsers.length }} Users
            </button>
          </div>
        </div>
      </div>

      <!-- Bulk Reject Modal -->
      <div v-if="showBulkRejectModal" class="modal-overlay" @click.self="closeBulkRejectModal">
        <div class="modal modal-sm">
          <div class="modal-header">
            <h3 class="modal-title">Bulk Reject Users</h3>
            <button @click="closeBulkRejectModal" class="modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          
          <div class="modal-body">
            <p>
              Are you sure you want to reject <strong>{{ selectedUsers.length }}</strong> selected users?
            </p>
            <p style="font-size: 13px; color: #64748b; margin-top: 12px;">
              This action cannot be undone. Rejected users will be notified via email with the reason provided.
            </p>
            
            <div class="form-group" style="margin-top: 20px;">
              <label>Rejection Reason <span class="required">*</span></label>
              <textarea
                v-model="bulkRejectionReason"
                placeholder="Please provide a reason for rejecting these users..."
                rows="4"
                required
                style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 10px; font-family: inherit;"
              ></textarea>
              <p class="form-help" style="margin-top: 8px;">
                This reason will be sent to all rejected users via email.
              </p>
            </div>
          </div>

          <div class="modal-actions">
            <button @click="closeBulkRejectModal" class="btn btn-secondary">
              Cancel
            </button>
            <button 
              @click="bulkRejectUsers" 
              :disabled="isBulkRejecting || !bulkRejectionReason.trim()" 
              class="btn btn-danger"
            >
              <div v-if="isBulkRejecting" class="spinner spinner-sm"></div>
              Reject {{ selectedUsers.length }} Users
            </button>
          </div>
        </div>
      </div>
      
      <Toast />
    </div>
  </MainLayout>
</template>

<script setup>
import { ref, computed, onMounted, inject, watch } from 'vue'
import { useRouter } from 'vue-router'
import MainLayout from '../../layout/MainLayout.vue'
import Toast from '../../common/components/Toast.vue'
import * as pendingVerificationsService from '../../services/pendingVerificationsService'

const router = useRouter()
const toast = inject('toast')

// Reactive data
const pendingUsers = ref([])
const loading = ref(true)
const searchQuery = ref('')
const roleFilter = ref('all')
const sortBy = ref('created_at')
const selectedUsers = ref([])

// Stats from backend
const stats = ref({
  total_pending: 0,
  pending_patients: 0,
  pending_nurses: 0,
  pending_doctors: 0
})

// Modal states
const showViewModal = ref(false)
const showVerifyModal = ref(false)
const showRejectModal = ref(false)
const showBulkVerifyModal = ref(false)
const showBulkRejectModal = ref(false)
const currentUser = ref(null)

// Form data
const verificationNotes = ref('')
const rejectionReason = ref('')
const bulkVerificationNotes = ref('')
const bulkRejectionReason = ref('')

// Loading states
const isVerifying = ref(false)
const isRejecting = ref(false)
const isBulkVerifying = ref(false)
const isBulkRejecting = ref(false)

// Computed properties
const filteredUsers = computed(() => pendingUsers.value)

const allSelected = computed(() => {
  return filteredUsers.value.length > 0 && selectedUsers.value.length === filteredUsers.value.length
})

// Methods
const loadPendingUsers = async () => {
  loading.value = true
  try {
    const params = {
      search: searchQuery.value || '',
      role: roleFilter.value,
      sort_by: sortBy.value,
      sort_direction: 'desc'
    }
    
    const response = await pendingVerificationsService.getPendingVerifications(params)
    
    pendingUsers.value = response.data || []
    
    if (response.stats) {
      stats.value = response.stats
    }
    
  } catch (error) {
    console.error('Error loading pending verifications:', error)
    toast.showError('Failed to load pending verifications. Please try again.')
  }
  loading.value = false
}

// Selection management
const toggleSelectAll = () => {
  if (allSelected.value) {
    selectedUsers.value = []
  } else {
    selectedUsers.value = filteredUsers.value.map(user => user.id)
  }
}

const toggleUserSelection = (userId) => {
  const index = selectedUsers.value.indexOf(userId)
  if (index === -1) {
    selectedUsers.value.push(userId)
  } else {
    selectedUsers.value.splice(index, 1)
  }
}

// Modal management
const openViewModal = (user) => {
  currentUser.value = user
  showViewModal.value = true
}

const closeViewModal = () => {
  showViewModal.value = false
  currentUser.value = null
}

const openVerifyModal = (user) => {
  currentUser.value = user
  verificationNotes.value = ''
  showVerifyModal.value = true
}

const closeVerifyModal = () => {
  showVerifyModal.value = false
  currentUser.value = null
  verificationNotes.value = ''
}

const openRejectModal = (user) => {
  currentUser.value = user
  rejectionReason.value = 'We regret to inform you that your application has been declined after careful review. Please feel free to reapply in the future once the necessary requirements are met.'
  showRejectModal.value = true
}

const closeRejectModal = () => {
  showRejectModal.value = false
  currentUser.value = null
  rejectionReason.value = ''
}

const openBulkVerifyModal = () => {
  bulkVerificationNotes.value = ''
  showBulkVerifyModal.value = true
}

const closeBulkVerifyModal = () => {
  showBulkVerifyModal.value = false
  bulkVerificationNotes.value = ''
}

const openBulkRejectModal = () => {
  bulkRejectionReason.value = ''
  showBulkRejectModal.value = true
}

const closeBulkRejectModal = () => {
  showBulkRejectModal.value = false
  bulkRejectionReason.value = ''
}

const verifyFromView = () => {
  closeViewModal()
  openVerifyModal(currentUser.value)
}

const rejectFromView = () => {
  closeViewModal()
  openRejectModal(currentUser.value)
}

// Actions
const verifyUser = async () => {
  isVerifying.value = true
  
  const userToVerify = currentUser.value
  
  try {
    await pendingVerificationsService.verifyUser(userToVerify.id, {
      verification_notes: verificationNotes.value
    })
    
    await loadPendingUsers()
    closeVerifyModal()
    toast.showSuccess(`${userToVerify.role === 'doctor' ? 'Dr. ' : ''}${userToVerify.first_name} ${userToVerify.last_name} verified successfully!`)
  } catch (error) {
    console.error('Error verifying user:', error)
    toast.showError(error.message || 'Failed to verify user')
  }
  
  isVerifying.value = false
}

const rejectUser = async () => {
  isRejecting.value = true
  
  const userToReject = currentUser.value
  
  try {
    await pendingVerificationsService.rejectUser(userToReject.id, {
      rejection_reason: rejectionReason.value
    })
    
    await loadPendingUsers()
    closeRejectModal()
    toast.showSuccess(`${userToReject.role === 'doctor' ? 'Dr. ' : ''}${userToReject.first_name} ${userToReject.last_name} rejected successfully`)
  } catch (error) {
    console.error('Error rejecting user:', error)
    toast.showError(error.message || 'Failed to reject user')
  }
  
  isRejecting.value = false
}

const bulkVerifyUsers = async () => {
  isBulkVerifying.value = true
  
  try {
    const result = await pendingVerificationsService.bulkVerifyUsers({
      user_ids: selectedUsers.value,
      verification_notes: bulkVerificationNotes.value
    })
    
    await loadPendingUsers()
    selectedUsers.value = []
    closeBulkVerifyModal()
    toast.showSuccess(result.message || `Users verified successfully!`)
  } catch (error) {
    console.error('Error bulk verifying users:', error)
    toast.showError(error.message || 'Failed to bulk verify users')
  }
  
  isBulkVerifying.value = false
}

const bulkRejectUsers = async () => {
  isBulkRejecting.value = true
  
  try {
    const result = await pendingVerificationsService.bulkRejectUsers({
      user_ids: selectedUsers.value,
      rejection_reason: bulkRejectionReason.value
    })
    
    await loadPendingUsers()
    selectedUsers.value = []
    closeBulkRejectModal()
    toast.showSuccess(result.message || `Users rejected successfully`)
  } catch (error) {
    console.error('Error bulk rejecting users:', error)
    toast.showError(error.message || 'Failed to bulk reject users')
  }
  
  isBulkRejecting.value = false
}

const exportPendingUsers = async () => {
  try {
    const filters = {
      role: roleFilter.value,
      search: searchQuery.value
    }
    
    const { blob, filename } = await pendingVerificationsService.exportPendingVerifications(filters)
    
    const downloadUrl = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = downloadUrl
    link.download = filename
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(downloadUrl)
    
    toast.showSuccess('Pending verifications exported successfully!')
  } catch (error) {
    console.error('Error exporting pending verifications:', error)
    toast.showError(error.message || 'Failed to export pending verifications. Please try again.')
  }
}

// Utility functions
const getRoleBadgeColor = (role) => {
  const colorMap = {
    'patient': 'badge-primary',
    'nurse': 'badge-success',
    'doctor': 'badge-info'
  }
  return colorMap[role] || 'badge-secondary'
}

const capitalizeFirst = (str) => {
  if (!str) return ''
  return str.charAt(0).toUpperCase() + str.slice(1)
}

const formatSpecialization = (spec) => {
  if (!spec) return 'Not specified'
  return spec.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatTimeAgo = (dateString) => {
  if (!dateString) return 'Unknown'
  const date = new Date(dateString)
  const now = new Date()
  const diffMs = now - date
  const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24))
  
  if (diffDays === 0) return 'Today'
  if (diffDays === 1) return '1 day ago'
  if (diffDays < 7) return `${diffDays} days ago`
  if (diffDays < 30) return `${Math.floor(diffDays / 7)} weeks ago`
  return `${Math.floor(diffDays / 30)} months ago`
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString()
}

const getIdNumber = (user) => {
  if (user.license_number) return user.license_number
  if (user.ghana_card_number) return user.ghana_card_number
  return `ID: ${user.id}`
}

// Debounce timer for search
let searchDebounceTimer = null

// Watch for search query changes with debounce
watch(searchQuery, () => {
  clearTimeout(searchDebounceTimer)
  searchDebounceTimer = setTimeout(() => {
    loadPendingUsers()
  }, 500)
})

// Watch for filter changes (instant reload)
watch([roleFilter, sortBy], () => {
  loadPendingUsers()
})

// Lifecycle
onMounted(() => {
  loadPendingUsers()
})
</script>

<style scoped>
/* Copy ALL styles from Nurses.vue exactly as they are */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

* {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

.users-page {
  padding: 32px;
  background: #f8fafc;
  min-height: 100vh;
  max-width: 100vw;
  overflow-x: hidden;
}

/* Photo Upload Styles */
.photo-upload-container {
  display: flex;
  gap: 24px;
  align-items: flex-start;
}

.photo-preview {
  width: 120px;
  height: 120px;
  border-radius: 16px;
  overflow: hidden;
  border: 2px solid #e2e8f0;
  flex-shrink: 0;
  background: #f8fafc;
  display: flex;
  align-items: center;
  justify-content: center;
}

.preview-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.preview-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #94a3b8;
  gap: 8px;
}

.preview-placeholder svg {
  width: 40px;
  height: 40px;
}

.preview-placeholder p {
  font-size: 12px;
  font-weight: 500;
  margin: 0;
}

.photo-controls {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.btn-sm {
  padding: 8px 16px;
  font-size: 13px;
}

/* Page Header */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 32px;
}

.page-header-content h1 {
  font-size: 32px;
  font-weight: 800;
  color: #0f172a;
  margin: 0 0 6px 0;
  letter-spacing: -0.8px;
}

.page-header-content p {
  font-size: 15px;
  color: #64748b;
  margin: 0;
  font-weight: 400;
}

.page-header-actions {
  display: flex;
  gap: 12px;
}

/* Modern Buttons */
.btn-modern {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px;
  border: none;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-modern svg {
  width: 18px;
  height: 18px;
}

.btn-modern.btn-primary {
  background: #667eea;
  color: white;
}

.btn-modern.btn-primary:hover {
  background: #5a67d8;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn-modern.btn-secondary {
  background: white;
  color: #334155;
  border: 1px solid #e2e8f0;
}

.btn-modern.btn-secondary:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
  transform: translateY(-1px);
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 20px;
  margin-bottom: 32px;
}

.stat-card {
  background: white;
  padding: 24px;
  border-radius: 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  border: 1px solid #f1f5f9;
  display: flex;
  gap: 16px;
  transition: all 0.2s;
}

.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 24px rgba(0, 0, 0, 0.08);
  border-color: #e2e8f0;
}

.stat-icon {
  width: 56px;
  height: 56px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.stat-icon svg {
  width: 28px;
  height: 28px;
  color: white;
}

.stat-icon.blue {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.stat-icon.green {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.stat-icon.yellow {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.stat-icon.purple {
  background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
}

.stat-content {
  flex: 1;
}

.stat-label {
  font-size: 13px;
  color: #64748b;
  font-weight: 600;
  margin-bottom: 6px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.stat-value {
  font-size: 32px;
  font-weight: 800;
  color: #0f172a;
  line-height: 1;
  margin-bottom: 6px;
  letter-spacing: -1px;
}

.stat-change {
  font-size: 13px;
  font-weight: 500;
}

.stat-change.positive {
  color: #10b981;
}

.stat-change.neutral {
  color: #f59e0b;
}

/* Filters Section */
.filters-section {
  background: white;
  padding: 20px;
  border-radius: 16px;
  margin-bottom: 24px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  border: 1px solid #f1f5f9;
  display: flex;
  gap: 16px;
  flex-wrap: wrap;
}

.search-wrapper {
  flex: 1;
  min-width: 300px;
  position: relative;
}

.search-icon {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  width: 20px;
  height: 20px;
  color: #94a3b8;
  pointer-events: none;
}

.search-input {
  width: 100%;
  padding: 10px 14px 10px 44px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  font-size: 14px;
  transition: all 0.2s;
  font-weight: 500;
}

.search-input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.filters-group {
  display: flex;
  gap: 12px;
}

.filter-select {
  padding: 10px 14px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 500;
  background: white;
  cursor: pointer;
  transition: all 0.2s;
  min-width: 150px;
}

.filter-select:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

/* Loading State */
.loading-state {
  background: white;
  padding: 60px;
  border-radius: 16px;
  text-align: center;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  border: 1px solid #f1f5f9;
}

.loading-spinner {
  width: 48px;
  height: 48px;
  border: 4px solid #f1f5f9;
  border-top: 4px solid #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 16px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.loading-text {
  color: #64748b;
  font-size: 14px;
  font-weight: 500;
}

/* Modern Table */
.users-table-container {
  background: white;
  border-radius: 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  border: 1px solid #f1f5f9;
  overflow: hidden;
}

.table-wrapper {
  overflow-x: auto;
}

.modern-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
}

.modern-table thead {
  background: #f8fafc;
}

.modern-table th {
  padding: 16px 20px;
  text-align: left;
  font-size: 12px;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  border-bottom: 1px solid #e2e8f0;
}

.modern-table tbody tr {
  transition: all 0.2s;
  border-bottom: 1px solid #f1f5f9;
}

.modern-table tbody tr:hover {
  background: #f8fafc;
}

.modern-table td {
  padding: 16px 20px;
  font-size: 14px;
  color: #334155;
  vertical-align: middle;
}

/* User Cell */
.user-cell {
  display: flex;
  align-items: center;
  gap: 12px;
}

.user-avatar-table {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  object-fit: cover;
  border: 2px solid #e2e8f0;
  flex-shrink: 0;
}

.user-name-table {
  font-weight: 600;
  color: #0f172a;
  margin-bottom: 2px;
  font-size: 14px;
}

.user-id-table {
  font-size: 12px;
  color: #94a3b8;
  font-weight: 500;
}

/* Contact Cell */
.contact-cell {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.contact-primary {
  font-size: 14px;
  color: #334155;
  font-weight: 500;
}

.contact-secondary {
  font-size: 13px;
  color: #94a3b8;
}

.text-secondary {
  color: #94a3b8;
  font-weight: 500;
}

.capitalize {
  text-transform: capitalize;
}

/* Modern Badges */
.modern-badge {
  display: inline-flex;
  align-items: center;
  padding: 6px 12px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 700;
  text-transform: capitalize;
  letter-spacing: 0.3px;
}

.modern-badge.badge-primary {
  background: #dbeafe;
  color: #1e40af;
}

.modern-badge.badge-success {
  background: #d1fae5;
  color: #065f46;
}

.modern-badge.badge-warning {
  background: #fef3c7;
  color: #92400e;
}

.modern-badge.badge-danger {
  background: #fee2e2;
  color: #991b1b;
}

.modern-badge.badge-secondary {
  background: #f1f5f9;
  color: #475569;
}

.modern-badge.badge-info {
  background: #e0e7ff;
  color: #3730a3;
}

/* Action Cell */
.action-cell {
  position: relative;
}


/* Action Buttons for Pending Verifications */
.action-buttons {
  display: flex;
  gap: 8px;
  align-items: center;
}

.action-btn-icon {
  width: 36px;
  height: 36px;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}

.action-btn-icon svg {
  width: 18px;
  height: 18px;
  color: #64748b;
}

.action-btn-icon:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
  transform: translateY(-1px);
}

.action-btn-icon.success {
  border-color: #d1fae5;
  background: #f0fdf4;
}

.action-btn-icon.success svg {
  color: #059669;
}

.action-btn-icon.success:hover {
  background: #d1fae5;
  border-color: #10b981;
}

.action-btn-icon.danger {
  border-color: #fee2e2;
  background: #fef2f2;
}

.action-btn-icon.danger svg {
  color: #dc2626;
}

.action-btn-icon.danger:hover {
  background: #fee2e2;
  border-color: #ef4444;
}


/* Modern Dropdown */
.modern-dropdown {
  position: absolute;
  right: 0;
  top: calc(100% + 8px);
  background: white;
  border-radius: 12px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12), 0 0 0 1px rgba(0, 0, 0, 0.05);
  min-width: 200px;
  z-index: 1000;
  padding: 8px;
  animation: slideInFadeMenu 0.2s ease-out;
}

@keyframes slideInFadeMenu {
  from {
    opacity: 0;
    transform: translateY(-8px) scale(0.98);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.form-group label .required {
  color: #ef4444;
  font-weight: 700;
  margin-left: 2px;
}

.dropdown-item-modern {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  padding: 10px 12px;
  background: none;
  border: none;
  text-align: left;
  cursor: pointer;
  color: #334155;
  font-size: 14px;
  font-weight: 500;
  border-radius: 8px;
  transition: all 0.2s;
}

.dropdown-item-modern:hover {
  background: #f8fafc;
}

.dropdown-item-modern svg {
  width: 18px;
  height: 18px;
}

.dropdown-item-modern.success {
  color: #059669;
}

.dropdown-item-modern.success:hover {
  background: #f0fdf4;
}

.dropdown-item-modern.warning {
  color: #d97706;
}

.dropdown-item-modern.warning:hover {
  background: #fffbeb;
}

.dropdown-item-modern.danger {
  color: #dc2626;
}

.dropdown-item-modern.danger:hover {
  background: #fef2f2;
}

.dropdown-divider {
  height: 1px;
  background: #e2e8f0;
  margin: 8px 0;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 60px 24px;
}

.empty-state svg {
  width: 64px;
  height: 64px;
  color: #cbd5e1;
  margin: 0 auto 16px;
}

.empty-state h3 {
  font-size: 18px;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 8px 0;
}

.empty-state p {
  font-size: 14px;
  color: #64748b;
  margin: 0;
}

/* Pagination */
.pagination-container {
  padding: 20px 24px;
  border-top: 1px solid #f1f5f9;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 16px;
}

.pagination-info {
  font-size: 14px;
  color: #64748b;
  font-weight: 500;
}

.pagination-controls {
  display: flex;
  align-items: center;
  gap: 12px;
}

.pagination-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 14px;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  color: #334155;
  cursor: pointer;
  transition: all 0.2s;
}

.pagination-btn:hover:not(:disabled) {
  background: #f8fafc;
  border-color: #cbd5e1;
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pagination-btn svg {
  width: 16px;
  height: 16px;
}

.pagination-pages {
  display: flex;
  gap: 4px;
}

.pagination-page {
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  color: #334155;
  cursor: pointer;
  transition: all 0.2s;
}

.pagination-page:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
}

.pagination-page.active {
  background: #667eea;
  border-color: #667eea;
  color: white;
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 20px;
  animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.modal {
  background: white;
  border-radius: 20px;
  max-width: 600px;
  width: 100%;
  max-height: 90vh;
  overflow: hidden;
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
  animation: slideUp 0.3s ease;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px) scale(0.98);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.modal-lg {
  max-width: 800px;
}

.modal-xl {
  max-width: 1000px;
}

.modal-sm {
  max-width: 450px;
}

.modal-header {
  padding: 24px 28px;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-title {
  font-size: 20px;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
  letter-spacing: -0.4px;
}

.modal-close {
  width: 36px;
  height: 36px;
  border: none;
  background: #f8fafc;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}

.modal-close:hover {
  background: #f1f5f9;
  transform: scale(1.05);
}

.modal-close svg {
  width: 20px;
  height: 20px;
  color: #64748b;
}

.modal-body {
  padding: 28px;
  max-height: calc(90vh - 160px);
  overflow-y: auto;
}

.modal-actions {
  padding: 20px 28px;
  border-top: 1px solid #e2e8f0;
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  background: #f8fafc;
}

/* Form Styles */
.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-group label {
  font-size: 13px;
  font-weight: 600;
  color: #334155;
  margin-bottom: 8px;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

.form-group input,
.form-group select,
.form-group textarea {
  padding: 10px 14px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.2s;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-group textarea {
  resize: vertical;
  font-family: inherit;
}

.form-section-header {
  grid-column: 1 / -1;
  margin-top: 12px;
  padding-top: 20px;
  border-top: 1px solid #e2e8f0;
}

.form-section-title {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
  letter-spacing: -0.3px;
}

.form-grid-full {
  grid-column: 1 / -1;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
  width: 18px;
  height: 18px;
  cursor: pointer;
}

.checkbox-text {
  font-size: 14px;
  font-weight: 500;
  color: #334155;
}

.btn {
  padding: 10px 20px;
  border: none;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.btn-primary {
  background: #667eea;
  color: white;
}

.btn-primary:hover {
  background: #5a67d8;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn-secondary {
  background: white;
  color: #334155;
  border: 1px solid #e2e8f0;
}

.btn-secondary:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
}

.btn-danger {
  background: #ef4444;
  color: white;
}

.btn-danger:hover {
  background: #dc2626;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none !important;
}

.spinner {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

.spinner-sm {
  width: 14px;
  height: 14px;
}

/* View Modal Styles */
.user-view-grid {
  display: grid;
  grid-template-columns: 300px 1fr;
  gap: 32px;
}

.user-profile-section {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.profile-avatar-large {
  width: 120px;
  height: 120px;
  border-radius: 20px;
  object-fit: cover;
  border: 4px solid #e2e8f0;
  margin-bottom: 16px;
}

.profile-name-view {
  font-size: 20px;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 12px 0;
  letter-spacing: -0.4px;
}

.profile-contact-view {
  margin-top: 20px;
  width: 100%;
}

.contact-item-view {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px;
  background: #f8fafc;
  border-radius: 10px;
  margin-bottom: 8px;
}

.contact-item-view svg {
  width: 18px;
  height: 18px;
  color: #64748b;
  flex-shrink: 0;
}

.contact-item-view span {
  font-size: 13px;
  color: #334155;
  font-weight: 500;
}

.details-section-view {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.details-group {
  background: #f8fafc;
  padding: 20px;
  border-radius: 12px;
}

.details-header {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 16px 0;
  letter-spacing: -0.3px;
}

.details-grid-view {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}

.detail-item-view {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.detail-item-view label {
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.detail-item-view p {
  font-size: 14px;
  color: #0f172a;
  font-weight: 500;
  margin: 0;
}

.form-help {
  font-size: 13px;
  color: #64748b;
  margin: 8px 0 0 0;
  line-height: 1.5;
}

/* Password Option Card */
.password-option-card {
  background: #f8fafc;
  padding: 16px;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  margin-bottom: 20px;
}

.password-option-card .checkbox-label {
  margin-bottom: 8px;
}

.password-option-card .form-help {
  margin-left: 28px;
  margin-top: 8px;
}
/* Medium-Large Screens (13-15 inch laptops: 1366px - 1440px) */
@media (max-width: 1440px) {
  .users-page {
    padding: 24px;
  }

  .page-header {
    margin-bottom: 24px;
  }

  .page-header-content h1 {
    font-size: 28px;
  }

  .page-header-content p {
    font-size: 14px;
  }

  .btn-modern {
    padding: 9px 16px;
    font-size: 13px;
    min-height: 40px;
  }

  .btn-modern svg {
    width: 16px;
    height: 16px;
  }

  .stats-grid {
    gap: 16px;
    margin-bottom: 24px;
  }

  .stat-card {
    padding: 20px;
  }

  .stat-icon {
    width: 52px;
    height: 52px;
  }

  .stat-icon svg {
    width: 26px;
    height: 26px;
  }

  .stat-label {
    font-size: 12px;
  }

  .stat-value {
    font-size: 28px;
  }

  .stat-change {
    font-size: 12px;
  }

  .filters-section {
    padding: 18px;
    margin-bottom: 20px;
  }

  .search-input,
  .filter-select {
    padding: 9px 12px 9px 40px;
    font-size: 13px;
    min-height: 40px;
  }

  .search-icon {
    left: 12px;
    width: 18px;
    height: 18px;
  }

  .filter-select {
    min-width: 140px;
    padding: 9px 12px;
  }

  .modern-table th {
    padding: 14px 18px;
    font-size: 11px;
  }

  .modern-table td {
    padding: 14px 18px;
    font-size: 13px;
  }

  .user-avatar-table {
    width: 40px;
    height: 40px;
  }

  .user-name-table {
    font-size: 13px;
  }

  .user-id-table {
    font-size: 11px;
  }

  .contact-primary {
    font-size: 13px;
  }

  .contact-secondary {
    font-size: 12px;
  }

  .modern-badge {
    padding: 5px 10px;
    font-size: 11px;
  }

  .action-btn {
    width: 36px;
    height: 36px;
  }

  .action-btn svg {
    width: 16px;
    height: 16px;
  }

  .dropdown-item-modern {
    padding: 9px 10px;
    font-size: 13px;
    min-height: 40px;
  }

  .dropdown-item-modern svg {
    width: 16px;
    height: 16px;
  }

  .pagination-container {
    padding: 18px 20px;
  }

  .pagination-info {
    font-size: 13px;
  }

  .pagination-btn {
    padding: 7px 12px;
    font-size: 13px;
    min-height: 36px;
  }

  .pagination-page {
    width: 36px;
    height: 36px;
    font-size: 13px;
  }

  .modal-header,
  .modal-body {
    padding: 22px 24px;
  }

  .modal-actions {
    padding: 18px 24px;
  }

  .modal-title {
    font-size: 18px;
  }

  .form-group label {
    font-size: 12px;
    margin-bottom: 7px;
  }

  .form-group input,
  .form-group select,
  .form-group textarea {
    padding: 9px 12px;
    font-size: 13px;
    min-height: 40px;
  }

  .btn {
    padding: 9px 18px;
    font-size: 13px;
    min-height: 40px;
  }

  .btn-sm {
    padding: 7px 14px;
    font-size: 12px;
    min-height: 36px;
  }

  .photo-preview {
    width: 110px;
    height: 110px;
  }

  .preview-placeholder svg {
    width: 36px;
    height: 36px;
  }

  .preview-placeholder p {
    font-size: 11px;
  }
}

/* Smaller Laptops (1200px - 1366px) */
@media (max-width: 1366px) {
  .users-page {
    padding: 20px;
  }

  .page-header-content h1 {
    font-size: 26px;
  }

  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .stat-card {
    padding: 18px;
  }

  .stat-icon {
    width: 48px;
    height: 48px;
  }

  .stat-icon svg {
    width: 24px;
    height: 24px;
  }

  .stat-value {
    font-size: 26px;
  }

  .modern-table th {
    padding: 12px 16px;
  }

  .modern-table td {
    padding: 12px 16px;
  }

  .profile-avatar-large {
    width: 110px;
    height: 110px;
  }

  .profile-name-view {
    font-size: 18px;
  }
}

/* Tablets and Small Laptops (1024px and below) */
@media (max-width: 1024px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .user-view-grid {
    grid-template-columns: 1fr;
  }

  .user-profile-section {
    padding-bottom: 24px;
    border-bottom: 1px solid #e2e8f0;
  }
}

/* Mobile (768px and below) */
@media (max-width: 768px) {
  .users-page {
    padding: 16px;
    max-width: 100vw;
    overflow-x: hidden;
  }
  
  .page-header {
    flex-direction: column;
    align-items: stretch;
    max-width: 100%;
    overflow: hidden;
  }

  .page-header-content h1 {
    font-size: 22px;
  }

  .page-header-content p {
    font-size: 13px;
  }

  .page-header-actions {
    width: 100%;
    max-width: 100%;
    flex-direction: column;
  }

  .btn-modern {
    flex: 1;
    width: 100%;
    justify-content: center;
  }
  .checkbox-input{
    min-height: 15px;
    min-width: 15px;
  }

  .stat-card {
    padding: 18px;
  }

  .stat-value {
    font-size: 24px;
  }

  .stat-icon {
    width: 44px;
    height: 44px;
  }

  .stat-icon svg {
    width: 22px;
    height: 22px;
  }
  
  .filters-section {
    flex-direction: column;
    padding: 16px;
  }
  
  .search-wrapper {
    min-width: 100%;
  }
  
  .filters-group {
    flex-direction: column;
    width: 100%;
    gap: 10px;
  }
  
  .filter-select {
    width: 100%;
  }

  .table-wrapper {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }

  .modern-table {
    min-width: 800px;
  }

  .pagination-container {
    flex-direction: column;
    align-items: stretch;
    padding: 16px;
  }

  .pagination-info {
    text-align: center;
    width: 100%;
    font-size: 12px;
  }

  .pagination-controls {
    justify-content: center;
    width: 100%;
    flex-wrap: wrap;
  }

  .details-grid-view {
    grid-template-columns: 1fr;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }

  .modal-actions {
    flex-direction: column-reverse;
  }

  .modal-actions .btn {
    width: 100%;
  }

  .modal-header,
  .modal-body {
    padding: 20px;
  }

  .modal-actions {
    padding: 16px 20px;
  }

  .photo-upload-container {
    flex-direction: column;
    gap: 16px;
  }

  .photo-preview {
    width: 100px;
    height: 100px;
    margin: 0 auto;
  }

  .photo-controls {
    width: 100%;
  }

  .photo-controls .btn {
    width: 100%;
  }
}

/* Small Mobile (480px and below) */
@media (max-width: 480px) {
  .users-page {
    padding: 12px;
  }

  .page-header-content h1 {
    font-size: 20px;
  }

  .page-header-content p {
    font-size: 12px;
  }

  .stat-card {
    padding: 16px;
    max-width: 100%;
  }

  .stat-icon {
    width: 40px;
    height: 40px;
  }

  .stat-icon svg {
    width: 20px;
    height: 20px;
  }

  .stat-value {
    font-size: 22px;
  }

  .stat-label {
    font-size: 11px;
  }

  .filters-section {
    padding: 14px;
  }

  .search-input,
  .filter-select {
    font-size: 12px;
    padding: 8px 10px 8px 36px;
    min-height: 38px;
  }

  .filter-select {
    padding: 8px 10px;
  }

  .modal {
    border-radius: 16px;
  }

  .modal-header,
  .modal-body,
  .modal-actions {
    padding: 18px;
  }

  .modal-title {
    font-size: 17px;
  }

  .modern-table {
    min-width: 700px;
  }

  .user-avatar-table {
    width: 36px;
    height: 36px;
  }

  .user-name-table {
    font-size: 12px;
  }

  .modern-badge {
    font-size: 10px;
    padding: 4px 8px;
  }

  .pagination-page {
    width: 34px;
    height: 34px;
    font-size: 12px;
  }

  .pagination-btn {
    padding: 7px 10px;
    min-height: 34px;
  }

  .profile-avatar-large {
    width: 100px;
    height: 100px;
  }

  .profile-name-view {
    font-size: 17px;
  }

  .form-group input,
  .form-group select,
  .form-group textarea {
    font-size: 12px;
    padding: 8px 10px;
    min-height: 38px;
  }

  .btn {
    font-size: 12px;
    padding: 8px 16px;
    min-height: 38px;
  }

  .btn-sm {
    font-size: 11px;
    padding: 6px 12px;
    min-height: 34px;
  }

  .photo-preview {
    width: 90px;
    height: 90px;
  }

  .preview-placeholder svg {
    width: 32px;
    height: 32px;
  }

  .preview-placeholder p {
    font-size: 10px;
  }
}

/* Extra Small (360px and below) */
@media (max-width: 360px) {
  .users-page {
    padding: 10px;
  }

  .page-header-content h1 {
    font-size: 18px;
  }

  .stat-value {
    font-size: 20px;
  }

  .stat-card {
    padding: 14px;
  }

  .modern-table {
    min-width: 600px;
  }

  .modern-badge {
    font-size: 9px;
    padding: 3px 7px;
  }

  .pagination-page {
    width: 32px;
    height: 32px;
    font-size: 11px;
  }
}

/* Tablet Landscape */
@media (max-width: 1024px) and (orientation: landscape) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>