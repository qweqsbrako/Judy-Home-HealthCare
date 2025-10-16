<template>
  <MainLayout>
    <div class="vehicles-page">
      <!-- Page Header -->
      <div class="page-header">
        <div class="page-header-content">
          <h1>Vehicles Management</h1>
          <p>Manage transportation vehicles and their assignments</p>
        </div>
        <div class="page-header-actions">
          <button @click="exportVehicles" class="btn-modern btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Export
          </button>
          <button @click="openCreateModal" class="btn-modern btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Add Vehicle
          </button>
        </div>
      </div>

      <!-- Stats Grid -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon blue">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Total Vehicles</div>
            <div class="stat-value">{{ statistics.total_vehicles || 0 }}</div>
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
            <div class="stat-label">Available</div>
            <div class="stat-value">{{ statistics.available_vehicles || 0 }}</div>
            <div class="stat-change positive">Ready for use</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon purple">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Assigned</div>
            <div class="stat-value">{{ statistics.assigned_vehicles || 0 }}</div>
            <div class="stat-change neutral">{{ statistics.unassigned_vehicles || 0 }} unassigned</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon yellow">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">In Maintenance</div>
            <div class="stat-value">{{ statistics.vehicles_in_maintenance || 0 }}</div>
            <div class="stat-change neutral">{{ statistics.vehicles_out_of_service || 0 }} out of service</div>
          </div>
        </div>
      </div>

      <!-- DEBUG: Remove this after checking -->
      <div v-if="false" style="background: white; padding: 20px; margin-bottom: 20px; border-radius: 10px;">
        <h3>Debug Statistics:</h3>
        <pre>{{ JSON.stringify(statistics, null, 2) }}</pre>
      </div>

      <!-- Filters Section -->
      <div class="filters-section">
        <div class="search-wrapper">
          <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <input
            type="text"
            placeholder="Search by registration, make, model, VIN..."
            v-model="searchQuery"
            class="search-input"
          />
        </div>
        <div class="filters-group">
          <select v-model="vehicleTypeFilter" class="filter-select">
            <option value="all">All Types</option>
            <option value="ambulance">Ambulance</option>
            <option value="regular">Regular</option>
          </select>
          
          <select v-model="statusFilter" class="filter-select">
            <option value="all">All Status</option>
            <option value="available">Available</option>
            <option value="in_use">In Use</option>
            <option value="maintenance">Maintenance</option>
            <option value="out_of_service">Out of Service</option>
          </select>
          
          <select v-model="assignedFilter" class="filter-select">
            <option value="all">All Vehicles</option>
            <option value="true">Assigned</option>
            <option value="false">Unassigned</option>
          </select>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="loading-state">
        <div class="loading-spinner"></div>
        <p class="loading-text">Loading vehicles...</p>
      </div>

      <!-- Vehicles Table -->
      <div v-else-if="!loading" class="vehicles-table-container">
        <div v-if="vehicles.data && vehicles.data.length > 0" class="table-wrapper">
          <table class="modern-table">
            <thead>
              <tr>
                <th>Vehicle</th>
                <th>Type</th>
                <th>Status</th>
                <th>Assigned Driver</th>
                <th>Usage</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="vehicle in vehicles.data" :key="vehicle.id">
                <td>
                  <div class="vehicle-cell">
                    <div class="vehicle-icon">
                      <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                      </svg>
                    </div>
                    <div class="vehicle-details-table">
                      <div class="vehicle-number-table">{{ vehicle.registration_number }}</div>
                      <div class="vehicle-model-table">{{ vehicle.make }} {{ vehicle.model }} {{ vehicle.year }}</div>
                      <div class="vehicle-color-table">{{ vehicle.vehicle_color }}</div>
                    </div>
                  </div>
                </td>
                
                <td>
                  <span class="modern-badge" :class="getVehicleTypeBadgeColor(vehicle.vehicle_type)">
                    {{ capitalizeFirst(vehicle.vehicle_type) }}
                  </span>
                </td>
                
                <td>
                  <div class="status-cell">
                    <span class="modern-badge" :class="getStatusBadgeColor(vehicle.status)">
                      {{ vehicle.status_label }}
                    </span>
                    <div v-if="!vehicle.is_active" class="inactive-indicator">
                      <span class="modern-badge badge-secondary">Inactive</span>
                    </div>
                  </div>
                </td>
                
                <td>
                  <div v-if="vehicle.current_driver" class="driver-assignment">
                    <div class="contact-cell">
                      <div class="contact-primary">{{ vehicle.current_driver.full_name }}</div>
                      <div class="contact-secondary">{{ vehicle.current_driver.phone }}</div>
                    </div>
                  </div>
                  <div v-else class="text-secondary">Not assigned</div>
                </td>
                
                <td>
                  <div class="usage-cell">
                    <div class="usage-primary">{{ getVehicleTrips(vehicle) }} trips</div>
                    <div v-if="vehicle.mileage" class="usage-secondary">{{ vehicle.mileage }} km</div>
                  </div>
                </td>
                
                <td>
                  <div class="action-cell">
                    <button @click="toggleDropdown(vehicle.id)" class="action-btn">
                      <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                      </svg>
                    </button>
                    
                    <div v-show="activeDropdown === vehicle.id" class="modern-dropdown">
                      <button @click="openViewModal(vehicle)" class="dropdown-item-modern">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        View Details
                      </button>
                      
                      <button @click="openEditModal(vehicle)" class="dropdown-item-modern">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Edit Vehicle
                      </button>
                      
                      <div class="dropdown-divider"></div>
                      
                      <button
                        v-if="!vehicle.current_driver && vehicle.status === 'available'"
                        @click="openAssignDriverModal(vehicle)"
                        class="dropdown-item-modern success"
                      >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Assign Driver
                      </button>
                      
                      <button
                        v-if="vehicle.current_driver"
                        @click="openUnassignDriverModal(vehicle)"
                        class="dropdown-item-modern warning"
                      >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Unassign Driver
                      </button>
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>

          <!-- Pagination -->
          <div v-if="vehicles.last_page > 1" class="pagination-container">
            <div class="pagination-info">
              Showing {{ (vehicles.current_page - 1) * vehicles.per_page + 1 }} to {{ Math.min(vehicles.current_page * vehicles.per_page, vehicles.total) }} of {{ vehicles.total }} vehicles
            </div>
            <div class="pagination-controls">
              <button 
                @click="prevPage" 
                :disabled="vehicles.current_page === 1"
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
                  :class="['pagination-page', { active: page === vehicles.current_page }]"
                >
                  {{ page }}
                </button>
              </div>
              
              <button 
                @click="nextPage" 
                :disabled="vehicles.current_page === vehicles.last_page"
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
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
          </svg>
          <h3>No vehicles found</h3>
          <p>
            {{ hasActiveFilters ? 'Try adjusting your search or filters.' : 'Start by adding a new vehicle.' }}
          </p>
          <button @click="openCreateModal" class="btn btn-primary">
            Add First Vehicle
          </button>
        </div>
      </div>

      <!-- Create/Edit Vehicle Modal -->
      <div v-if="showVehicleModal" class="modal-overlay" @click.self="closeVehicleModal">
        <div class="modal modal-lg">
          <div class="modal-header">
            <h2 class="modal-title">
              {{ isEditing ? 'Edit Vehicle' : 'Add New Vehicle' }}
            </h2>
            <button @click="closeVehicleModal" class="modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <form @submit.prevent="saveVehicle">
            <div class="modal-body">
              <div class="form-grid">
                <!-- Basic Information -->
                <div class="form-section-header">
                  <h3 class="form-section-title">Basic Information</h3>
                </div>

                <div class="form-group">
                  <label>Vehicle Type <span class="required">*</span></label>
                  <SearchableSelect
                    v-model="vehicleForm.vehicle_type"
                    :options="vehicleTypeOptions"
                    placeholder="Select vehicle type..."
                    required
                  />
                </div>

                <div class="form-group">
                  <label>Registration Number <span class="required">*</span></label>
                  <input
                    type="text"
                    v-model="vehicleForm.registration_number"
                    placeholder="GR-1234-20"
                    required
                  />
                </div>

                <div class="form-group">
                  <label>Vehicle Color <span class="required">*</span></label>
                  <input
                    type="text"
                    v-model="vehicleForm.vehicle_color"
                    placeholder="e.g., White, Black, Blue"
                    required
                  />
                </div>

                <!-- Vehicle Details -->
                <div class="form-section-header">
                  <h3 class="form-section-title">Vehicle Details</h3>
                </div>

                <div class="form-group">
                  <label>Make</label>
                  <input
                    type="text"
                    v-model="vehicleForm.make"
                    placeholder="e.g., Toyota, Honda"
                  />
                </div>

                <div class="form-group">
                  <label>Model</label>
                  <input
                    type="text"
                    v-model="vehicleForm.model"
                    placeholder="e.g., Camry, Civic"
                  />
                </div>

                <div class="form-group">
                  <label>Year</label>
                  <input
                    type="number"
                    min="1990"
                    :max="new Date().getFullYear() + 2"
                    v-model="vehicleForm.year"
                  />
                </div>

                <div class="form-group">
                  <label>VIN Number</label>
                  <input
                    type="text"
                    v-model="vehicleForm.vin_number"
                    placeholder="17-character VIN"
                    maxlength="17"
                  />
                </div>

                <div class="form-group">
                  <label>Current Mileage (km)</label>
                  <input
                    type="number"
                    min="0"
                    v-model="vehicleForm.mileage"
                  />
                </div>

                <!-- Insurance & Registration -->
                <div class="form-section-header">
                  <h3 class="form-section-title">Insurance & Registration</h3>
                </div>

                <div class="form-group">
                  <label>Insurance Policy Number</label>
                  <input
                    type="text"
                    v-model="vehicleForm.insurance_policy"
                  />
                </div>

                <div class="form-group">
                  <label>Insurance Expiry Date</label>
                  <input
                    type="date"
                    v-model="vehicleForm.insurance_expiry"
                    :min="today"
                  />
                </div>

                <div class="form-group">
                  <label>Registration Expiry Date</label>
                  <input
                    type="date"
                    v-model="vehicleForm.registration_expiry"
                    :min="today"
                  />
                </div>

                <!-- Maintenance -->
                <div class="form-section-header">
                  <h3 class="form-section-title">Maintenance</h3>
                </div>

                <div class="form-group">
                  <label>Last Service Date</label>
                  <input
                    type="date"
                    v-model="vehicleForm.last_service_date"
                    :max="today"
                  />
                </div>

                <div class="form-group">
                  <label>Next Service Date</label>
                  <input
                    type="date"
                    v-model="vehicleForm.next_service_date"
                    :min="today"
                  />
                </div>

                <!-- Additional Information -->
                <div class="form-group form-grid-full">
                  <label>Notes</label>
                  <textarea
                    v-model="vehicleForm.notes"
                    rows="3"
                    placeholder="Any additional information about the vehicle..."
                  ></textarea>
                </div>
              </div>
            </div>

            <div class="modal-actions">
              <button type="button" @click="closeVehicleModal" class="btn btn-secondary">
                Cancel
              </button>
              <button type="submit" :disabled="isSaving" class="btn btn-primary">
                <div v-if="isSaving" class="spinner spinner-sm"></div>
                {{ isEditing ? 'Update Vehicle' : 'Add Vehicle' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- View Vehicle Modal -->
      <div v-if="showViewModal && currentVehicle" class="modal-overlay" @click.self="closeViewModal">
        <div class="modal modal-xl">
          <div class="modal-header">
            <h2 class="modal-title">Vehicle Details</h2>
            <button @click="closeViewModal" class="modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="modal-body">
            <div class="user-view-grid">
              <div class="user-profile-section">
                <div class="vehicle-icon-large">
                  <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                  </svg>
                </div>
                <h3 class="profile-name-view">
                  {{ currentVehicle.registration_number }}
                </h3>
                <span class="modern-badge badge-info">Vehicle ID: {{ currentVehicle.vehicle_id }}</span>
                
                <div class="profile-contact-view">
                  <div class="contact-item-view">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                    <span>{{ currentVehicle.make }} {{ currentVehicle.model }}</span>
                  </div>
                  
                  <div class="contact-item-view">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                    </svg>
                    <span>{{ currentVehicle.vehicle_color }}</span>
                  </div>
                </div>
                
                <div class="status-badges-view">
                  <span class="modern-badge" :class="getStatusBadgeColor(currentVehicle.status)">
                    {{ currentVehicle.status_label }}
                  </span>
                  <span class="modern-badge" :class="getVehicleTypeBadgeColor(currentVehicle.vehicle_type)">
                    {{ capitalizeFirst(currentVehicle.vehicle_type) }}
                  </span>
                </div>
              </div>

              <div class="details-section-view">
                <div class="details-group">
                  <h4 class="details-header">Vehicle Information</h4>
                  <div class="details-grid-view">
                    <div class="detail-item-view">
                      <label>Registration</label>
                      <p>{{ currentVehicle.registration_number }}</p>
                    </div>
                    <div class="detail-item-view">
                      <label>Type</label>
                      <p>{{ capitalizeFirst(currentVehicle.vehicle_type) }}</p>
                    </div>
                    <div class="detail-item-view">
                      <label>Make</label>
                      <p>{{ currentVehicle.make || 'Not specified' }}</p>
                    </div>
                    <div class="detail-item-view">
                      <label>Model</label>
                      <p>{{ currentVehicle.model || 'Not specified' }}</p>
                    </div>
                    <div class="detail-item-view">
                      <label>Year</label>
                      <p>{{ currentVehicle.year || 'Not specified' }}</p>
                    </div>
                    <div class="detail-item-view">
                      <label>VIN</label>
                      <p>{{ currentVehicle.vin_number || 'Not specified' }}</p>
                    </div>
                  </div>
                </div>

                <div class="details-group">
                  <h4 class="details-header">Driver Assignment</h4>
                  <div v-if="currentVehicle.current_driver" class="vehicle-assignment-info">
                    <div class="assigned-vehicle-card">
                      <div class="vehicle-details">
                        <h5>{{ currentVehicle.current_driver.full_name }}</h5>
                        <p>{{ currentVehicle.current_driver.phone }}</p>
                      </div>
                      <span class="modern-badge badge-info">
                        Assigned Driver
                      </span>
                    </div>
                  </div>
                  <div v-else class="no-vehicle-assigned">
                    <p class="text-secondary">No driver currently assigned</p>
                  </div>
                </div>

                <div class="details-group">
                  <h4 class="details-header">Documents & Compliance</h4>
                  <div class="details-grid-view">
                    <div class="detail-item-view">
                      <label>Insurance Policy</label>
                      <p>{{ currentVehicle.insurance_policy || 'Not provided' }}</p>
                    </div>
                    <div class="detail-item-view">
                      <label>Insurance Expiry</label>
                      <p>{{ formatDate(currentVehicle.insurance_expiry) }}</p>
                    </div>
                    <div class="detail-item-view">
                      <label>Registration Expiry</label>
                      <p>{{ formatDate(currentVehicle.registration_expiry) }}</p>
                    </div>
                  </div>
                </div>

                <div class="details-group">
                  <h4 class="details-header">Usage & Maintenance</h4>
                  <div class="performance-grid">
                    <div class="metric-card">
                      <div class="metric-value">{{ getVehicleTrips(currentVehicle) }}</div>
                      <div class="metric-label">Total Trips</div>
                    </div>
                    <div class="metric-card">
                      <div class="metric-value">{{ currentVehicle.mileage || 'N/A' }}</div>
                      <div class="metric-label">Mileage (km)</div>
                    </div>
                    <div class="metric-card">
                      <div class="metric-value">{{ formatDate(currentVehicle.last_service_date) }}</div>
                      <div class="metric-label">Last Service</div>
                    </div>
                    <div class="metric-card">
                      <div class="metric-value">{{ formatDate(currentVehicle.next_service_date) }}</div>
                      <div class="metric-label">Next Service</div>
                    </div>
                  </div>
                </div>

                <div v-if="currentVehicle.notes" class="details-group">
                  <h4 class="details-header">Additional Notes</h4>
                  <p class="notes-text">{{ currentVehicle.notes }}</p>
                </div>
              </div>
            </div>
          </div>

          <div class="modal-actions">
            <button @click="closeViewModal" class="btn btn-secondary">Close</button>
            <button @click="editFromView" class="btn btn-primary">Edit Vehicle</button>
          </div>
        </div>
      </div>

      <!-- Assign Driver Modal -->
      <div v-if="showAssignDriverModal" class="modal-overlay" @click.self="closeAssignDriverModal">
        <div class="modal modal-lg">
          <div class="modal-header">
            <h3 class="modal-title">Assign Driver to Vehicle</h3>
            <button @click="closeAssignDriverModal" class="modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <form @submit.prevent="assignDriver">
            <div class="modal-body">
              <div class="driver-assignment-info">
                <div class="assignment-header">
                  <h4>Assigning driver to:</h4>
                  <div class="driver-card">
                    <div class="driver-info">
                      <span class="driver-name">{{ currentVehicle?.registration_number }}</span>
                      <span class="driver-details">{{ currentVehicle?.make }} {{ currentVehicle?.model }}</span>
                      <span class="modern-badge badge-info">{{ capitalizeFirst(currentVehicle?.vehicle_type) }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <div class="form-grid">
                <div class="form-group form-grid-full">
                  <label>Select Driver <span class="required">*</span></label>
                  <SearchableSelect
                    v-model="assignmentForm.driver_id"
                    :options="driverOptions"
                    placeholder="Search for available drivers..."
                    required
                    :loading="loadingDrivers"
                  />
                  <p class="form-help">
                    {{ driverOptions.length }} available drivers found
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

              <div v-if="selectedDriverPreview" class="selected-vehicle-preview">
                <h5>Selected Driver:</h5>
                <div class="vehicle-preview-card">
                  <div class="vehicle-info">
                    <div class="vehicle-reg">{{ selectedDriverPreview.full_name }}</div>
                    <div class="vehicle-details">
                      <span>{{ selectedDriverPreview.phone }}</span>
                      <span v-if="selectedDriverPreview.email">{{ selectedDriverPreview.email }}</span>
                    </div>
                  </div>
                  <div class="vehicle-stats">
                    <div class="stat-item">
                      <span class="stat-value">{{ selectedDriverPreview.average_rating || 'N/A' }}</span>
                      <span class="stat-label">Rating</span>
                    </div>
                    <div class="stat-item">
                      <span class="stat-value">{{ selectedDriverPreview.total_trips || 0 }}</span>
                      <span class="stat-label">Trips</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="modal-actions">
              <button type="button" @click="closeAssignDriverModal" class="btn btn-secondary">
                Cancel
              </button>
              <button
                type="submit"
                :disabled="isSaving || !assignmentForm.driver_id"
                class="btn btn-primary"
              >
                <div v-if="isSaving" class="spinner spinner-sm"></div>
                Assign Driver
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Unassign Driver Modal -->
      <div v-if="showUnassignDriverModal && currentVehicle" class="modal-overlay" @click.self="closeUnassignDriverModal">
        <div class="modal modal-lg">
          <div class="modal-header modal-header-danger">
            <h3 class="modal-title">Unassign Driver from Vehicle</h3>
            <button @click="closeUnassignDriverModal" class="modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          
          <form @submit.prevent="unassignDriver">
            <div class="modal-body">
              <div class="unassign-confirmation-content">
                <p class="confirmation-message">
                  Are you sure you want to unassign the driver from this vehicle? This action will remove the current driver assignment.
                </p>

                <div class="assignment-details">
                  <div class="detail-section">
                    <h5>Vehicle</h5>
                    <div class="driver-summary">
                      <div class="driver-name">{{ currentVehicle.registration_number }}</div>
                      <div class="driver-contact">
                        <span>{{ currentVehicle.make }} {{ currentVehicle.model }} {{ currentVehicle.year }}</span>
                        <span>{{ currentVehicle.vehicle_color }}</span>
                      </div>
                      <div class="driver-id">{{ capitalizeFirst(currentVehicle.vehicle_type) }}</div>
                    </div>
                  </div>

                  <div v-if="currentVehicle.current_driver" class="detail-section">
                    <h5>Current Driver</h5>
                    <div class="vehicle-summary">
                      <div class="vehicle-reg">{{ currentVehicle.current_driver.full_name }}</div>
                      <div class="vehicle-info">{{ currentVehicle.current_driver.phone }}</div>
                      <span v-if="currentVehicle.current_driver.email" class="modern-badge badge-info">
                        {{ currentVehicle.current_driver.email }}
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
              <button type="button" @click="closeUnassignDriverModal" class="btn btn-secondary">
                Cancel
              </button>
              <button
                type="submit"
                :disabled="isUnassigning"
                class="btn btn-danger"
              >
                <div v-if="isUnassigning" class="spinner spinner-sm"></div>
                Unassign Driver
              </button>
            </div>
          </form>
        </div>
      </div>

      <Toast />
    </div>
  </MainLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, inject, watch } from 'vue'
import MainLayout from '../../layout/MainLayout.vue'
import Toast from '../../common/components/Toast.vue'
import SearchableSelect from '../../common/components/SearchableSelect.vue'
import * as vehicleService from '../../services/vehicleService'

const toast = inject('toast')

// Reactive data
const vehicles = ref({ data: [], total: 0, per_page: 15, current_page: 1, last_page: 1 })
const availableDrivers = ref([])
const statistics = ref({
  total_vehicles: 0,
  active_vehicles: 0,
  available_vehicles: 0,
  assigned_vehicles: 0,
  unassigned_vehicles: 0,
  vehicles_in_maintenance: 0,
  vehicles_out_of_service: 0
})
const loading = ref(true)
const loadingDrivers = ref(false)
const searchQuery = ref('')
const vehicleTypeFilter = ref('all')
const statusFilter = ref('all')
const assignedFilter = ref('all')

// Modal states
const showVehicleModal = ref(false)
const showViewModal = ref(false)
const showAssignDriverModal = ref(false)
const showUnassignDriverModal = ref(false)
const isEditing = ref(false)
const currentVehicle = ref(null)
const isSaving = ref(false)
const isUnassigning = ref(false)

// Dropdown state
const activeDropdown = ref(null)

// Form data
const vehicleForm = ref({
  vehicle_type: '',
  registration_number: '',
  vehicle_color: '',
  make: '',
  model: '',
  year: '',
  vin_number: '',
  mileage: '',
  insurance_policy: '',
  insurance_expiry: '',
  registration_expiry: '',
  last_service_date: '',
  next_service_date: '',
  notes: ''
})

const assignmentForm = ref({
  driver_id: '',
  notes: ''
})

// Computed properties
const today = computed(() => new Date().toISOString().split('T')[0])

const hasActiveFilters = computed(() => {
  return searchQuery.value || vehicleTypeFilter.value !== 'all' ||
    statusFilter.value !== 'all' || assignedFilter.value !== 'all'
})

const vehicleTypeOptions = [
  { value: 'ambulance', label: 'Ambulance', searchText: 'ambulance emergency' },
  { value: 'regular', label: 'Regular', searchText: 'regular standard normal' }
]

const driverOptions = computed(() => {
  return availableDrivers.value.map(driver => ({
    value: driver.id,
    label: `${driver.full_name} (${driver.phone})`,
    searchText: `${driver.full_name} ${driver.phone} ${driver.email || ''}`.toLowerCase()
  }))
})

const selectedDriverPreview = computed(() => {
  if (!assignmentForm.value.driver_id) return null
  return availableDrivers.value.find(driver => driver.id == assignmentForm.value.driver_id)
})

// Methods
const loadVehicles = async (page = 1) => {
  loading.value = true
  try {
    const params = {
      page: page,
      per_page: 15,
      search: searchQuery.value || undefined,
      vehicle_type: vehicleTypeFilter.value !== 'all' ? vehicleTypeFilter.value : undefined,
      status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
      assigned: assignedFilter.value !== 'all' ? assignedFilter.value : undefined
    }
    
    Object.keys(params).forEach(key => params[key] === undefined && delete params[key])
    
    const response = await vehicleService.getVehicles(params)
    
    console.log('API Response:', response)
    
    // Your backend returns: { success: true, data: [...], pagination: {...} }
    if (response.success) {
      vehicles.value = {
        data: response.data || [],
        total: response.pagination?.total || 0,
        current_page: response.pagination?.current_page || 1,
        last_page: response.pagination?.last_page || 1,
        per_page: response.pagination?.per_page || 15
      }
    } else {
      // Fallback for unexpected response format
      vehicles.value = {
        data: [],
        total: 0,
        current_page: 1,
        last_page: 1,
        per_page: 15
      }
    }
    
    console.log('Processed vehicles:', vehicles.value)
  } catch (error) {
    console.error('Error loading vehicles:', error)
    toast.showError('Failed to load vehicles')
    vehicles.value = { data: [], total: 0, current_page: 1, last_page: 1, per_page: 15 }
  }
  loading.value = false
}

const loadAvailableDrivers = async () => {
  loadingDrivers.value = true
  try {
    const response = await vehicleService.getAvailableDrivers()
    availableDrivers.value = response.data || []
  } catch (error) {
    console.error('Error loading available drivers:', error)
    toast.showError('Failed to load available drivers')
  }
  loadingDrivers.value = false
}

const getStatistics = async () => {
  try {
    const response = await vehicleService.getDashboardStats()
    console.log('Statistics Response:', response)
    
    // Handle different response structures
    let statsData = {}
    
    if (response.success && response.data) {
      statsData = response.data
    } else if (response.data && typeof response.data === 'object') {
      statsData = response.data
    } else if (typeof response === 'object' && !response.success) {
      statsData = response
    }
    
    // Ensure all required fields exist with defaults
    statistics.value = {
      total_vehicles: statsData.total_vehicles || 0,
      active_vehicles: statsData.active_vehicles || 0,
      available_vehicles: statsData.available_vehicles || 0,
      assigned_vehicles: statsData.assigned_vehicles || 0,
      unassigned_vehicles: statsData.unassigned_vehicles || 0,
      vehicles_in_maintenance: statsData.vehicles_in_maintenance || 0,
      vehicles_out_of_service: statsData.vehicles_out_of_service || 0,
      by_type: statsData.by_type || {},
      by_status: statsData.by_status || {},
      insurance_expiring: statsData.insurance_expiring || 0,
      registration_expiring: statsData.registration_expiring || 0,
      insurance_expired: statsData.insurance_expired || 0,
      registration_expired: statsData.registration_expired || 0,
      usage_metrics: statsData.usage_metrics || {}
    }
    
    console.log('Processed statistics:', statistics.value)
  } catch (error) {
    console.error('Error loading statistics:', error)
    // Set default empty statistics on error
    statistics.value = {
      total_vehicles: 0,
      active_vehicles: 0,
      available_vehicles: 0,
      assigned_vehicles: 0,
      unassigned_vehicles: 0,
      vehicles_in_maintenance: 0,
      vehicles_out_of_service: 0,
      by_type: {},
      by_status: {},
      insurance_expiring: 0,
      registration_expiring: 0,
      insurance_expired: 0,
      registration_expired: 0,
      usage_metrics: {}
    }
  }
}

const getVehicleTypeBadgeColor = (type) => {
  const colorMap = {
    'ambulance': 'badge-danger',
    'regular': 'badge-primary'
  }
  return colorMap[type] || 'badge-secondary'
}

const getStatusBadgeColor = (status) => {
  const colorMap = {
    'available': 'badge-success',
    'in_use': 'badge-primary',
    'maintenance': 'badge-warning',
    'out_of_service': 'badge-danger'
  }
  return colorMap[status] || 'badge-secondary'
}

const getVehicleTrips = (vehicle) => {
  return vehicle.total_trips || 0
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

const toggleDropdown = (vehicleId) => {
  activeDropdown.value = activeDropdown.value === vehicleId ? null : vehicleId
}

const openCreateModal = () => {
  isEditing.value = false
  currentVehicle.value = null
  resetForm()
  showVehicleModal.value = true
}

const openEditModal = (vehicle) => {
  isEditing.value = true
  currentVehicle.value = vehicle
  populateForm(vehicle)
  showVehicleModal.value = true
  activeDropdown.value = null
}

const openViewModal = (vehicle) => {
  currentVehicle.value = vehicle
  showViewModal.value = true
  activeDropdown.value = null
}

const openAssignDriverModal = async (vehicle) => {
  currentVehicle.value = vehicle
  assignmentForm.value = { driver_id: '', notes: '' }
  showAssignDriverModal.value = true
  await loadAvailableDrivers()
  activeDropdown.value = null
}

const openUnassignDriverModal = (vehicle) => {
  currentVehicle.value = vehicle
  showUnassignDriverModal.value = true
  activeDropdown.value = null
}

const closeVehicleModal = () => {
  showVehicleModal.value = false
}

const closeViewModal = () => {
  showViewModal.value = false
}

const closeAssignDriverModal = () => {
  showAssignDriverModal.value = false
}

const closeUnassignDriverModal = () => {
  showUnassignDriverModal.value = false
  currentVehicle.value = null
}

const editFromView = () => {
  closeViewModal()
  openEditModal(currentVehicle.value)
}

const resetForm = () => {
  vehicleForm.value = {
    vehicle_type: '',
    registration_number: '',
    vehicle_color: '',
    make: '',
    model: '',
    year: '',
    vin_number: '',
    mileage: '',
    insurance_policy: '',
    insurance_expiry: '',
    registration_expiry: '',
    last_service_date: '',
    next_service_date: '',
    notes: ''
  }
}

const populateForm = (vehicle) => {
  vehicleForm.value = {
    vehicle_type: vehicle.vehicle_type,
    registration_number: vehicle.registration_number,
    vehicle_color: vehicle.vehicle_color,
    make: vehicle.make || '',
    model: vehicle.model || '',
    year: vehicle.year || '',
    vin_number: vehicle.vin_number || '',
    mileage: vehicle.mileage || '',
    insurance_policy: vehicle.insurance_policy || '',
    insurance_expiry: formatDateForInput(vehicle.insurance_expiry),
    registration_expiry: formatDateForInput(vehicle.registration_expiry),
    last_service_date: formatDateForInput(vehicle.last_service_date),
    next_service_date: formatDateForInput(vehicle.next_service_date),
    notes: vehicle.notes || ''
  }
}

const saveVehicle = async () => {
  isSaving.value = true
  
  try {
    if (isEditing.value) {
      await vehicleService.updateVehicle(currentVehicle.value.id, vehicleForm.value)
    } else {
      await vehicleService.createVehicle(vehicleForm.value)
    }
    
    await Promise.all([loadVehicles(vehicles.value.current_page), getStatistics()])
    closeVehicleModal()
    toast.showSuccess(isEditing.value ? 'Vehicle updated successfully!' : 'Vehicle added successfully!')
  } catch (error) {
    console.error('Error saving vehicle:', error)
    toast.showError(error.message || 'Failed to save vehicle')
  }
  
  isSaving.value = false
}

const assignDriver = async () => {
  isSaving.value = true
  
  try {
    await vehicleService.assignDriver(currentVehicle.value.id, assignmentForm.value)
    await Promise.all([loadVehicles(vehicles.value.current_page), getStatistics()])
    closeAssignDriverModal()
    toast.showSuccess('Driver assigned successfully!')
  } catch (error) {
    console.error('Error assigning driver:', error)
    toast.showError(error.message || 'Failed to assign driver')
  }
  
  isSaving.value = false
}

const unassignDriver = async () => {
  isUnassigning.value = true
  
  try {
    await vehicleService.unassignDriver(currentVehicle.value.id)
    await Promise.all([loadVehicles(vehicles.value.current_page), getStatistics()])
    closeUnassignDriverModal()
    toast.showSuccess('Driver unassigned successfully!')
  } catch (error) {
    console.error('Error unassigning driver:', error)
    toast.showError(error.message || 'Failed to unassign driver')
  }
  
  isUnassigning.value = false
}

const exportVehicles = async () => {
  try {
    toast.showInfo('Preparing export...')
    
    const filters = {
      vehicle_type: vehicleTypeFilter.value !== 'all' ? vehicleTypeFilter.value : undefined,
      status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
      search: searchQuery.value || undefined
    }
    
    Object.keys(filters).forEach(key => filters[key] === undefined && delete filters[key])
    
    const { blob, filename } = await vehicleService.exportVehicles(filters)
    
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = filename
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
    
    toast.showSuccess('Vehicles exported successfully!')
  } catch (error) {
    console.error('Error exporting vehicles:', error)
    toast.showError('Failed to export vehicles')
  }
}

const goToPage = (page) => {
  if (page >= 1 && page <= vehicles.value.last_page) {
    loadVehicles(page)
  }
}

const nextPage = () => {
  if (vehicles.value.current_page < vehicles.value.last_page) {
    goToPage(vehicles.value.current_page + 1)
  }
}

const prevPage = () => {
  if (vehicles.value.current_page > 1) {
    goToPage(vehicles.value.current_page - 1)
  }
}

const getPaginationPages = () => {
  const pages = []
  const maxVisible = 5
  let start = Math.max(1, vehicles.value.current_page - Math.floor(maxVisible / 2))
  let end = Math.min(vehicles.value.last_page, start + maxVisible - 1)
  
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
    loadVehicles(1)
  }, 500)
})

