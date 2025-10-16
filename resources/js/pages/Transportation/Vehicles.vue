<template>
  <MainLayout>
    <div class="vehicles-page">
      <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Page Header -->
        <div class="page-header">
          <div class="page-header-content">
            <h1>Vehicles Management</h1>
            <p>Manage transportation vehicles and their assignments</p>
          </div>
          <div class="page-header-actions">
            <button @click="exportVehicles" class="btn btn-secondary">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              Export
            </button>
            <button @click="openCreateModal" class="btn btn-primary">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
              </svg>
              Add Vehicle
            </button>
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
                placeholder="Search by registration number, make, model..."
                v-model="searchQuery"
                class="search-input"
              />
            </div>
            <div class="filters-group">
              <select v-model="vehicleTypeFilter" class="filter-select">
                <option value="all">All Vehicle Types</option>
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
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="loading-state">
          <div class="loading-spinner"></div>
          <p class="loading-text">Loading vehicles...</p>
        </div>

        <!-- Vehicles Table -->
        <div v-else class="vehicles-table-container">
          <div class="overflow-x-auto">
            <table class="vehicles-table">
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
                <tr v-for="vehicle in filteredVehicles" :key="vehicle.id">
                  <td>
                    <div class="vehicle-info">
                      <div class="vehicle-details">
                        <div class="vehicle-number">{{ vehicle.registration_number }}</div>
                        <div class="vehicle-model">{{ vehicle.make }} {{ vehicle.model }} {{ vehicle.year }}</div>
                        <div class="vehicle-color">{{ vehicle.vehicle_color }}</div>
                      </div>
                    </div>
                  </td>
                  <td>
                    <span :class="'badge ' + getVehicleTypeBadgeColor(vehicle.vehicle_type)">
                      {{ capitalizeFirst(vehicle.vehicle_type) }}
                    </span>
                  </td>
                  <td>
                    <div class="status-info">
                      <span :class="'badge ' + getStatusBadgeColor(vehicle.status)">
                        {{ vehicle.status_label }}
                      </span>
                      <div v-if="!vehicle.is_active" class="inactive-badge">
                        <span class="badge badge-sm badge-secondary">Inactive</span>
                      </div>
                    </div>
                  </td>
                  <td>
                    <div class="driver-assignment">
                      <div v-if="vehicle.current_driver" class="assigned-driver">
                        <div class="driver-name">{{ vehicle.current_driver.full_name }}</div>
                        <div class="driver-phone">{{ vehicle.current_driver.phone }}</div>
                      </div>
                      <div v-else class="no-driver">
                        <span class="text-gray-500">Not assigned</span>
                      </div>
                    </div>
                  </td>
                  <td>
                    <div class="usage-info">
                      <div class="trips-info">{{ getVehicleTrips(vehicle) }} trips</div>
                    
                    </div>
                  </td>
                  <td>
                    <div class="action-dropdown">
                      <button
                        @click="toggleDropdown(vehicle.id)"
                        class="btn btn-secondary btn-sm"
                        style="min-width: auto; padding: 0.5rem;"
                      >
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                          <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                        </svg>
                      </button>
                      <div v-show="activeDropdown === vehicle.id" class="dropdown-menu">
                        <button @click="openViewModal(vehicle)" class="dropdown-item">
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                          </svg>
                          View Details
                        </button>
                        <button @click="openEditModal(vehicle)" class="dropdown-item">
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                          </svg>
                          Edit Vehicle
                        </button>
                        <div class="dropdown-divider"></div>
                        <button
                          v-if="!vehicle.current_driver && vehicle.status === 'available'"
                          @click="openAssignDriverModal(vehicle)"
                          class="dropdown-item dropdown-item-success"
                        >
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                          </svg>
                          Assign Driver
                        </button>
                        <button
                          v-if="vehicle.current_driver"
                          @click="openUnassignDriverModal(vehicle)"
                          class="dropdown-item dropdown-item-warning"
                        >
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
          </div>
          
          <div v-if="filteredVehicles.length === 0" class="empty-state">
            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            <h3>No vehicles found</h3>
            <p>
              {{ hasActiveFilters ? 'Try adjusting your search or filters.' : 'Get started by adding a new vehicle.' }}
            </p>
          </div>
        </div>

        <!-- Create/Edit Vehicle Modal -->
        <div v-if="showVehicleModal" class="modal-overlay">
          <div class="modal modal-lg">
            <div class="modal-header">
              <h2 class="modal-title">
                <svg class="modal-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                {{ isEditing ? 'Edit Vehicle' : 'Add New Vehicle' }}
              </h2>
              <button @click="closeVehicleModal" class="modal-close">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <form @submit.prevent="saveVehicle" id="vehicleForm">
              <div class="modal-body">
                <div class="form-grid">
                  <!-- Basic Information -->
                  <div class="form-section-header">
                    <h3 class="form-section-title">Basic Information</h3>
                  </div>

                  <div class="form-group">
                    <label>Vehicle Type *</label>
                    <SearchableSelect
                      v-model="vehicleForm.vehicle_type"
                      :options="vehicleTypeOptions"
                      placeholder="Select vehicle type..."
                      required
                    />
                  </div>

                  <div class="form-group">
                    <label>Registration Number *</label>
                    <input
                      type="text"
                      v-model="vehicleForm.registration_number"
                      placeholder="GR-1234-20"
                      required
                    />
                  </div>

                  <div class="form-group">
                    <label>Vehicle Color *</label>
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
                      max="2030"
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
                    />
                  </div>

                  <div class="form-group">
                    <label>Registration Expiry Date</label>
                    <input
                      type="date"
                      v-model="vehicleForm.registration_expiry"
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
                    />
                  </div>

                  <div class="form-group">
                    <label>Next Service Date</label>
                    <input
                      type="date"
                      v-model="vehicleForm.next_service_date"
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
                <button 
                  type="submit" 
                  form="vehicleForm" 
                  :disabled="isSaving" 
                  class="btn btn-primary"
                >
                  <div v-if="isSaving" class="spinner spinner-sm"></div>
                  {{ isEditing ? 'Update Vehicle' : 'Add Vehicle' }}
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- View Vehicle Modal -->
        <div v-if="showViewModal && currentVehicle" class="modal-overlay">
          <div class="modal modal-xl">
            <div class="modal-header">
              <h2 class="modal-title">
                <svg class="modal-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                Vehicle Details - {{ currentVehicle.registration_number }}
              </h2>
              <button @click="closeViewModal" class="modal-close">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <div class="modal-body">
              <div class="vehicle-view">
                <!-- Vehicle Profile Card -->
                <div class="vehicle-profile-card">
                  <div class="vehicle-header">
                    <div class="vehicle-info-details">
                      <h3 class="vehicle-name-large">{{ currentVehicle.registration_number }}</h3>
                      <div class="vehicle-meta">
                        <span class="meta-item">{{ currentVehicle.vehicle_id }}</span>
                        <span class="meta-item">{{ capitalizeFirst(currentVehicle.vehicle_type) }}</span>
                        <span class="meta-item">{{ currentVehicle.vehicle_color }}</span>
                      </div>
                      <div v-if="currentVehicle.make && currentVehicle.model" class="vehicle-description">
                        {{ currentVehicle.make }} {{ currentVehicle.model }} {{ currentVehicle.year }}
                      </div>
                    </div>
                    <div class="status-indicators">
                      <span :class="'badge badge-lg ' + getStatusBadgeColor(currentVehicle.status)">
                        {{ currentVehicle.status_label }}
                      </span>
                      <span :class="'badge badge-lg ' + getVehicleTypeBadgeColor(currentVehicle.vehicle_type)">
                        {{ capitalizeFirst(currentVehicle.vehicle_type) }}
                      </span>
                    </div>
                  </div>
                </div>

                <!-- Vehicle Details Sections -->
                <div class="vehicle-details-sections">
                  <!-- Basic Information -->
                  <div class="details-section">
                    <h4 class="section-title">Basic Information</h4>
                    <div class="details-grid">
                      <div class="detail-item">
                        <label>Registration Number:</label>
                        <span>{{ currentVehicle.registration_number }}</span>
                      </div>
                      <div class="detail-item">
                        <label>Vehicle Type:</label>
                        <span>{{ capitalizeFirst(currentVehicle.vehicle_type) }}</span>
                      </div>
                      <div class="detail-item">
                        <label>Color:</label>
                        <span>{{ currentVehicle.vehicle_color }}</span>
                      </div>
                      <div class="detail-item">
                        <label>Make:</label>
                        <span>{{ currentVehicle.make || 'Not specified' }}</span>
                      </div>
                      <div class="detail-item">
                        <label>Model:</label>
                        <span>{{ currentVehicle.model || 'Not specified' }}</span>
                      </div>
                      <div class="detail-item">
                        <label>Year:</label>
                        <span>{{ currentVehicle.year || 'Not specified' }}</span>
                      </div>
                    </div>
                  </div>

                  <!-- Driver Assignment -->
                  <div class="details-section">
                    <h4 class="section-title">Driver Assignment</h4>
                    <div v-if="currentVehicle.current_driver" class="driver-assignment-info">
                      <div class="assigned-driver-card">
                        <div class="driver-details">
                          <h5>{{ currentVehicle.current_driver.full_name }}</h5>
                          <p>{{ currentVehicle.current_driver.phone }}</p>
                          <p v-if="currentVehicle.current_driver.email">{{ currentVehicle.current_driver.email }}</p>
                        </div>
                        <div class="driver-performance">
                          <span>Rating: {{ currentVehicle.current_driver.average_rating || 'N/A' }}</span>
                        </div>
                      </div>
                    </div>
                    <div v-else class="no-driver-assigned">
                      <p class="text-gray-500">No driver currently assigned</p>
                    </div>
                  </div>

                  <!-- Documents Status -->
                  <div class="details-section">
                    <h4 class="section-title">Documents & Compliance</h4>
                    <div class="documents-grid">
                      <div class="document-card">
                        <div class="document-header">
                          <h6>Insurance</h6>
                          <span :class="'badge ' + getDocumentStatusColor(currentVehicle.insurance_status)">
                            {{ getDocumentStatusLabel(currentVehicle.insurance_status) }}
                          </span>
                        </div>
                        <div class="document-details">
                          <p>Policy: {{ currentVehicle.insurance_policy || 'Not provided' }}</p>
                          <p>Expiry: {{ formatDate(currentVehicle.insurance_expiry) }}</p>
                        </div>
                      </div>
                      <div class="document-card">
                        <div class="document-header">
                          <h6>Registration</h6>
                          <span :class="'badge ' + getDocumentStatusColor(currentVehicle.registration_status)">
                            {{ getDocumentStatusLabel(currentVehicle.registration_status) }}
                          </span>
                        </div>
                        <div class="document-details">
                          <p>Expiry: {{ formatDate(currentVehicle.registration_expiry) }}</p>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Usage Statistics -->
                  <div class="details-section">
                    <h4 class="section-title">Usage Statistics</h4>
                    <div class="usage-grid">
                      <div class="usage-card">
                        <div class="usage-value">{{ getVehicleTrips(currentVehicle) }}</div>
                        <div class="usage-label">Total Trips</div>
                      </div>
                      <div class="usage-card">
                        <div class="usage-value">{{ currentVehicle.mileage || 'N/A' }}</div>
                        <div class="usage-label">Mileage (km)</div>
                      </div>
                      <div class="usage-card">
                        <div class="usage-value">{{ formatDate(currentVehicle.last_service_date) }}</div>
                        <div class="usage-label">Last Service</div>
                      </div>
                      <div class="usage-card">
                        <div class="usage-value">{{ formatDate(currentVehicle.next_service_date) }}</div>
                        <div class="usage-label">Next Service</div>
                      </div>
                    </div>
                  </div>

                  <!-- Additional Notes -->
                  <div v-if="currentVehicle.notes" class="details-section">
                    <h4 class="section-title">Additional Notes</h4>
                    <p>{{ currentVehicle.notes }}</p>
                  </div>
                </div>
              </div>
            </div>

            <div class="modal-actions">
              <button @click="closeViewModal" class="btn btn-secondary">
                Close
              </button>
              <button @click="editFromView" class="btn btn-primary">
                Edit Vehicle
              </button>
            </div>
          </div>
        </div>

        <!-- Assign Driver Modal -->
        <div v-if="showAssignDriverModal" class="modal-overlay">
          <div class="modal modal-lg">
            <div class="modal-header">
              <h3 class="modal-title">
                <svg class="modal-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Assign Driver to Vehicle
              </h3>
              <button @click="closeAssignDriverModal" class="modal-close">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
            
            <form @submit.prevent="assignDriver">
              <div class="modal-body">
                <div class="vehicle-assignment-info">
                  <div class="assignment-header">
                    <h4>Assigning driver to:</h4>
                    <div class="vehicle-card">
                      <div class="vehicle-info">
                        <span class="vehicle-reg">{{ currentVehicle?.registration_number }}</span>
                        <span class="vehicle-details">{{ currentVehicle?.make }} {{ currentVehicle?.model }}</span>
                        <span :class="'badge ' + getVehicleTypeBadgeColor(currentVehicle?.vehicle_type)">
                          {{ capitalizeFirst(currentVehicle?.vehicle_type) }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="form-grid">
                  <div class="form-group form-grid-full">
                    <label>Select Driver *</label>
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
                      placeholder="Optional notes about this assignment (e.g., shift schedule, special instructions)..."
                    ></textarea>
                  </div>
                </div>

                <!-- Driver Preview -->
                <div v-if="selectedDriverPreview" class="selected-driver-preview">
                  <h5>Selected Driver:</h5>
                  <div class="driver-preview-card">
                    <div class="driver-info">
                      <div class="driver-name">{{ selectedDriverPreview.full_name }}</div>
                      <div class="driver-contact">
                        <span>{{ selectedDriverPreview.phone }}</span>
                        <span v-if="selectedDriverPreview.email">{{ selectedDriverPreview.email }}</span>
                      </div>
                      <div class="driver-details">
                        <span v-if="selectedDriverPreview.license_number">License: {{ selectedDriverPreview.license_number }}</span>
                        <span v-if="selectedDriverPreview.years_experience">Experience: {{ selectedDriverPreview.years_experience }} years</span>
                      </div>
                    </div>
                    <div class="driver-stats">
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
        <div v-if="showUnassignDriverModal && currentVehicle" class="modal-overlay">
          <div class="modal modal-lg">
            <div class="modal-header modal-header-danger">
              <h3 class="modal-title">
                <svg class="modal-icon modal-icon-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
                Unassign Driver from Vehicle
              </h3>
              <button @click="closeUnassignDriverModal" class="modal-close">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

                  <!-- Vehicle Information -->
                  <div class="assignment-details">
                    <div class="detail-section">
                      <h5>Vehicle</h5>
                      <div class="vehicle-summary">
                        <div class="vehicle-reg">{{ currentVehicle.registration_number }}</div>
                        <div class="vehicle-info">{{ currentVehicle.make }} {{ currentVehicle.model }} {{ currentVehicle.year }}</div>
                        <span :class="'badge ' + getVehicleTypeBadgeColor(currentVehicle.vehicle_type)">
                          {{ capitalizeFirst(currentVehicle.vehicle_type) }}
                        </span>
                      </div>
                    </div>

                    <!-- Current Driver Information -->
                    <div v-if="currentVehicle.current_driver" class="detail-section">
                      <h5>Current Driver</h5>
                      <div class="driver-summary">
                        <div class="driver-name">{{ currentVehicle.current_driver.full_name }}</div>
                        <div class="driver-contact">
                          <span>{{ currentVehicle.current_driver.phone }}</span>
                          <span v-if="currentVehicle.current_driver.email">{{ currentVehicle.current_driver.email }}</span>
                        </div>
                        <div v-if="currentVehicle.current_driver.license_number" class="driver-license">
                          License: {{ currentVehicle.current_driver.license_number }}
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="warning-note">
                    <svg class="warning-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                    <div>
                      <p><strong>Note:</strong> After unassigning, this vehicle will become available for assignment to other drivers. The driver will no longer have access to this vehicle.</p>
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

      </div>
    </div>

    <!-- Toast Component -->
    <Toast />
  </MainLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, inject, watch } from 'vue'
