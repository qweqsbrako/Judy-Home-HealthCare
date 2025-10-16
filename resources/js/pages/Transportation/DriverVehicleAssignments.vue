<template>
  <MainLayout>
    <div class="assignments-page">
      <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Page Header -->
        <div class="page-header">
          <div class="page-header-content">
            <h1>Driver-Vehicle Assignments</h1>
            <p>Manage assignments between drivers and vehicles</p>
          </div>
          <div class="page-header-actions">
            <button @click="exportAssignments" class="btn btn-secondary">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              Export
            </button>
            <button @click="openCreateModal" class="btn btn-primary">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
              </svg>
              New Assignment
            </button>
          </div>
        </div>

        <!-- Quick Stats -->
        <div class="stats-section">
          <div class="stats-grid">
            <div class="stat-card">
              <div class="stat-icon stat-icon-primary">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div class="stat-content">
                <div class="stat-number">{{ stats.active_assignments || 0 }}</div>
                <div class="stat-label">Active Assignments</div>
              </div>
            </div>
            
            <div class="stat-card">
              <div class="stat-icon stat-icon-warning">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div class="stat-content">
                <div class="stat-number">{{ stats.expiring_assignments || 0 }}</div>
                <div class="stat-label">Expiring Soon</div>
              </div>
            </div>
            
            <div class="stat-card">
              <div class="stat-icon stat-icon-info">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
              </div>
              <div class="stat-content">
                <div class="stat-number">{{ stats.unassigned_drivers || 0 }}</div>
                <div class="stat-label">Unassigned Drivers</div>
              </div>
            </div>
            
            <div class="stat-card">
              <div class="stat-icon stat-icon-secondary">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </div>
              <div class="stat-content">
                <div class="stat-number">{{ stats.unassigned_vehicles || 0 }}</div>
                <div class="stat-label">Unassigned Vehicles</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Filters and Search -->
        <div class="filters-section">
          <div class="filters-content">
            <div class="search-wrapper">
              <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
              <input
                type="text"
                placeholder="Search assignments..."
                v-model="searchQuery"
                class="search-input"
              />
            </div>
            <div class="filters-group">
              <select v-model="statusFilter" class="filter-select">
                <option value="all">All Status</option>
                <option value="active">Active</option>
                <option value="temporary">Temporary</option>
                <option value="inactive">Inactive</option>
              </select>
              <select v-model="activeFilter" class="filter-select">
                <option value="all">All Assignments</option>
                <option value="true">Active Only</option>
                <option value="false">Inactive Only</option>
              </select>
              <input
                type="date"
                v-model="dateFromFilter"
                class="filter-select"
                placeholder="From Date"
              />
              <input
                type="date"
                v-model="dateToFilter"
                class="filter-select"
                placeholder="To Date"
              />
            </div>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="loading-state">
          <div class="loading-spinner"></div>
          <p class="loading-text">Loading assignments...</p>
        </div>

        <!-- Assignments Table -->
        <div v-else class="assignments-table-container">
          <div class="overflow-x-auto">
            <table class="assignments-table">
              <thead>
                <tr>
                  <th>Assignment</th>
                  <th>Driver</th>
                  <th>Vehicle</th>
                  <th>Status</th>
                  <th>Duration</th>
                  <th>Assigned By</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="assignment in filteredAssignments" :key="assignment.id">
                  <td>
                    <div class="assignment-info">
                      <div class="assignment-id">Assignment #{{ assignment.id }}</div>
                      <div class="assignment-date">{{ formatDateTime(assignment.assigned_at) }}</div>
                    </div>
                  </td>
                  <td>
                    <div class="driver-info">
                      <div class="driver-name">{{ assignment.driver.full_name }}</div>
                      <div class="driver-phone">{{ assignment.driver.phone }}</div>
                    </div>
                  </td>
                  <td>
                    <div class="vehicle-info">
                      <div class="vehicle-number">{{ assignment.vehicle.registration_number }}</div>
                      <span :class="'badge badge-sm ' + getVehicleTypeBadgeColor(assignment.vehicle.vehicle_type)">
                        {{ capitalizeFirst(assignment.vehicle.vehicle_type) }}
                      </span>
                    </div>
                  </td>
                  <td>
                    <div class="status-info">
                      <span :class="'badge ' + getAssignmentStatusBadgeColor(assignment)">
                        {{ assignment.status_label }}
                      </span>
                      <div v-if="assignment.is_temporary && assignment.effective_until" class="expiry-info">
                        <span class="badge badge-sm badge-warning">
                          Expires {{ formatDate(assignment.effective_until) }}
                        </span>
                      </div>
                    </div>
                  </td>
                  <td>
                    <div class="duration-info">
                      <span class="duration-text">{{ assignment.duration }}</span>
                      <div v-if="assignment.is_active" class="active-indicator">
                        <span class="badge badge-sm badge-success">Active</span>
                      </div>
                    </div>
                  </td>
                  <td>
                    <div class="assigned-by-info">
                      <div class="assigned-by-name">{{ assignment.assigned_by?.name || 'System' }}</div>
                      <div class="assigned-date">{{ formatDate(assignment.assigned_at) }}</div>
                    </div>
                  </td>
                  <td>
                    <div class="action-dropdown">
                      <button
                        @click="toggleDropdown(assignment.id)"
                        class="btn btn-secondary btn-sm"
                        style="min-width: auto; padding: 0.5rem;"
                      >
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                          <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                        </svg>
                      </button>
                      <div v-show="activeDropdown === assignment.id" class="dropdown-menu">
                        <button @click="openViewModal(assignment)" class="dropdown-item">
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                          </svg>
                          View Details
                        </button>
                        <button @click="openEditModal(assignment)" class="dropdown-item">
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                          </svg>
                          Edit Assignment
                        </button>
                        <div class="dropdown-divider"></div>
                        <button
                          v-if="assignment.is_active"
                          @click="deactivateAssignment(assignment)"
                          class="dropdown-item dropdown-item-warning"
                        >
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                          </svg>
                          Deactivate
                        </button>
                        <button
                          v-if="!assignment.is_active"
                          @click="activateAssignment(assignment)"
                          class="dropdown-item dropdown-item-success"
                        >
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                          </svg>
                          Activate
                        </button>
                        <button
                          v-if="assignment.is_temporary"
                          @click="openExtendModal(assignment)"
                          class="dropdown-item dropdown-item-info"
                        >
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                          </svg>
                          Extend Assignment
                        </button>
                        <button
                          v-if="assignment.is_temporary"
                          @click="makePermanent(assignment)"
                          class="dropdown-item dropdown-item-success"
                        >
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                          </svg>
                          Make Permanent
                        </button>
                      </div>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          
          <div v-if="filteredAssignments.length === 0" class="empty-state">
            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3>No assignments found</h3>
            <p>
              {{ hasActiveFilters ? 'Try adjusting your search or filters.' : 'Get started by creating a new assignment.' }}
            </p>
          </div>
        </div>

        <!-- Create/Edit Assignment Modal -->
        <div v-if="showAssignmentModal" class="modal-overlay">
          <div class="modal modal-lg">
            <div class="modal-header">
              <h2 class="modal-title">
                <svg class="modal-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                {{ isEditing ? 'Edit Assignment' : 'Create New Assignment' }}
              </h2>
              <button @click="closeAssignmentModal" class="modal-close">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <form @submit.prevent="saveAssignment" id="assignmentForm">
              <div class="modal-body">
                <div class="form-grid">
                  <div class="form-group">
                    <label>Driver *</label>
                    <select v-model="assignmentForm.driver_id" required :disabled="isEditing">
                      <option value="">Select a driver...</option>
                      <option
                        v-for="driver in availableDrivers"
                        :key="driver.id"
                        :value="driver.id"
                      >
                        {{ driver.full_name }} ({{ driver.phone }})
                      </option>
                    </select>
                  </div>

                  <div class="form-group">
                    <label>Vehicle *</label>
                    <select v-model="assignmentForm.vehicle_id" required :disabled="isEditing">
                      <option value="">Select a vehicle...</option>
                      <option
                        v-for="vehicle in availableVehicles"
                        :key="vehicle.id"
                        :value="vehicle.id"
                      >
                        {{ vehicle.registration_number }} ({{ vehicle.vehicle_type }})
                      </option>
                    </select>
                  </div>

                  <div class="form-group">
                    <label>Assignment Type</label>
                    <select v-model="assignmentForm.status">
                      <option value="active">Permanent Assignment</option>
                      <option value="temporary">Temporary Assignment</option>
                    </select>
                  </div>

                  <div class="form-group">
                    <label>Effective From</label>
                    <input
                      type="datetime-local"
                      v-model="assignmentForm.effective_from"
                    />
                  </div>

                  <div v-if="assignmentForm.status === 'temporary'" class="form-group">
                    <label>Effective Until *</label>
                    <input
                      type="datetime-local"
                      v-model="assignmentForm.effective_until"
                      :required="assignmentForm.status === 'temporary'"
                    />
                  </div>

                  <div class="form-group form-grid-full">
                    <label>Assignment Notes</label>
                    <textarea
                      v-model="assignmentForm.assignment_notes"
                      rows="3"
                      placeholder="Any notes about this assignment..."
                    ></textarea>
                  </div>
                </div>
              </div>

              <div class="modal-actions">
                <button type="button" @click="closeAssignmentModal" class="btn btn-secondary">
                  Cancel
                </button>
                <button 
                  type="submit" 
                  form="assignmentForm" 
                  :disabled="isSaving" 
                  class="btn btn-primary"
                >
                  <div v-if="isSaving" class="spinner spinner-sm"></div>
                  {{ isEditing ? 'Update Assignment' : 'Create Assignment' }}
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- View Assignment Modal -->
        <div v-if="showViewModal && currentAssignment" class="modal-overlay">
          <div class="modal modal-lg">
            <div class="modal-header">
              <h2 class="modal-title">
                <svg class="modal-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Assignment Details - #{{ currentAssignment.id }}
              </h2>
              <button @click="closeViewModal" class="modal-close">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <div class="modal-body">
              <div class="assignment-view">
                <div class="assignment-details-grid">
                  <!-- Driver Details -->
                  <div class="assignment-card">
                    <h4 class="card-title">Driver Information</h4>
                    <div class="driver-profile">
                      <div class="driver-avatar">
                        <img :src="currentAssignment.driver.avatar_url" :alt="currentAssignment.driver.full_name" />
                      </div>
                      <div class="driver-details">
                        <h5>{{ currentAssignment.driver.full_name }}</h5>
                        <p>{{ currentAssignment.driver.phone }}</p>
                        <p v-if="currentAssignment.driver.email">{{ currentAssignment.driver.email }}</p>
                        <div class="driver-stats">
                          <span class="stat">Rating: {{ currentAssignment.driver.average_rating || 'N/A' }}</span>
                          <span class="stat">Trips: {{ currentAssignment.driver.total_trips || 0 }}</span>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Vehicle Details -->
                  <div class="assignment-card">
                    <h4 class="card-title">Vehicle Information</h4>
                    <div class="vehicle-profile">
                      <div class="vehicle-info">
                        <h5>{{ currentAssignment.vehicle.registration_number }}</h5>
                        <p>{{ currentAssignment.vehicle.make }} {{ currentAssignment.vehicle.model }} {{ currentAssignment.vehicle.year }}</p>
                        <p>Color: {{ currentAssignment.vehicle.vehicle_color }}</p>
                        <span :class="'badge ' + getVehicleTypeBadgeColor(currentAssignment.vehicle.vehicle_type)">
                          {{ capitalizeFirst(currentAssignment.vehicle.vehicle_type) }}
                        </span>
                      </div>
                    </div>
                  </div>

                  <!-- Assignment Details -->
                  <div class="assignment-card assignment-card-full">
                    <h4 class="card-title">Assignment Details</h4>
                    <div class="assignment-info-grid">
                      <div class="info-item">
                        <label>Status:</label>
                        <span :class="'badge ' + getAssignmentStatusBadgeColor(currentAssignment)">
                          {{ currentAssignment.status_label }}
                        </span>
                      </div>
                      <div class="info-item">
                        <label>Duration:</label>
                        <span>{{ currentAssignment.duration }}</span>
                      </div>
                      <div class="info-item">
                        <label>Assigned At:</label>
                        <span>{{ formatDateTime(currentAssignment.assigned_at) }}</span>
                      </div>
                      <div class="info-item">
                        <label>Assigned By:</label>
                        <span>{{ currentAssignment.assigned_by?.name || 'System' }}</span>
                      </div>
                      <div v-if="currentAssignment.effective_from" class="info-item">
                        <label>Effective From:</label>
                        <span>{{ formatDateTime(currentAssignment.effective_from) }}</span>
                      </div>
                      <div v-if="currentAssignment.effective_until" class="info-item">
                        <label>Effective Until:</label>
                        <span>{{ formatDateTime(currentAssignment.effective_until) }}</span>
                      </div>
                      <div v-if="currentAssignment.unassigned_at" class="info-item">
                        <label>Unassigned At:</label>
                        <span>{{ formatDateTime(currentAssignment.unassigned_at) }}</span>
                      </div>
                      <div v-if="currentAssignment.unassigned_by" class="info-item">
                        <label>Unassigned By:</label>
                        <span>{{ currentAssignment.unassigned_by.name }}</span>
                      </div>
                    </div>
                    
                    <div v-if="currentAssignment.assignment_notes" class="assignment-notes">
                      <label>Assignment Notes:</label>
                      <p>{{ currentAssignment.assignment_notes }}</p>
                    </div>
                    
                    <div v-if="currentAssignment.unassignment_reason" class="unassignment-reason">
                      <label>Unassignment Reason:</label>
                      <p>{{ currentAssignment.unassignment_reason }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="modal-actions">
              <button @click="closeViewModal" class="btn btn-secondary">
                Close
              </button>
              <button v-if="currentAssignment.is_active" @click="editFromView" class="btn btn-primary">
                Edit Assignment
              </button>
            </div>
          </div>
        </div>

        <!-- Extend Assignment Modal -->
        <div v-if="showExtendModal && currentAssignment" class="modal-overlay">
          <div class="modal modal-sm">
            <div class="modal-header">
              <h3 class="modal-title">
                <svg class="modal-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Extend Assignment
              </h3>
              <button @click="closeExtendModal" class="modal-close">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
            
            <form @submit.prevent="extendAssignment">
              <div class="modal-body">
                <p>
                  Extend assignment for <strong>{{ currentAssignment.driver.full_name }}</strong> 
                  and <strong>{{ currentAssignment.vehicle.registration_number }}</strong>
                </p>
                <div class="form-group">
                  <label>New End Date *</label>
                  <input
                    type="datetime-local"
                    v-model="extendForm.new_end_date"
                    required
                  />
                </div>
                <div class="form-group">
                  <label>Extension Notes</label>
                  <textarea
                    v-model="extendForm.notes"
                    rows="2"
                    placeholder="Reason for extension..."
                  ></textarea>
                </div>
              </div>

              <div class="modal-actions">
                <button type="button" @click="closeExtendModal" class="btn btn-secondary">
                  Cancel
                </button>
                <button
                  type="submit"
                  :disabled="isSaving"
                  class="btn btn-primary"
                >
                  <div v-if="isSaving" class="spinner spinner-sm"></div>
                  Extend Assignment
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Toast Component -->
    <Toast />
  </MainLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, inject } from 'vue'