watch([vehicleTypeFilter, statusFilter, assignedFilter], () => {
  loadVehicles(1)
})

watch(() => assignmentForm.value.driver_id, (newDriverId) => {
  if (newDriverId) {
    console.log('Selected driver:', selectedDriverPreview.value)
  }
})

onMounted(async () => {
  try {
    await Promise.all([
      loadVehicles(),
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

.vehicles-page {
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
.vehicles-table-container {
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

/* Vehicle Cell */
.vehicle-cell {
  display: flex;
  align-items: center;
  gap: 12px;
  min-width: 240px;
}

.vehicle-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.vehicle-icon svg {
  width: 24px;
  height: 24px;
  color: white;
}

.vehicle-details-table {
  min-width: 0;
}

.vehicle-number-table {
  font-weight: 600;
  color: #0f172a;
  margin-bottom: 2px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.vehicle-model-table {
  font-size: 13px;
  color: #64748b;
  margin-bottom: 2px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.vehicle-color-table {
  font-size: 12px;
  color: #94a3b8;
  font-weight: 500;
}

/* Status Cell */
.status-cell {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.inactive-indicator {
  display: inline-block;
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

/* Usage Cell */
.usage-cell {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.usage-primary {
  font-size: 14px;
  color: #334155;
  font-weight: 500;
}

.usage-secondary {
  font-size: 13px;
  color: #94a3b8;
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

.modern-badge.badge-primary {
  background: #dbeafe;
  color: #1e40af;
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

.form-section-header {
  grid-column: 1 / -1;
  margin-top: 8px;
}

.form-section-title {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
  padding-bottom: 12px;
  border-bottom: 2px solid #e2e8f0;
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

.vehicle-icon-large {
  width: 120px;
  height: 120px;
  border-radius: 20px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 16px;
}

.vehicle-icon-large svg {
  width: 60px;
  height: 60px;
  color: white;
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
  .vehicles-page {
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