import { useRouter } from 'vue-router'
import MainLayout from '../../layout/MainLayout.vue'
import Toast from '../../common/components/Toast.vue'
import SearchableSelect from '../../common/components/SearchableSelect.vue'

const router = useRouter()
const toast = inject('toast')

// Reactive data
const vehicles = ref([])
const availableDrivers = ref([])
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

// Options
const vehicleTypeOptions = [
  { value: 'ambulance', label: 'Ambulance' },
  { value: 'regular', label: 'Regular' }
]

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
const filteredVehicles = computed(() => {
  return vehicles.value.filter(vehicle => {
    const matchesSearch = !searchQuery.value || 
      vehicle.registration_number.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      (vehicle.make && vehicle.make.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
      (vehicle.model && vehicle.model.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
      (vehicle.vin_number && vehicle.vin_number.toLowerCase().includes(searchQuery.value.toLowerCase()))
    
    const matchesType = vehicleTypeFilter.value === 'all' || vehicle.vehicle_type === vehicleTypeFilter.value
    const matchesStatus = statusFilter.value === 'all' || vehicle.status === statusFilter.value
    const matchesAssigned = assignedFilter.value === 'all' ||
      (assignedFilter.value === 'true' && vehicle.current_driver) ||
      (assignedFilter.value === 'false' && !vehicle.current_driver)
    
    return matchesSearch && matchesType && matchesStatus && matchesAssigned
  })
})

const hasActiveFilters = computed(() => {
  return searchQuery.value || vehicleTypeFilter.value !== 'all' || 
         statusFilter.value !== 'all' || assignedFilter.value !== 'all'
})

// Convert available drivers to SearchableSelect format
const driverOptions = computed(() => {
  return availableDrivers.value.map(driver => ({
    value: driver.id,
    label: `${driver.full_name} (${driver.phone})`,
    searchText: `${driver.full_name} ${driver.phone} ${driver.email || ''} ${driver.license_number || ''}`.toLowerCase()
  }))
})

// Get selected driver details for preview
const selectedDriverPreview = computed(() => {
  if (!assignmentForm.value.driver_id) return null
  return availableDrivers.value.find(driver => driver.id == assignmentForm.value.driver_id)
})

// Watch for changes to selected driver to show preview
watch(() => assignmentForm.value.driver_id, (newDriverId) => {
  if (newDriverId) {
    console.log('Selected driver:', selectedDriverPreview.value)
  }
})

// Methods
const loadVehicles = async () => {
  loading.value = true
  try {
    const response = await fetch('/api/vehicles', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Content-Type': 'application/json'
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      vehicles.value = data.data || data
    } else {
      console.error('Failed to load vehicles')      
    }
  } catch (error) {
    console.error('Error loading vehicles:', error)
  }
  loading.value = false
}

const loadAvailableDrivers = async () => {
  loadingDrivers.value = true
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
    toast.showError('Failed to load available drivers')
  }
  loadingDrivers.value = false
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

const getDocumentStatusColor = (status) => {
  const colorMap = {
    'valid': 'badge-success',
    'expiring_soon': 'badge-warning',
    'expired': 'badge-danger',
    'unknown': 'badge-secondary'
  }
  return colorMap[status] || 'badge-secondary'
}

const getDocumentStatusLabel = (status) => {
  const labelMap = {
    'valid': 'Valid',
    'expiring_soon': 'Expiring Soon',
    'expired': 'Expired',
    'unknown': 'Unknown'
  }
  return labelMap[status] || 'Unknown'
}

const getVehicleTrips = (vehicle) => {
  // This would come from the vehicle's usage metrics
  return vehicle.total_trips || 0
}

const capitalizeFirst = (str) => {
  return str ? str.charAt(0).toUpperCase() + str.slice(1).replace('_', ' ') : ''
}

const formatDate = (dateString) => {
  if (!dateString) return 'Not set'
  return new Date(dateString).toLocaleDateString()
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
    const url = isEditing.value ? `/api/vehicles/${currentVehicle.value.id}` : '/api/vehicles'
    const method = isEditing.value ? 'PUT' : 'POST'
    
    const response = await fetch(url, {
      method,
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(vehicleForm.value)
    })
    
    if (response.ok) {
      await loadVehicles()
      closeVehicleModal()
      toast.showSuccess(isEditing.value ? 'Vehicle updated successfully!' : 'Vehicle added successfully!')
    } else {
      const errorData = await response.json()
      console.error('Failed to save vehicle:', errorData)
      
      if (errorData.errors) {
        const firstError = Object.values(errorData.errors)[0][0]
        toast.showError(firstError)
      } else {
        toast.showError(errorData.message || 'Failed to save vehicle. Please try again.')
      }
    }
  } catch (error) {
    console.error('Error saving vehicle:', error)
    toast.showError('An error occurred while saving the vehicle.')
  }
  
  isSaving.value = false
}

const assignDriver = async () => {
  isSaving.value = true
  
  try {
    const response = await fetch(`/api/vehicles/${currentVehicle.value.id}/assign-driver`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(assignmentForm.value)
    })
    
    if (response.ok) {
      await loadVehicles()
      closeAssignDriverModal()
      toast.showSuccess('Driver assigned successfully!')
    } else {
      const errorData = await response.json()
      toast.showError(errorData.message || 'Failed to assign driver. Please try again.')
    }
  } catch (error) {
    console.error('Error assigning driver:', error)
    toast.showError('An error occurred while assigning driver.')
  }
  
  isSaving.value = false
}