import { useRouter } from 'vue-router'
import MainLayout from '../../layout/MainLayout.vue'
import Toast from '../../common/components/Toast.vue'

const router = useRouter()
const toast = inject('toast')

// Reactive data
const assignments = ref([])
const availableDrivers = ref([])
const availableVehicles = ref([])
const stats = ref({})
const loading = ref(true)
const searchQuery = ref('')
const statusFilter = ref('all')
const activeFilter = ref('all')
const dateFromFilter = ref('')
const dateToFilter = ref('')

// Modal states
const showAssignmentModal = ref(false)
const showViewModal = ref(false)
const showExtendModal = ref(false)
const isEditing = ref(false)
const currentAssignment = ref(null)
const isSaving = ref(false)

// Dropdown state
const activeDropdown = ref(null)

// Form data
const assignmentForm = ref({
  driver_id: '',
  vehicle_id: '',
  assignment_notes: '',
  status: 'active',
  effective_from: '',
  effective_until: ''
})

const extendForm = ref({
  new_end_date: '',
  notes: ''
})

// Computed properties
const filteredAssignments = computed(() => {
  return assignments.value.filter(assignment => {
    const matchesSearch = !searchQuery.value || 
      assignment.driver.full_name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      assignment.vehicle.registration_number.toLowerCase().includes(searchQuery.value.toLowerCase())
    
    const matchesStatus = statusFilter.value === 'all' || assignment.status === statusFilter.value
    const matchesActive = activeFilter.value === 'all' || 
      (activeFilter.value === 'true' && assignment.is_active) ||
      (activeFilter.value === 'false' && !assignment.is_active)
    
    let matchesDateRange = true
    if (dateFromFilter.value && dateToFilter.value) {
      const assignedDate = new Date(assignment.assigned_at).toDateString()
      const fromDate = new Date(dateFromFilter.value).toDateString()
      const toDate = new Date(dateToFilter.value).toDateString()
      matchesDateRange = assignedDate >= fromDate && assignedDate <= toDate
    }
    
    return matchesSearch && matchesStatus && matchesActive && matchesDateRange
  })
})

