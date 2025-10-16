<template>
  <MainLayout>
    <div class="drivers-page">
      <!-- Page Header -->
      <div class="page-header">
        <div class="page-header-content">
          <h1>Drivers Management</h1>
          <p>Manage transportation drivers and their assignments</p>
        </div>
        <div class="page-header-actions">
          <button @click="exportDrivers" class="btn-modern btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Export
          </button>
          <button @click="openCreateModal" class="btn-modern btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Add Driver
          </button>
        </div>
      </div>

      <!-- Stats Grid -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon blue">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Total Drivers</div>
            <div class="stat-value">{{ statistics.total_drivers || 0 }}</div>
            <div class="stat-change positive">All registered</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon green">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Active Drivers</div>
            <div class="stat-value">{{ statistics.active_drivers || 0 }}</div>
            <div class="stat-change positive">Currently active</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon purple">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/>
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">With Vehicles</div>
            <div class="stat-value">{{ statistics.drivers_with_vehicles || 0 }}</div>
            <div class="stat-change neutral">{{ statistics.drivers_without_vehicles || 0 }} available</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon yellow">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Avg Rating</div>
            <div class="stat-value">{{ formatRating(statistics.performance_metrics?.average_rating) }}</div>
            <div class="stat-change positive">{{ statistics.performance_metrics?.total_trips || 0 }} trips</div>
          </div>
        </div>
      </div>

      <!-- Filters Section -->
      <div class="filters-section">
        <div class="search-wrapper">
          <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <input
            type="text"
            placeholder="Search by name, phone, email..."
            v-model="searchQuery"
            class="search-input"
          />
        </div>
        <div class="filters-group">
          <select v-model="activeFilter" class="filter-select">
            <option value="all">All Status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
          
          <select v-model="suspendedFilter" class="filter-select">
            <option value="all">All Drivers</option>
            <option value="false">Not Suspended</option>
            <option value="true">Suspended</option>
          </select>
          
          <select v-model="vehicleAssignedFilter" class="filter-select">
            <option value="all">All Assignments</option>
            <option value="true">With Vehicle</option>
            <option value="false">Without Vehicle</option>
          </select>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="loading-state">
        <div class="loading-spinner"></div>
        <p class="loading-text">Loading drivers...</p>
      </div>

      <!-- Drivers Table -->
      <div v-else-if="!loading" class="drivers-table-container">
        <div v-if="drivers.data && drivers.data.length > 0" class="table-wrapper">
          <table class="modern-table">
            <thead>
              <tr>
                <th>Driver</th>
                <th>Contact</th>
                <th>Age</th>
                <th>Status</th>
                <th>Assigned Vehicle</th>
                <th>Performance</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="driver in drivers.data" :key="driver.id">
                <td>
                  <div class="user-cell">
                    <img :src="driver.avatar_url || generateAvatar(driver)" class="user-avatar-table" />
                    <div class="user-details-table">
                      <div class="user-name-table">{{ driver.first_name }} {{ driver.last_name }}</div>
                      <div class="user-id-table">ID: {{ driver.driver_id }}</div>
                    </div>
                  </div>
                </td>
                
                <td>
                  <div class="contact-cell">
                    <div class="contact-primary">{{ driver.phone }}</div>
                    <div class="contact-secondary">{{ driver.email || 'N/A' }}</div>
                  </div>
                </td>
                
                <td>
                  <span class="age-info">{{ driver.age }} years</span>
                </td>
                
                <td>
                  <span class="modern-badge" :class="getStatusBadgeColor(driver)">
                    {{ getStatusLabel(driver) }}
                  </span>
                </td>
                
                <td>
                  <div v-if="driver.current_vehicle" class="vehicle-assignment">
                    <div class="contact-cell">
                      <div class="contact-primary">{{ driver.current_vehicle.registration_number }}</div>
                      <div class="contact-secondary">{{ capitalizeFirst(driver.current_vehicle.vehicle_type) }}</div>
                    </div>
                  </div>
                  <div v-else class="text-secondary">Not assigned</div>
                </td>
                
                <td>
                  <div class="performance-cell">
                    <div class="rating-display">
                      <span class="rating-score">{{ driver.average_rating || 'N/A' }}</span>
                      <div v-if="driver.average_rating" class="rating-stars">
                        <span v-for="star in 5" :key="star" :class="star <= Math.floor(driver.average_rating) ? 'star filled' : 'star'">★</span>
                      </div>
                    </div>
                    <div class="trips-count">{{ driver.total_trips || 0 }} trips</div>
                  </div>
                </td>
                
                <td>
                  <div class="action-cell">
                    <button @click="toggleDropdown(driver.id)" class="action-btn">
                      <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                      </svg>
                    </button>
                    
                    <div v-show="activeDropdown === driver.id" class="modern-dropdown">
                      <button @click="openViewModal(driver)" class="dropdown-item-modern">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        View Details
                      </button>
                      
                      <button @click="openEditModal(driver)" class="dropdown-item-modern">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Edit Driver
                      </button>
                      
                      <div class="dropdown-divider"></div>
                      
                      <button
                        v-if="!driver.current_vehicle"
                        @click="openAssignVehicleModal(driver)"
                        class="dropdown-item-modern success"
                      >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Assign Vehicle
                      </button>
                      
                      <button
                        v-if="driver.current_vehicle"
                        @click="openUnassignVehicleModal(driver)"
                        class="dropdown-item-modern warning"
                      >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Unassign Vehicle
                      </button>
                      
                      <div class="dropdown-divider"></div>
                      
                      <button
                        v-if="!driver.is_suspended"
                        @click="openSuspendModal(driver)"
                        class="dropdown-item-modern danger"
                      >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728" />
                        </svg>
                        Suspend Driver
                      </button>
                      
                      <button
                        v-if="driver.is_suspended"
                        @click="openReactivateModal(driver)"
                        class="dropdown-item-modern success"
                      >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Reactivate Driver
                      </button>
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>

          <!-- Pagination -->
          <div v-if="drivers.last_page > 1" class="pagination-container">
            <div class="pagination-info">
              Showing {{ (drivers.current_page - 1) * drivers.per_page + 1 }} to {{ Math.min(drivers.current_page * drivers.per_page, drivers.total) }} of {{ drivers.total }} drivers
            </div>
            <div class="pagination-controls">
              <button 
                @click="prevPage" 
                :disabled="drivers.current_page === 1"
                class="pagination-btn"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Previous
              </button>
              
              <div class="pagination-pages">
                <button
                  v-for="page in getPaginationPages()"
                  :key="page"
                  @click="goToPage(page)"
                  :class="['pagination-page', { active: page === drivers.current_page }]"
                >
                  {{ page }}
                </button>
              </div>
              
              <button 
                @click="nextPage" 
                :disabled="drivers.current_page === drivers.last_page"
                class="pagination-btn"
              >
                Next
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        <div v-else class="empty-state">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
          </svg>
          <h3>No drivers found</h3>
          <p>
            {{ hasActiveFilters ? 'Try adjusting your search or filters.' : 'Start by adding a new driver.' }}
          </p>
          <button @click="openCreateModal" class="btn btn-primary">
            Add First Driver
          </button>
        </div>
      </div>

      <!-- Create/Edit Driver Modal -->
      <div v-if="showDriverModal" class="modal-overlay" @click.self="closeDriverModal">
        <div class="modal modal-lg">
          <div class="modal-header">
            <h2 class="modal-title">
              {{ isEditing ? 'Edit Driver' : 'Add New Driver' }}
            </h2>
            <button @click="closeDriverModal" class="modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <form @submit.prevent="saveDriver">
            <div class="modal-body">
              <div class="form-grid">
                <div class="form-group">
                  <label>First Name <span class="required">*</span></label>
                  <input type="text" v-model="driverForm.first_name" required />
                </div>
                
                <div class="form-group">
                  <label>Last Name <span class="required">*</span></label>
                  <input type="text" v-model="driverForm.last_name" required />
                </div>
                
                <div class="form-group">
                  <label>Phone Number <span class="required">*</span></label>
                  <input type="tel" v-model="driverForm.phone" required />
                </div>
                
                <div class="form-group">
                  <label>Email</label>
                  <input type="email" v-model="driverForm.email" />
                </div>
                
                <div class="form-group">
                  <label>Date of Birth <span class="required">*</span></label>
                  <input type="date" v-model="driverForm.date_of_birth" required />
                </div>
                
                <div class="form-group">
                  <label>Photo</label>
                  <div v-if="isEditing && currentDriver?.avatar_url && !newImagePreview" class="current-image-preview">
                    <img :src="currentDriver.avatar_url" alt="Current photo" class="current-avatar" />
                    <p class="image-help-text">Current photo (upload a new one to replace)</p>
                  </div>
                  
                  <div v-if="newImagePreview" class="new-image-preview">
                    <img :src="newImagePreview" alt="New photo preview" class="preview-avatar" />
                    <p class="image-help-text">New photo preview</p>
                    <button type="button" @click="clearImagePreview" class="btn btn-secondary btn-sm">
                      Remove
                    </button>
                  </div>
                  
                  <input
                    type="file"
                    ref="avatarInput"
                    accept="image/*"
                    @change="handleAvatarChange"
                    class="form-control"
                  />
                  <p class="form-help">Upload a clear photo of the driver</p>
                </div>
                
                <div class="form-group form-grid-full">
                  <label>Notes</label>
                  <textarea
                    v-model="driverForm.notes"
                    rows="3"
                    placeholder="Any additional information about the driver..."
                  ></textarea>
                </div>
              </div>
            </div>

            <div class="modal-actions">
              <button type="button" @click="closeDriverModal" class="btn btn-secondary">
                Cancel
              </button>
              <button type="submit" :disabled="isSaving" class="btn btn-primary">
                <div v-if="isSaving" class="spinner spinner-sm"></div>
                {{ isEditing ? 'Update Driver' : 'Add Driver' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- View Driver Modal -->
      <div v-if="showViewModal && currentDriver" class="modal-overlay" @click.self="closeViewModal">
        <div class="modal modal-xl">
          <div class="modal-header">
            <h2 class="modal-title">Driver Details</h2>
            <button @click="closeViewModal" class="modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="modal-body">
            <div class="user-view-grid">
              <div class="user-profile-section">
                <img
                  :src="currentDriver.avatar_url || generateAvatar(currentDriver)"
                  class="profile-avatar-large"
                />
                <h3 class="profile-name-view">
                  {{ currentDriver.first_name }} {{ currentDriver.last_name }}
                </h3>
                <span class="modern-badge badge-info">Driver ID: {{ currentDriver.driver_id }}</span>
                
                <div class="profile-contact-view">
                  <div class="contact-item-view">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                    <span>{{ currentDriver.phone }}</span>
                  </div>
                  
                  <div class="contact-item-view">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <span>{{ currentDriver.email || 'N/A' }}</span>
                  </div>
                </div>
                
                <div class="status-badges-view">
                  <span class="modern-badge" :class="getStatusBadgeColor(currentDriver)">
                    {{ getStatusLabel(currentDriver) }}
                  </span>
                  <span v-if="currentDriver.is_suspended" class="modern-badge badge-danger">
                    Suspended
                  </span>
                </div>
              </div>

              <div class="details-section-view">
                <div class="details-group">
                  <h4 class="details-header">Personal Information</h4>
                  <div class="details-grid-view">
                    <div class="detail-item-view">
                      <label>Date of Birth</label>
                      <p>{{ formatDate(currentDriver.date_of_birth) }}</p>
                    </div>
                    <div class="detail-item-view">
                      <label>Age</label>
                      <p>{{ currentDriver.age }} years</p>
                    </div>
                  </div>
                </div>

                <div class="details-group">
                  <h4 class="details-header">Vehicle Assignment</h4>
                  <div v-if="currentDriver.current_vehicle" class="vehicle-assignment-info">
                    <div class="assigned-vehicle-card">
                      <div class="vehicle-details">
                        <h5>{{ currentDriver.current_vehicle.registration_number }}</h5>
                        <p>{{ currentDriver.current_vehicle.vehicle_type }} - {{ currentDriver.current_vehicle.vehicle_color }}</p>
                      </div>
                      <span class="modern-badge badge-info">
                        {{ capitalizeFirst(currentDriver.current_vehicle.vehicle_type) }}
                      </span>
                    </div>
                  </div>
                  <div v-else class="no-vehicle-assigned">
                    <p class="text-secondary">No vehicle currently assigned</p>
                  </div>
                </div>

                <div class="details-group">
                  <h4 class="details-header">Performance Metrics</h4>
                  <div class="performance-grid">
                    <div class="metric-card">
                      <div class="metric-value">{{ currentDriver.average_rating || 'N/A' }}</div>
                      <div class="metric-label">Average Rating</div>
                    </div>
                    <div class="metric-card">
                      <div class="metric-value">{{ currentDriver.total_trips || 0 }}</div>
                      <div class="metric-label">Total Trips</div>
                    </div>
                    <div class="metric-card">
                      <div class="metric-value">{{ currentDriver.completed_trips || 0 }}</div>
                      <div class="metric-label">Completed</div>
                    </div>
                    <div class="metric-card">
                      <div class="metric-value">{{ currentDriver.completion_rate || 0 }}%</div>
                      <div class="metric-label">Success Rate</div>
                    </div>
                  </div>
                </div>

                <div v-if="currentDriver.notes" class="details-group">
                  <h4 class="details-header">Additional Notes</h4>
                  <p class="notes-text">{{ currentDriver.notes }}</p>
                </div>
              </div>
            </div>
          </div>

          <div class="modal-actions">
            <button @click="closeViewModal" class="btn btn-secondary">Close</button>
            <button @click="editFromView" class="btn btn-primary">Edit Driver</button>
          </div>
        </div>
      </div>

      <!-- Assign Vehicle Modal -->
      <div v-if="showAssignVehicleModal" class="modal-overlay" @click.self="closeAssignVehicleModal">
        <div class="modal modal-lg">
          <div class="modal-header">
            <h3 class="modal-title">Assign Vehicle to Driver</h3>
            <button @click="closeAssignVehicleModal" class="modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <form @submit.prevent="assignVehicle">
            <div class="modal-body">
              <div class="driver-assignment-info">
                <div class="assignment-header">
                  <h4>Assigning vehicle to:</h4>
                  <div class="driver-card">
                    <div class="driver-info">
                      <span class="driver-name">{{ currentDriver?.full_name }}</span>
                      <span class="driver-details">{{ currentDriver?.phone }}</span>
                      <span class="modern-badge badge-info">Driver ID: {{ currentDriver?.driver_id }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <div class="form-grid">
                <div class="form-group form-grid-full">
                  <label>Select Vehicle <span class="required">*</span></label>
                  <SearchableSelect
                    v-model="assignmentForm.vehicle_id"
                    :options="vehicleOptions"
                    placeholder="Search for available vehicles..."
                    required
                    :loading="loadingVehicles"
                  />
                  <p class="form-help">
                    {{ vehicleOptions.length }} available vehicles found
                  </p>
                </div>

                <div class="form-group form-grid-full">
                  <label>Assignment Notes</label>
                  <textarea
                    v-model="assignmentForm.notes"
                    rows="3"
                    placeholder="Optional notes about this assignment..."
                  ></textarea>
                </div>
              </div>

              <div v-if="selectedVehiclePreview" class="selected-vehicle-preview">
                <h5>Selected Vehicle:</h5>
                <div class="vehicle-preview-card">
                  <div class="vehicle-info">
                    <div class="vehicle-reg">{{ selectedVehiclePreview.registration_number }}</div>
                    <div class="vehicle-details">
                      <span>{{ selectedVehiclePreview.make }} {{ selectedVehiclePreview.model }}</span>
                      <span v-if="selectedVehiclePreview.year"> ({{ selectedVehiclePreview.year }})</span>
                    </div>
                    <div class="vehicle-details">
                      <span>Color: {{ selectedVehiclePreview.vehicle_color }}</span>
                    </div>
                  </div>
                  <div class="vehicle-stats">
                    <div class="stat-item">
                      <span class="stat-value">{{ selectedVehiclePreview.mileage || 'N/A' }}</span>
                      <span class="stat-label">Mileage</span>
                    </div>
                    <span class="modern-badge badge-info">
                      {{ capitalizeFirst(selectedVehiclePreview.vehicle_type) }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <div class="modal-actions">
              <button type="button" @click="closeAssignVehicleModal" class="btn btn-secondary">
                Cancel
              </button>
              <button
                type="submit"
                :disabled="isSaving || !assignmentForm.vehicle_id"
                class="btn btn-primary"
              >
                <div v-if="isSaving" class="spinner spinner-sm"></div>
                Assign Vehicle
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Unassign Vehicle Modal -->
      <div v-if="showUnassignVehicleModal && currentDriver" class="modal-overlay" @click.self="closeUnassignVehicleModal">
        <div class="modal modal-lg">
          <div class="modal-header modal-header-danger">
            <h3 class="modal-title">Unassign Vehicle from Driver</h3>
            <button @click="closeUnassignVehicleModal" class="modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          
          <form @submit.prevent="unassignVehicle">
            <div class="modal-body">
              <div class="unassign-confirmation-content">
                <p class="confirmation-message">
                  Are you sure you want to unassign the vehicle from this driver? This action will remove the current vehicle assignment.
                </p>

                <div class="assignment-details">
                  <div class="detail-section">
                    <h5>Driver</h5>
                    <div class="driver-summary">
                      <div class="driver-name">{{ currentDriver.full_name }}</div>
                      <div class="driver-contact">
                        <span>{{ currentDriver.phone }}</span>
                        <span v-if="currentDriver.email">{{ currentDriver.email }}</span>
                      </div>
                      <div class="driver-id">ID: {{ currentDriver.driver_id }}</div>
                    </div>
                  </div>

                  <div v-if="currentDriver.current_vehicle" class="detail-section">
                    <h5>Current Vehicle</h5>
                    <div class="vehicle-summary">
                      <div class="vehicle-reg">{{ currentDriver.current_vehicle.registration_number }}</div>
                      <div class="vehicle-info">{{ currentDriver.current_vehicle.make }} {{ currentDriver.current_vehicle.model }} {{ currentDriver.current_vehicle.year }}</div>
                      <span class="modern-badge badge-info">
                        {{ capitalizeFirst(currentDriver.current_vehicle.vehicle_type) }}
                      </span>
                    </div>
                  </div>
                </div>

                <div class="warning-note">
                  <svg class="warning-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                  </svg>
                  <div>
                    <p><strong>Note:</strong> After unassigning, this vehicle will become available for assignment to other drivers.</p>
                  </div>
                </div>
              </div>
            </div>

            <div class="modal-actions">
              <button type="button" @click="closeUnassignVehicleModal" class="btn btn-secondary">
                Cancel
              </button>
              <button
                type="submit"
                :disabled="isUnassigning"
                class="btn btn-danger"
              >
                <div v-if="isUnassigning" class="spinner spinner-sm"></div>
                Unassign Vehicle
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Suspend Driver Modal -->
      <div v-if="showSuspendModal && currentDriver" class="modal-overlay" @click.self="closeSuspendModal">
        <div class="modal modal-sm">
          <div class="modal-header modal-header-danger">
            <h3 class="modal-title">Suspend Driver</h3>
            <button @click="closeSuspendModal" class="modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          
          <form @submit.prevent="suspendDriver">
            <div class="modal-body">
              <p>Are you sure you want to suspend <strong>{{ currentDriver.full_name }}</strong>?</p>
              <div class="form-group">
                <label>Reason for Suspension <span class="required">*</span></label>
                <textarea
                  v-model="suspensionReason"
                  rows="3"
                  placeholder="Please provide a reason for suspension..."
                  required
                ></textarea>
              </div>
            </div>

            <div class="modal-actions">
              <button type="button" @click="closeSuspendModal" class="btn btn-secondary">
                Cancel
              </button>
              <button type="submit" :disabled="isSaving" class="btn btn-danger">
                <div v-if="isSaving" class="spinner spinner-sm"></div>
                Suspend Driver
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Reactivate Driver Modal -->
      <div v-if="showReactivateModal && currentDriver" class="modal-overlay" @click.self="closeReactivateModal">
        <div class="modal modal-sm">
          <div class="modal-header">
            <h3 class="modal-title">Reactivate Driver</h3>
            <button @click="closeReactivateModal" class="modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          
          <div class="modal-body">
            <p>Are you sure you want to reactivate <strong>{{ currentDriver.full_name }}</strong>?</p>
            <p class="text-sm text-gray-600 mt-2">
              This driver will regain access to the system and be able to accept transport assignments again.
            </p>
          </div>

          <div class="modal-actions">
            <button @click="closeReactivateModal" class="btn btn-secondary">
              Cancel
            </button>
            <button
              @click="reactivateDriver"
              :disabled="isSaving"
              class="btn btn-primary"
            >
              <div v-if="isSaving" class="spinner spinner-sm"></div>
              Reactivate Driver
            </button>
          </div>
        </div>
      </div>

      <Toast />
    </div>
  </MainLayout>
</template>

<script setup>
import { formatRating, formatPercentage } from '../../utils/format';
import { ref, computed, onMounted, onUnmounted, inject, watch } from 'vue'
import MainLayout from '../../layout/MainLayout.vue'
import Toast from '../../common/components/Toast.vue'
import SearchableSelect from '../../common/components/SearchableSelect.vue'
import * as driverService from '../../services/driverService'

const toast = inject('toast')

// Reactive data
const drivers = ref({ data: [], total: 0, per_page: 15, current_page: 1, last_page: 1 })
const availableVehicles = ref([])
const statistics = ref({})
const loading = ref(true)
const loadingVehicles = ref(false)
const searchQuery = ref('')
const activeFilter = ref('all')
const suspendedFilter = ref('all')
const vehicleAssignedFilter = ref('all')

// Modal states
const showDriverModal = ref(false)
const showViewModal = ref(false)
const showAssignVehicleModal = ref(false)
const showUnassignVehicleModal = ref(false)
const showSuspendModal = ref(false)
const showReactivateModal = ref(false)
const isEditing = ref(false)
const currentDriver = ref(null)
const isSaving = ref(false)
const isUnassigning = ref(false)
const suspensionReason = ref('')

// Image preview states
const newImagePreview = ref(null)

// Dropdown state
const activeDropdown = ref(null)

// Form data
const driverForm = ref({
  first_name: '',
  last_name: '',
  phone: '',
  email: '',
  date_of_birth: '',
  notes: '',
  avatar: null
})

const assignmentForm = ref({
  vehicle_id: '',
  notes: ''
})

// Computed properties
const today = computed(() => new Date().toISOString().split('T')[0])

const hasActiveFilters = computed(() => {
  return searchQuery.value || activeFilter.value !== 'all' ||
    suspendedFilter.value !== 'all' || vehicleAssignedFilter.value !== 'all'
})

const vehicleOptions = computed(() => {
  return availableVehicles.value.map(vehicle => ({
    value: vehicle.id,
    label: `${vehicle.registration_number} (${vehicle.vehicle_type})`,
    searchText: `${vehicle.registration_number} ${vehicle.make || ''} ${vehicle.model || ''} ${vehicle.vehicle_type} ${vehicle.vehicle_color || ''}`.toLowerCase()
  }))
})

const selectedVehiclePreview = computed(() => {
  if (!assignmentForm.value.vehicle_id) return null
  return availableVehicles.value.find(vehicle => vehicle.id == assignmentForm.value.vehicle_id)
})

// Methods
const loadDrivers = async (page = 1) => {
  loading.value = true
  try {
    const params = {
      page: page,
      per_page: 15,
      search: searchQuery.value || undefined,
      active: activeFilter.value !== 'all' ? activeFilter.value : undefined,
      suspended: suspendedFilter.value !== 'all' ? suspendedFilter.value : undefined,
      vehicle_assigned: vehicleAssignedFilter.value !== 'all' ? vehicleAssignedFilter.value : undefined
    }
    
    Object.keys(params).forEach(key => params[key] === undefined && delete params[key])
    
    const response = await driverService.getDrivers(params)
    
    console.log('API Response:', response) // Debug log
    
    // Handle different response structures
    if (response.success && response.data) {
      // If data has pagination property
      if (response.pagination) {
        drivers.value = {
          data: response.data || [],
          total: response.pagination.total || 0,
          current_page: response.pagination.current_page || 1,
          last_page: response.pagination.last_page || 1,
          per_page: response.pagination.per_page || 15
        }
      } else if (Array.isArray(response.data)) {
        // If data is directly an array
        drivers.value = {
          data: response.data,
          total: response.data.length,
          current_page: 1,
          last_page: 1,
          per_page: 15
        }
      } else {
        // If it's already paginated in data
        drivers.value = {
          data: response.data || [],
          total: 0,
          current_page: 1,
          last_page: 1,
          per_page: 15
        }
      }
    } else if (response.data && typeof response.data === 'object') {
      // Laravel paginated response structure
      drivers.value = {
        data: response.data.data || response.data || [],
        total: response.data.total || 0,
        current_page: response.data.current_page || 1,
        last_page: response.data.last_page || 1,
        per_page: response.data.per_page || 15
      }
    } else {
      // Fallback: treat response as direct data
      drivers.value = {
        data: Array.isArray(response) ? response : [],
        total: Array.isArray(response) ? response.length : 0,
        current_page: 1,
        last_page: 1,
        per_page: 15
      }
    }
    
    console.log('Processed drivers:', drivers.value) // Debug log
  } catch (error) {
    console.error('Error loading drivers:', error)
    toast.showError('Failed to load drivers')
    drivers.value = { data: [], total: 0, current_page: 1, last_page: 1, per_page: 15 }
  }
  loading.value = false
}

const loadAvailableVehicles = async () => {
  loadingVehicles.value = true
  try {
    const response = await driverService.getAvailableVehicles()
    availableVehicles.value = response.data || []
  } catch (error) {
    console.error('Error loading available vehicles:', error)
    toast.showError('Failed to load available vehicles')
  }
  loadingVehicles.value = false
}

const getStatistics = async () => {
  try {
    const response = await driverService.getDashboardStats()
    console.log('Statistics Response:', response) // Debug log
    
    // Handle different response structures
    if (response.success && response.data) {
      statistics.value = response.data
    } else if (response.data) {
      statistics.value = response.data
    } else {
      statistics.value = response || {}
    }
    
    console.log('Processed statistics:', statistics.value) // Debug log
  } catch (error) {
    console.error('Error loading statistics:', error)
    statistics.value = {}
  }
}

const generateAvatar = (driver) => {
  if (!driver) {
    return 'https://ui-avatars.com/api/?name=N+A&color=667eea&background=f8f9fa&size=200&font-size=0.6'
  }
  const name = `${driver.first_name || ''} ${driver.last_name || ''}`
  return `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&color=667eea&background=f8f9fa&size=200&font-size=0.6`
}

const getStatusBadgeColor = (driver) => {
  if (driver.is_suspended) return 'badge-danger'
  if (!driver.is_active) return 'badge-secondary'
  return 'badge-success'
}

const getStatusLabel = (driver) => {
  if (driver.is_suspended) return 'Suspended'
  if (!driver.is_active) return 'Inactive'
  return 'Active'
}

const capitalizeFirst = (str) => {
  return str ? str.charAt(0).toUpperCase() + str.slice(1).replace('_', ' ') : ''
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatDateForInput = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  if (isNaN(date.getTime())) return ''
  return date.toISOString().split('T')[0]
}

const toggleDropdown = (driverId) => {
  activeDropdown.value = activeDropdown.value === driverId ? null : driverId
}

const openCreateModal = () => {
  isEditing.value = false
  currentDriver.value = null
  resetForm()
  showDriverModal.value = true
}

const openEditModal = (driver) => {
  isEditing.value = true
  currentDriver.value = driver
  populateForm(driver)
  showDriverModal.value = true
  activeDropdown.value = null
}

const openViewModal = (driver) => {
  currentDriver.value = driver
  showViewModal.value = true
  activeDropdown.value = null
}

const openSuspendModal = (driver) => {
  currentDriver.value = driver
  suspensionReason.value = ''
  showSuspendModal.value = true
  activeDropdown.value = null
}

const openReactivateModal = (driver) => {
  currentDriver.value = driver
  showReactivateModal.value = true
  activeDropdown.value = null
}

const openAssignVehicleModal = async (driver) => {
  currentDriver.value = driver
  assignmentForm.value = { vehicle_id: '', notes: '' }
  showAssignVehicleModal.value = true
  await loadAvailableVehicles()
  activeDropdown.value = null
}

const openUnassignVehicleModal = (driver) => {
  currentDriver.value = driver
  showUnassignVehicleModal.value = true
  activeDropdown.value = null
}

const closeDriverModal = () => {
  showDriverModal.value = false
  clearImagePreview()
}

const closeViewModal = () => {
  showViewModal.value = false
}

const closeSuspendModal = () => {
  showSuspendModal.value = false
}

const closeReactivateModal = () => {
  showReactivateModal.value = false
}

const closeAssignVehicleModal = () => {
  showAssignVehicleModal.value = false
}

const closeUnassignVehicleModal = () => {
  showUnassignVehicleModal.value = false
  currentDriver.value = null
}

const editFromView = () => {
  closeViewModal()
  openEditModal(currentDriver.value)
}

const resetForm = () => {
  driverForm.value = {
    first_name: '',
    last_name: '',
    phone: '',
    email: '',
    date_of_birth: '',
    notes: '',
    avatar: null
  }
  clearImagePreview()
}

const populateForm = (driver) => {
  driverForm.value = {
    first_name: driver.first_name,
    last_name: driver.last_name,
    phone: driver.phone,
    email: driver.email || '',
    date_of_birth: formatDateForInput(driver.date_of_birth),
    notes: driver.notes || '',
    avatar: null
  }
  clearImagePreview()
}

const handleAvatarChange = (event) => {
  const file = event.target.files[0]
  if (file) {
    const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp']
    const maxSize = 2 * 1024 * 1024

    if (!allowedTypes.includes(file.type)) {
      toast.showError('Please select a valid image file (JPEG, PNG, JPG, WEBP)')
      event.target.value = ''
      return
    }

    if (file.size > maxSize) {
      toast.showError('Image size must be less than 2MB')
      event.target.value = ''
      return
    }

    driverForm.value.avatar = file

    const reader = new FileReader()
    reader.onload = (e) => {
      newImagePreview.value = e.target.result
    }
    reader.readAsDataURL(file)
  } else {
    clearImagePreview()
  }
}

const clearImagePreview = () => {
  newImagePreview.value = null
  if (driverForm.value) {
    driverForm.value.avatar = null
  }
  const fileInput = document.querySelector('input[type="file"]')
  if (fileInput) {
    fileInput.value = ''
  }
}

const saveDriver = async () => {
  isSaving.value = true
  
  try {
    const formData = new FormData()
    
    const textFields = ['first_name', 'last_name', 'phone', 'email', 'date_of_birth', 'notes']
    textFields.forEach(field => {
      if (driverForm.value[field] !== null && driverForm.value[field] !== '') {
        formData.append(field, driverForm.value[field])
      }
    })
    
    if (driverForm.value.avatar && driverForm.value.avatar instanceof File) {
      formData.append('avatar', driverForm.value.avatar)
    }
    
    if (isEditing.value) {
      formData.append('_method', 'PUT')
      await driverService.updateDriver(currentDriver.value.id, formData)
    } else {
      await driverService.createDriver(formData)
    }
    
    await Promise.all([loadDrivers(drivers.value.current_page), getStatistics()])
    closeDriverModal()
    toast.showSuccess(isEditing.value ? 'Driver updated successfully!' : 'Driver added successfully!')
  } catch (error) {
    console.error('Error saving driver:', error)
    toast.showError(error.message || 'Failed to save driver')
  }
  
  isSaving.value = false
}

const assignVehicle = async () => {
  isSaving.value = true
  
  try {
    await driverService.assignVehicle(currentDriver.value.id, assignmentForm.value)
    await Promise.all([loadDrivers(drivers.value.current_page), getStatistics()])
    closeAssignVehicleModal()
    toast.showSuccess('Vehicle assigned successfully!')
  } catch (error) {
    console.error('Error assigning vehicle:', error)
    toast.showError(error.message || 'Failed to assign vehicle')
  }
  
  isSaving.value = false
}

const unassignVehicle = async () => {
  isUnassigning.value = true
  
  try {
    await driverService.unassignVehicle(currentDriver.value.id)
    await Promise.all([loadDrivers(drivers.value.current_page), getStatistics()])
    closeUnassignVehicleModal()
    toast.showSuccess('Vehicle unassigned successfully!')
  } catch (error) {
    console.error('Error unassigning vehicle:', error)
    toast.showError(error.message || 'Failed to unassign vehicle')
  }
  
  isUnassigning.value = false
}

const suspendDriver = async () => {
  isSaving.value = true
  
  try {
    await driverService.suspendDriver(currentDriver.value.id, { reason: suspensionReason.value })
    await Promise.all([loadDrivers(drivers.value.current_page), getStatistics()])
    closeSuspendModal()
    toast.showSuccess('Driver suspended successfully!')
  } catch (error) {
    console.error('Error suspending driver:', error)
    toast.showError(error.message || 'Failed to suspend driver')
  }
  
  isSaving.value = false
}

const reactivateDriver = async () => {
  isSaving.value = true
  
  try {
    await driverService.reactivateDriver(currentDriver.value.id)
    await Promise.all([loadDrivers(drivers.value.current_page), getStatistics()])
    closeReactivateModal()
    toast.showSuccess('Driver reactivated successfully!')
  } catch (error) {
    console.error('Error reactivating driver:', error)
    toast.showError(error.message || 'Failed to reactivate driver')
  }
  
  isSaving.value = false
}

const exportDrivers = async () => {
  try {
    toast.showInfo('Preparing export...')
    
    const filters = {
      active: activeFilter.value !== 'all' ? activeFilter.value : undefined,
      suspended: suspendedFilter.value !== 'all' ? suspendedFilter.value : undefined,
      search: searchQuery.value || undefined
    }
    
    Object.keys(filters).forEach(key => filters[key] === undefined && delete filters[key])
    
    const { blob, filename } = await driverService.exportDrivers(filters)
    
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = filename
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
    
    toast.showSuccess('Drivers exported successfully!')
  } catch (error) {
    console.error('Error exporting drivers:', error)
    toast.showError('Failed to export drivers')
  }
}

const goToPage = (page) => {
  if (page >= 1 && page <= drivers.value.last_page) {
    loadDrivers(page)
  }
}

const nextPage = () => {
  if (drivers.value.current_page < drivers.value.last_page) {
    goToPage(drivers.value.current_page + 1)
  }
}

const prevPage = () => {
  if (drivers.value.current_page > 1) {
    goToPage(drivers.value.current_page - 1)
  }
}

const getPaginationPages = () => {
  const pages = []
  const maxVisible = 5
  let start = Math.max(1, drivers.value.current_page - Math.floor(maxVisible / 2))
  let end = Math.min(drivers.value.last_page, start + maxVisible - 1)
  
  if (end - start < maxVisible - 1) {
    start = Math.max(1, end - maxVisible + 1)
  }
  
  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  
  return pages
}

const handleClickOutside = (event) => {
  if (!event.target.closest('.action-cell')) {
    activeDropdown.value = null
  }
}

let searchDebounceTimer = null

watch(searchQuery, () => {
  clearTimeout(searchDebounceTimer)
  searchDebounceTimer = setTimeout(() => {
    loadDrivers(1)
  }, 500)
})

watch([activeFilter, suspendedFilter, vehicleAssignedFilter], () => {
  loadDrivers(1)
})

watch(() => assignmentForm.value.vehicle_id, (newVehicleId) => {
  if (newVehicleId) {
    console.log('Selected vehicle:', selectedVehiclePreview.value)
  }
})

onMounted(async () => {
  try {
    await Promise.all([
      loadDrivers(),
      getStatistics()
    ])
    
    document.addEventListener('click', handleClickOutside)
  } catch (error) {
    console.error('Mount error:', error)
  }
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
  clearTimeout(searchDebounceTimer)
})
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

* {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

.drivers-page {
  padding: 32px;
  background: #f8fafc;
  min-height: 100vh;
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

/* Table Styles */
.drivers-table-container {
  background: white;
  border-radius: 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  border: 1px solid #f1f5f9;
  overflow: visible;
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
  white-space: nowrap;
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
  min-width: 200px;
}

.user-avatar-table {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  object-fit: cover;
  border: 2px solid #e2e8f0;
  flex-shrink: 0;
}

.user-details-table {
  min-width: 0;
}

.user-name-table {
  font-weight: 600;
  color: #0f172a;
  margin-bottom: 2px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.user-id-table {
  font-size: 12px;
  color: #94a3b8;
  font-weight: 500;
  white-space: nowrap;
}

/* Contact Cell */
.contact-cell {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 150px;
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

.age-info {
  color: #334155;
  font-weight: 500;
}

.vehicle-assignment {
  min-width: 150px;
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
  white-space: nowrap;
}

.modern-badge.badge-info {
  background: #e0e7ff;
  color: #3730a3;
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

/* Performance Cell */
.performance-cell {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 120px;
}

.rating-display {
  display: flex;
  align-items: center;
  gap: 8px;
}

.rating-score {
  font-weight: 600;
  color: #0f172a;
}

.rating-stars {
  display: flex;
  gap: 2px;
}

.star {
  color: #d1d5db;
  font-size: 14px;
}

.star.filled {
  color: #fbbf24;
}

.trips-count {
  color: #94a3b8;
  font-size: 13px;
}

/* Action Cell */
.action-cell {
  position: relative;
}

.action-btn {
  width: 36px;
  height: 36px;
  background: transparent;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}

.action-btn:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
}

.action-btn svg {
  width: 18px;
  height: 18px;
  color: #64748b;
}

/* Modern Dropdown */
.modern-dropdown {
  position: absolute;
  right: 0;
  top: calc(100% + 8px);
  background: white;
  border-radius: 12px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
  min-width: 200px;
  z-index: 1000;
  padding: 8px;
  animation: slideIn 0.2s ease-out;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
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
  flex-shrink: 0;
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
  margin: 0 0 16px 0;
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
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
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

.modal-header-danger {
  background: #fef2f2;
  border-bottom-color: #fee2e2;
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
  flex-shrink: 0;
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

.form-group label .required {
  color: #ef4444;
  font-weight: 700;
  margin-left: 2px;
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

.form-grid-full {
  grid-column: 1 / -1;
}

.form-help {
  font-size: 12px;
  color: #94a3b8;
  margin-top: 4px;
}

.form-control {
  width: 100%;
}

/* Image Preview */
.current-image-preview,
.new-image-preview {
  margin-bottom: 12px;
  padding: 16px;
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  text-align: center;
}

.current-avatar,
.preview-avatar {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #e5e7eb;
  margin-bottom: 8px;
}

.image-help-text {
  font-size: 13px;
  color: #6b7280;
  margin: 8px 0;
}

/* Buttons */
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

.btn-primary:hover:not(:disabled) {
  background: #5a67d8;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn-secondary {
  background: white;
  color: #334155;
  border: 1px solid #e2e8f0;
}

.btn-secondary:hover:not(:disabled) {
  background: #f8fafc;
  border-color: #cbd5e1;
}

.btn-danger {
  background: #ef4444;
  color: white;
}

.btn-danger:hover:not(:disabled) {
  background: #dc2626;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none !important;
}

.btn-sm {
  padding: 6px 12px;
  font-size: 12px;
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

/* View Modal */
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
  text-align: left;
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
  word-break: break-word;
}

.status-badges-view {
  margin-top: 16px;
  display: flex;
  flex-direction: column;
  gap: 8px;
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

.vehicle-assignment-info .assigned-vehicle-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.vehicle-details h5 {
  font-weight: 600;
  color: #0f172a;
  margin: 0 0 4px 0;
}

.vehicle-details p {
  color: #64748b;
  margin: 0;
  font-size: 14px;
}

.no-vehicle-assigned {
  padding: 16px;
  text-align: center;
}

.performance-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  gap: 16px;
}

.metric-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 16px;
  text-align: center;
}

.metric-value {
  font-size: 28px;
  font-weight: 700;
  color: #0f172a;
  margin-bottom: 4px;
}

.metric-label {
  font-size: 12px;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.notes-text {
  background: white;
  padding: 16px;
  border-radius: 8px;
  line-height: 1.6;
  color: #334155;
}

/* Assignment Modals */
.driver-assignment-info {
  margin-bottom: 24px;
}

.assignment-header h4 {
  margin: 0 0 16px 0;
  color: #0f172a;
  font-size: 16px;
  font-weight: 600;
}

.driver-card {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 12px;
  padding: 20px;
  color: white;
}

.driver-info .driver-name {
  display: block;
  font-size: 18px;
  font-weight: 700;
  margin-bottom: 6px;
}

.driver-info .driver-details {
  display: block;
  font-size: 14px;
  opacity: 0.9;
  margin-bottom: 10px;
}

.selected-vehicle-preview {
  margin-top: 24px;
  padding-top: 24px;
  border-top: 1px solid #e2e8f0;
}

.selected-vehicle-preview h5 {
  margin: 0 0 16px 0;
  color: #0f172a;
  font-size: 16px;
  font-weight: 600;
}

.vehicle-preview-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 16px;
}

.vehicle-info .vehicle-reg {
  font-size: 18px;
  font-weight: 600;
  color: #0f172a;
  margin-bottom: 8px;
}

.vehicle-details {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.vehicle-details span {
  color: #64748b;
  font-size: 14px;
}

.vehicle-stats {
  display: flex;
  gap: 24px;
  align-items: center;
}

.stat-item {
  display: flex;
  flex-direction: column;
  text-align: center;
}

.stat-value {
  font-size: 24px;
  font-weight: 700;
  color: #0f172a;
  line-height: 1;
}

.stat-label {
  font-size: 11px;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-top: 4px;
}

/* Unassign Modal */
.unassign-confirmation-content .confirmation-message {
  font-size: 15px;
  color: #374151;
  margin-bottom: 24px;
  line-height: 1.6;
}

.assignment-details {
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 20px;
}

.detail-section {
  margin-bottom: 20px;
}

.detail-section:last-child {
  margin-bottom: 0;
}

.detail-section h5 {
  font-size: 13px;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin: 0 0 12px 0;
}

.driver-summary,
.vehicle-summary {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.driver-summary .driver-name,
.vehicle-summary .vehicle-reg {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
}

.driver-contact {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.driver-contact span,
.vehicle-summary .vehicle-info {
  color: #64748b;
  font-size: 14px;
}

.driver-id {
  color: #94a3b8;
  font-size: 13px;
}

.warning-note {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  background: #fef3c7;
  border: 1px solid #fbbf24;
  border-radius: 8px;
  padding: 16px;
}

.warning-icon {
  width: 20px;
  height: 20px;
  color: #d97706;
  flex-shrink: 0;
  margin-top: 2px;
}

.warning-note p {
  margin: 0;
  color: #92400e;
  font-size: 14px;
  line-height: 1.5;
}

.text-sm {
  font-size: 14px;
}

.text-gray-600 {
  color: #64748b;
}

.mt-2 {
  margin-top: 8px;
}

/* Responsive */
@media (max-width: 1024px) {
  .user-view-grid {
    grid-template-columns: 1fr;
  }
  
  .details-grid-view {
    grid-template-columns: 1fr;
  }

  .vehicle-preview-card {
    flex-direction: column;
    align-items: flex-start;
  }

  .vehicle-stats {
    align-self: stretch;
    justify-content: space-around;
  }
}

@media (max-width: 768px) {
  .drivers-page {
    padding: 16px;
  }
  
  .page-header {
    flex-direction: column;
    gap: 16px;
    align-items: stretch;
  }

  .page-header-actions {
    flex-wrap: wrap;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .filters-section {
    flex-direction: column;
  }
  
  .search-wrapper {
    min-width: 100%;
  }
  
  .filters-group {
    flex-direction: column;
  }
  
  .filter-select {
    width: 100%;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }

  .performance-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .pagination-container {
    flex-direction: column;
    text-align: center;
  }

  .pagination-controls {
    flex-wrap: wrap;
    justify-content: center;
  }
}

@media (max-width: 640px) {
  .modern-table {
    font-size: 13px;
  }

  .modern-table th,
  .modern-table td {
    padding: 12px 16px;
  }

  .performance-grid {
    grid-template-columns: 1fr;
  }

  .vehicle-stats {
    gap: 16px;
  }

  .modal {
    margin: 10px;
  }

  .modal-body {
    padding: 20px;
  }

  .modal-actions {
    padding: 16px 20px;
  }
}
</style>