const unassignDriver = async () => {
  isUnassigning.value = true
  
  try {
    const response = await fetch(`/api/vehicles/${currentVehicle.value.id}/unassign-driver`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Content-Type': 'application/json'
      }
    })
    
    if (response.ok) {
      await loadVehicles()
      closeUnassignDriverModal()
      toast.showSuccess('Driver unassigned successfully!')
    } else {
      const errorData = await response.json()
      toast.showError(errorData.message || 'Failed to unassign driver.')
    }
  } catch (error) {
    console.error('Error unassigning driver:', error)
    toast.showError('An error occurred while unassigning driver.')
  }
  
  isUnassigning.value = false
}

const exportVehicles = async () => {
  try {
    const params = new URLSearchParams()
    
    if (vehicleTypeFilter.value !== 'all') {
      params.append('vehicle_type', vehicleTypeFilter.value)
    }
    
    if (statusFilter.value !== 'all') {
      params.append('status', statusFilter.value)
    }
    
    if (searchQuery.value) {
      params.append('search', searchQuery.value)
    }
    
    const queryString = params.toString()
    const url = `/api/vehicles/export${queryString ? '?' + queryString : ''}`
    
    const response = await fetch(url, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
      }
    })
    
    if (response.ok) {
      const contentDisposition = response.headers.get('Content-Disposition')
      let filename = 'vehicles_export.csv'
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
      
      toast.showSuccess('Vehicles exported successfully!')
    } else {
      const errorData = await response.json()
      console.error('Failed to export vehicles:', errorData)
      toast.showError(errorData.message || 'Failed to export vehicles. Please try again.')
    }
  } catch (error) {
    console.error('Error exporting vehicles:', error)
    toast.showError('An error occurred while exporting vehicles.')
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
  loadVehicles()
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
/* Vehicle specific styles */
.vehicles-page {
  min-height: 100vh;
  background: #f8f9fa;
}

.vehicles-table-container {
  background: white;
  border-radius: 0.75rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  border: 1px solid #e5e7eb;
  overflow: visible;
}

.vehicles-table {
  width: 100%;
  border-collapse: collapse;
}

.vehicles-table thead {
  background: #f9fafb;
}

.vehicles-table th {
  padding: 0.75rem 1rem;
  text-align: left;
  font-size: 0.75rem;
  font-weight: 500;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  border-bottom: 1px solid #e5e7eb;
}

.vehicles-table tbody tr:hover {
  background: #f9fafb;
}

.vehicles-table td {
  padding: 1rem;
  white-space: nowrap;
  font-size: 0.875rem;
  border-bottom: 1px solid #e5e7eb;
}

/* Vehicle Info */
.vehicle-info {
  align-items: center;
}

.vehicle-details .vehicle-number {
  font-weight: 600;
  color: #1f2937;
  font-size: 1rem;
}

.vehicle-details .vehicle-model {
  color: #6b7280;
  font-size: 0.875rem;
  margin: 0.25rem 0;
}

.vehicle-details .vehicle-color {
  color: #6b7280;
  font-size: 0.8125rem;
}

.trips-info {
  font-weight: 500;
  color: #1f2937;
}

.mileage-info {
  color: #6b7280;
  font-size: 0.875rem;
}

/* Vehicle Profile Card */
.vehicle-profile-card {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 0.75rem;
  padding: 2rem;
  margin-bottom: 2rem;
  color: white;
}

.vehicle-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.vehicle-info-details .vehicle-name-large {
  font-size: 2rem;
  font-weight: 700;
  margin: 0 0 0.5rem 0;
}

.vehicle-meta {
  display: flex;
  gap: 1rem;
  margin-bottom: 0.75rem;
  font-size: 0.875rem;
  opacity: 0.9;
}

.meta-item {
  background: rgba(255, 255, 255, 0.2);
  padding: 0.25rem 0.75rem;
  border-radius: 1rem;
}

.vehicle-description {
  font-size: 1.125rem;
  opacity: 0.9;
}

.status-indicators {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

/* Details Sections */
.vehicle-details-sections .details-section {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 0.75rem;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
}

.section-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1f2937;
  margin: 0 0 1rem 0;
}

.details-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.detail-item label {
  font-weight: 500;
  color: #6b7280;
  font-size: 0.875rem;
}

.detail-item span {
  color: #1f2937;
}

/* Documents Grid */
.documents-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1rem;
}