const hasActiveFilters = computed(() => {
  return searchQuery.value || statusFilter.value !== 'all' || 
         activeFilter.value !== 'all' || dateFromFilter.value || dateToFilter.value
})

// Methods
const loadAssignments = async () => {
  loading.value = true
  try {
    const response = await fetch('/api/driver-vehicle-assignments', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Content-Type': 'application/json'
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      assignments.value = data.data || data
    } else {
      console.error('Failed to load assignments')      
    }
  } catch (error) {
    console.error('Error loading assignments:', error)
  }
  loading.value = false
}

const loadDashboardStats = async () => {
  try {
    const response = await fetch('/api/driver-vehicle-assignments/dashboard', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Content-Type': 'application/json'
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      stats.value = data.data || {}
    }
  } catch (error) {
    console.error('Error loading dashboard stats:', error)
  }
}

const loadAvailableDrivers = async () => {
  try {
    const response = await fetch('/api/drivers/available', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Content-Type': 'application/json'
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      availableDrivers.value = data.data || []
    }
  } catch (error) {
    console.error('Error loading available drivers:', error)
  }
}

const loadAvailableVehicles = async () => {
  try {
    const response = await fetch('/api/vehicles/available', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Content-Type': 'application/json'
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      availableVehicles.value = data.data || []
    }
  } catch (error) {
    console.error('Error loading available vehicles:', error)
  }
}

const getVehicleTypeBadgeColor = (type) => {
  const colorMap = {
    'ambulance': 'badge-danger',
    'regular': 'badge-primary'
}
  return colorMap[type] || 'badge-secondary'
}

const getAssignmentStatusBadgeColor = (assignment) => {
  if (!assignment.is_active) return 'badge-secondary'
  if (assignment.status === 'temporary') return 'badge-warning'
  return 'badge-success'
}

const capitalizeFirst = (str) => {
  return str ? str.charAt(0).toUpperCase() + str.slice(1).replace('_', ' ') : ''
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString()
}

const formatDateTime = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleString()
}

const toggleDropdown = (assignmentId) => {
  activeDropdown.value = activeDropdown.value === assignmentId ? null : assignmentId
}

const openCreateModal = async () => {
  isEditing.value = false
  currentAssignment.value = null
  resetForm()
  await loadAvailableDrivers()
  await loadAvailableVehicles()
  showAssignmentModal.value = true
}

const openEditModal = (assignment) => {
  isEditing.value = true
  currentAssignment.value = assignment
  populateForm(assignment)
  showAssignmentModal.value = true
  activeDropdown.value = null
}

const openViewModal = (assignment) => {
  currentAssignment.value = assignment
  showViewModal.value = true
  activeDropdown.value = null
}