.document-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 0.5rem;
  padding: 1rem;
}

.document-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.document-header h6 {
  font-weight: 600;
  color: #1f2937;
  margin: 0;
}

.document-details p {
  margin: 0.25rem 0;
  color: #6b7280;
  font-size: 0.875rem;
}

/* Usage Grid */
.usage-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 1rem;
}

.usage-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 0.5rem;
  padding: 1rem;
  text-align: center;
}

.usage-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1f2937;
  margin-bottom: 0.25rem;
}

.usage-label {
  font-size: 0.875rem;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

/* Driver Assignment Info */
.assigned-driver-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 0.5rem;
  padding: 1rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
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

.driver-performance {
  text-align: right;
  font-size: 0.875rem;
  color: #6b7280;
}

/* Assignment Modal Styles */
.vehicle-assignment-info {
  margin-bottom: 1.5rem;
}

.assignment-header h4 {
  margin: 0 0 1rem 0;
  color: #1f2937;
  font-size: 1.125rem;
  font-weight: 600;
}

.vehicle-card {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 0.75rem;
  padding: 1.5rem;
  color: white;
}

.vehicle-info .vehicle-reg {
  display: block;
  font-size: 1.25rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
}

.vehicle-info .vehicle-details {
  display: block;
  font-size: 1rem;
  opacity: 0.9;
  margin-bottom: 0.75rem;
}

/* Unassign Modal Styles */
.unassign-confirmation-content .confirmation-message {
  font-size: 1rem;
  color: #374151;
  margin-bottom: 1.5rem;
  line-height: 1.6;
}

.assignment-details {
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 0.75rem;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
}

.detail-section {
  margin-bottom: 1.5rem;
}

.detail-section:last-child {
  margin-bottom: 0;
}

.detail-section h5 {
  font-size: 0.875rem;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin: 0 0 0.75rem 0;
}

.vehicle-summary {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.vehicle-summary .vehicle-reg {
  font-size: 1.125rem;
  font-weight: 700;
  color: #1f2937;
}

.vehicle-summary .vehicle-info {
  color: #6b7280;
  font-size: 0.875rem;
}

.driver-summary {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.driver-summary .driver-name {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1f2937;
}

.driver-contact {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.driver-contact span {
  color: #6b7280;
  font-size: 0.875rem;
}

.driver-license {
  color: #6b7280;
  font-size: 0.8125rem;
}

.warning-note {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  background: #fef3c7;
  border: 1px solid #fbbf24;
  border-radius: 0.5rem;
  padding: 1rem;
}

.warning-icon {
  width: 1.25rem;
  height: 1.25rem;
  color: #d97706;
  flex-shrink: 0;
  margin-top: 0.125rem;
}

.warning-note p {
  margin: 0;
  color: #92400e;
  font-size: 0.875rem;
  line-height: 1.5;
}

/* Selected Driver Preview */
.selected-driver-preview {
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e5e7eb;
}

.selected-driver-preview h5 {
  margin: 0 0 1rem 0;
  color: #1f2937;
  font-size: 1rem;
  font-weight: 600;
}

.driver-preview-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 0.75rem;
  padding: 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.driver-info .driver-name {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 0.5rem;
}

.driver-contact {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  margin-bottom: 0.75rem;
}

.driver-contact span {
  color: #6b7280;
  font-size: 0.875rem;
}

.driver-details {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.driver-details span {
  color: #6b7280;
  font-size: 0.8125rem;
}

.driver-stats {
  display: flex;
  gap: 2rem;
  text-align: center;
}

.stat-item {
  display: flex;
  flex-direction: column;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1f2937;
  line-height: 1;
}

.stat-label {
  font-size: 0.75rem;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-top: 0.25rem;
}

/* Responsive Design */
@media (max-width: 768px) {
  .vehicle-header {
    flex-direction: column;
    text-align: center;
    gap: 1rem;
  }
  
  .vehicle-meta {
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .status-indicators {
    flex-direction: row;
    justify-content: center;
  }
  
  .details-grid {
    grid-template-columns: 1fr;
  }
  
  .documents-grid {
    grid-template-columns: 1fr;
  }
  
  .usage-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .driver-preview-card {
    flex-direction: column;
    gap: 1rem;
    align-items: flex-start;
  }

  .driver-stats {
    align-self: stretch;
    justify-content: space-around;
  }
}

@media (max-width: 640px) {
  .vehicles-table-container {
    overflow-x: auto;
  }
  
  .vehicles-table {
    min-width: 900px;
  }
  
  .usage-grid {
    grid-template-columns: 1fr;
  }

  .driver-stats {
    gap: 1rem;
  }
}
</style>