const openExtendModal = (assignment) => {
  currentAssignment.value = assignment
  extendForm.value = {
    new_end_date: '',
    notes: ''
  }
  showExtendModal.value = true
  activeDropdown.value = null
}

const closeAssignmentModal = () => {
  showAssignmentModal.value = false
}

const closeViewModal = () => {
  showViewModal.value = false
}

const closeExtendModal = () => {
  showExtendModal.value = false
}

const editFromView = () => {
  closeViewModal()
  openEditModal(currentAssignment.value)
}

const resetForm = () => {
  assignmentForm.value = {
    driver_id: '',
    vehicle_id: '',
    assignment_notes: '',
    status: 'active',
    effective_from: '',
    effective_until: ''
  }
}

const populateForm = (assignment) => {
  assignmentForm.value = {
    driver_id: assignment.driver_id,
    vehicle_id: assignment.vehicle_id,
    assignment_notes: assignment.assignment_notes || '',
    status: assignment.status,
    effective_from: assignment.effective_from ? assignment.effective_from.slice(0, 16) : '',
    effective_until: assignment.effective_until ? assignment.effective_until.slice(0, 16) : ''
  }
}

const saveAssignment = async () => {
  isSaving.value = true
  
  try {
    const url = isEditing.value ? `/api/driver-vehicle-assignments/${currentAssignment.value.id}` : '/api/driver-vehicle-assignments'
    const method = isEditing.value ? 'PUT' : 'POST'
    
    const response = await fetch(url, {
      method,
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(assignmentForm.value)
    })
    
    if (response.ok) {
      await loadAssignments()
      await loadDashboardStats()
      closeAssignmentModal()
      toast.showSuccess(isEditing.value ? 'Assignment updated successfully!' : 'Assignment created successfully!')
    } else {
      const errorData = await response.json()
      console.error('Failed to save assignment:', errorData)
      
      if (errorData.errors) {
        const firstError = Object.values(errorData.errors)[0][0]
        toast.showError(firstError)
      } else {
        toast.showError(errorData.message || 'Failed to save assignment. Please try again.')
      }
    }
  } catch (error) {
    console.error('Error saving assignment:', error)
    toast.showError('An error occurred while saving the assignment.')
  }
  
  isSaving.value = false
}

const activateAssignment = async (assignment) => {
  if (confirm(`Are you sure you want to activate this assignment?`)) {
    try {
      const response = await fetch(`/api/driver-vehicle-assignments/${assignment.id}/activate`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
          'Content-Type': 'application/json'
        }
      })
      
      if (response.ok) {
        await loadAssignments()
        await loadDashboardStats()
        toast.showSuccess('Assignment activated successfully!')
      } else {
        const errorData = await response.json()
        toast.showError(errorData.message || 'Failed to activate assignment.')
      }
    } catch (error) {
      console.error('Error activating assignment:', error)
      toast.showError('An error occurred while activating the assignment.')
    }
  }
  activeDropdown.value = null
}

const deactivateAssignment = async (assignment) => {
  const reason = prompt('Please provide a reason for deactivating this assignment (optional):')
  if (reason !== null) {
    try {
      const response = await fetch(`/api/driver-vehicle-assignments/${assignment.id}/deactivate`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ reason })
      })
      
      if (response.ok) {
        await loadAssignments()
        await loadDashboardStats()
        toast.showSuccess('Assignment deactivated successfully!')
      } else {
        const errorData = await response.json()
        toast.showError(errorData.message || 'Failed to deactivate assignment.')
      }
    } catch (error) {
      console.error('Error deactivating assignment:', error)
      toast.showError('An error occurred while deactivating the assignment.')
    }
  }
  activeDropdown.value = null
}

const extendAssignment = async () => {
  isSaving.value = true
  
  try {
    const response = await fetch(`/api/driver-vehicle-assignments/${currentAssignment.value.id}/extend`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(extendForm.value)
    })
    
    if (response.ok) {
      await loadAssignments()
      closeExtendModal()
      toast.showSuccess('Assignment extended successfully!')
    } else {
      const errorData = await response.json()
      toast.showError(errorData.message || 'Failed to extend assignment.')
    }
  } catch (error) {
    console.error('Error extending assignment:', error)
    toast.showError('An error occurred while extending the assignment.')
  }
  
  isSaving.value = false
}

const makePermanent = async (assignment) => {
  if (confirm(`Are you sure you want to make this assignment permanent?`)) {
    try {
      const response = await fetch(`/api/driver-vehicle-assignments/${assignment.id}/make-permanent`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
          'Content-Type': 'application/json'
        }
      })
      
      if (response.ok) {
        await loadAssignments()
        toast.showSuccess('Assignment made permanent successfully!')
      } else {
        const errorData = await response.json()
        toast.showError(errorData.message || 'Failed to make assignment permanent.')
      }
    } catch (error) {
      console.error('Error making assignment permanent:', error)
      toast.showError('An error occurred while making the assignment permanent.')
    }
  }
  activeDropdown.value = null
}

const exportAssignments = async () => {
  try {
    const params = new URLSearchParams()
    
    if (statusFilter.value !== 'all') {
      params.append('status', statusFilter.value)
    }
    
    if (activeFilter.value !== 'all') {
      params.append('active', activeFilter.value)
    }
    
    if (dateFromFilter.value) {
      params.append('date_from', dateFromFilter.value)
    }
    
    if (dateToFilter.value) {
      params.append('date_to', dateToFilter.value)
    }
    
    const queryString = params.toString()
    const url = `/api/driver-vehicle-assignments/export${queryString ? '?' + queryString : ''}`
    
    const response = await fetch(url, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
      }
    })
    
    if (response.ok) {
      const contentDisposition = response.headers.get('Content-Disposition')
      let filename = 'assignments_export.csv'
      if (contentDisposition) {
        const filenameMatch = contentDisposition.match(/filename=(.+)/)
        if (filenameMatch) {
          filename = filenameMatch[1]
        }
      }
      
      const blob = await response.blob()
      const downloadUrl = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = downloadUrl
      link.download = filename
      document.body.appendChild(link)
      link.click()
      link.remove()
      window.URL.revokeObjectURL(downloadUrl)
      
      toast.showSuccess('Assignments exported successfully!')
    } else {
      const errorData = await response.json()
      console.error('Failed to export assignments:', errorData)
      toast.showError(errorData.message || 'Failed to export assignments. Please try again.')
    }
  } catch (error) {
    console.error('Error exporting assignments:', error)
    toast.showError('An error occurred while exporting assignments.')
  }
}

// Close dropdown when clicking outside
const handleClickOutside = (event) => {
  if (!event.target.closest('.action-dropdown')) {
    activeDropdown.value = null
  }
}

// Lifecycle
onMounted(() => {
  loadAssignments()
  loadDashboardStats()
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
/* Assignment specific styles */
.assignments-page {
  min-height: 100vh;
  background: #f8f9fa;
}

/* Stats Section */
.stats-section {
  margin-bottom: 2rem;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
}

.stat-card {
  background: white;
  border-radius: 0.75rem;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  border: 1px solid #e5e7eb;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.stat-icon {
  width: 3rem;
  height: 3rem;
  border-radius: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.stat-icon svg {
  width: 1.5rem;
  height: 1.5rem;
}

.stat-icon-primary {
  background: #dbeafe;
  color: #3b82f6;
}

.stat-icon-warning {
  background: #fef3c7;
  color: #d97706;
}

.stat-icon-info {
  background: #e0f2fe;
  color: #0369a1;
}

.stat-icon-secondary {
  background: #f3f4f6;
  color: #6b7280;
}

.stat-content {
  flex: 1;
}

.stat-number {
  font-size: 2rem;
  font-weight: 700;
  color: #1f2937;
  line-height: 1;
}

.stat-label {
  color: #6b7280;
  font-size: 0.875rem;
  margin-top: 0.25rem;
}

/* Table Styles */
.assignments-table-container {
  background: white;
  border-radius: 0.75rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  border: 1px solid #e5e7eb;
  overflow: visible;
}

.assignments-table {
  width: 100%;
  border-collapse: collapse;
}

.assignments-table thead {
  background: #f9fafb;
}

.assignments-table th {
  padding: 0.75rem 1rem;
  text-align: left;
  font-size: 0.75rem;
  font-weight: 500;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  border-bottom: 1px solid #e5e7eb;
}

.assignments-table tbody tr:hover {
  background: #f9fafb;
}

.assignments-table td {
  padding: 1rem;
  white-space: nowrap;
  font-size: 0.875rem;
  border-bottom: 1px solid #e5e7eb;
}

/* Assignment Info */
.assignment-info .assignment-id {
  font-weight: 600;
  color: #1f2937;
}

.assignment-info .assignment-date {
  color: #6b7280;
  font-size: 0.8125rem;
}

/* Driver and Vehicle Info */
.driver-info .driver-name,
.vehicle-info .vehicle-number {
  font-weight: 500;
  color: #1f2937;
}

.driver-info .driver-phone {
  color: #6b7280;
  font-size: 0.8125rem;
}

/* Duration Info */
.duration-info .duration-text {
  font-weight: 500;
  color: #1f2937;
}

.active-indicator {
  margin-top: 0.25rem;
}

/* Assignment View */
.assignment-view {
  padding: 0;
}

.assignment-details-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
}

.assignment-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 0.75rem;
  padding: 1.5rem;
}

.assignment-card-full {
  grid-column: 1 / -1;
}

.card-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1f2937;
  margin: 0 0 1rem 0;
}

/* Driver Profile in Assignment View */
.driver-profile {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.driver-avatar {
  flex-shrink: 0;
  width: 3rem;
  height: 3rem;
}

.driver-avatar img {
  width: 3rem;
  height: 3rem;
  border-radius: 50%;
  object-fit: cover;
}

.driver-details h5 {
  font-weight: 600;
  color: #1f2937;
  margin: 0 0 0.25rem 0;
}

.driver-details p {
  color: #6b7280;
  margin: 0.125rem 0;
  font-size: 0.875rem;
}

.driver-stats {
  display: flex;
  gap: 1rem;
  margin-top: 0.5rem;
}

.driver-stats .stat {
  color: #6b7280;
  font-size: 0.8125rem;
}

/* Vehicle Profile */
.vehicle-profile .vehicle-info h5 {
  font-weight: 600;
  color: #1f2937;
  margin: 0 0 0.25rem 0;
}

.vehicle-profile .vehicle-info p {
  color: #6b7280;
  margin: 0.125rem 0;
  font-size: 0.875rem;
}

/* Assignment Info Grid */
.assignment-info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 1rem;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.info-item label {
  font-weight: 500;
  color: #6b7280;
  font-size: 0.875rem;
}

.info-item span {
  color: #1f2937;
}

/* Assignment Notes */
.assignment-notes,
.unassignment-reason {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #e2e8f0;
}

.assignment-notes label,
.unassignment-reason label {
  font-weight: 500;
  color: #6b7280;
  font-size: 0.875rem;
  display: block;
  margin-bottom: 0.5rem;
}

.assignment-notes p,
.unassignment-reason p {
  color: #1f2937;
  margin: 0;
  line-height: 1.5;
}

/* Responsive Design */
@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .assignment-details-grid {
    grid-template-columns: 1fr;
  }
  
  .driver-profile {
    flex-direction: column;
    text-align: center;
  }
  
  .assignment-info-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 640px) {
  .assignments-table-container {
    overflow-x: auto;
  }
  
  .assignments-table {
    min-width: 1000px;
  }
}
</style>