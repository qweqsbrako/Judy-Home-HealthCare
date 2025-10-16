<template>
  <MainLayout>
    <div class="transport-page">
      <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Page Header -->
        <div class="page-header">
          <div class="page-header-content">
            <h1>Transport Management</h1>
            <p>Manage patient transportation requests and assignments</p>
          </div>
          <div class="page-header-actions">
            <button
              @click="exportTransports"
              class="btn btn-secondary"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              Export
            </button>
            <button
              @click="openCreateModal"
              class="btn btn-primary"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
              </svg>
              New Transport Request
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
                placeholder="Search by patient name, destination, driver..."
                v-model="searchQuery"
                class="search-input"
              />
            </div>
            <div class="filters-group">
              <select v-model="statusFilter" class="filter-select">
                <option value="all">All Status</option>
                <option value="requested">Requested</option>
                <option value="assigned">Assigned</option>
                <option value="in_progress">In Progress</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
              </select>
              <select v-model="priorityFilter" class="filter-select">
                <option value="all">All Priority</option>
                <option value="emergency">Emergency</option>
                <option value="urgent">Urgent</option>
                <option value="routine">Routine</option>
              </select>
              <select v-model="typeFilter" class="filter-select">
                <option value="all">All Types</option>
                <option value="ambulance">Ambulance</option>
                <option value="regular">Regular Transport</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="loading-state">
          <div class="loading-spinner"></div>
          <p class="loading-text">Loading transport requests...</p>
        </div>

        <!-- Transport Requests Table -->
        <div v-else class="transports-table-container">
          <div class="overflow-x-auto">
            <table class="transports-table">
              <thead>
                <tr>
                  <th>Patient</th>
                  <th>Type</th>
                  <th>Priority</th>
                  <th>Pickup Location</th>
                  <th>Destination</th>
                  <th>Scheduled Time</th>
                  <th>Driver</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="transport in filteredTransports" :key="transport.id">
                  <td>
                    <div class="patient-info">
                      <div class="patient-avatar">
                        <img :src="getPatientAvatar(transport.patient)" :alt="transport.patient_name" />
                      </div>
                      <div class="patient-details">
                        <div class="patient-name">{{ transport.patient_name }}</div>
                        <div class="requested-by">{{ transport.requested_by_name }}</div>
                      </div>
                    </div>
                  </td>
                  <td>
                    <span :class="'badge ' + getTypeBadgeColor(transport.transport_type)">
                      {{ capitalizeFirst(transport.transport_type) }}
                    </span>
                  </td>
                  <td>
                    <span :class="'badge ' + getPriorityBadgeColor(transport.priority)">
                      {{ capitalizeFirst(transport.priority) }}
                    </span>
                  </td>
                  <td>
                    <div class="location-info">
                      <div class="location-name">{{ transport.pickup_location }}</div>
                      <div class="location-address">{{ transport.pickup_address }}</div>
                    </div>
                  </td>
                  <td>
                    <div class="location-info">
                      <div class="location-name">{{ transport.destination_location }}</div>
                      <div class="location-address">{{ transport.destination_address }}</div>
                    </div>
                  </td>
                  <td>
                    <div class="schedule-info">
                      <div class="schedule-date">{{ formatDate(transport.scheduled_time) }}</div>
                      <div class="schedule-time">{{ formatTime(transport.scheduled_time) }}</div>
                    </div>
                  </td>
                  <td>
                    <div v-if="transport.driver" class="driver-info">
                      <div class="driver-name">{{ transport.driver.first_name }} {{ transport.driver.last_name }}</div>
                      <div class="driver-vehicle">{{ transport.driver.vehicle_number }}</div>
                    </div>
                    <div v-else class="no-driver">
                      <span class="text-gray-500">Not assigned</span>
                    </div>
                  </td>
                  <td>
                    <span :class="'badge ' + getStatusBadgeColor(transport.status)">
                      {{ capitalizeFirst(transport.status) }}
                    </span>
                  </td>
                  <td>
                    <div class="action-dropdown">
                      <button
                        @click="toggleDropdown(transport.id)"
                        class="btn btn-secondary btn-sm"
                        style="min-width: auto; padding: 0.5rem;"
                      >
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                          <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                        </svg>
                      </button>
                      <div v-show="activeDropdown === transport.id" class="dropdown-menu">
                        <button @click="openViewModal(transport)" class="dropdown-item">
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                          </svg>
                          View Details
                        </button>
                        <button @click="openEditModal(transport)" class="dropdown-item">
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                          </svg>
                          Edit Request
                        </button>
                        <button
                          v-if="transport.status === 'requested'"
                          @click="openAssignDriverModal(transport)"
                          class="dropdown-item dropdown-item-success"
                        >
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                          </svg>
                          Assign Driver
                        </button>
                        <button
                          v-if="transport.status === 'assigned'"
                          @click="startTransport(transport)"
                          class="dropdown-item dropdown-item-success"
                        >
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293H15" />
                          </svg>
                          Start Transport
                        </button>
                        <button
                          v-if="transport.status === 'in_progress'"
                          @click="completeTransport(transport)"
                          class="dropdown-item dropdown-item-success"
                        >
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                          </svg>
                          Complete Transport
                        </button>
                        <div class="dropdown-divider"></div>
                        <button
                          v-if="['requested', 'assigned'].includes(transport.status)"
                          @click="cancelTransport(transport)"
                          class="dropdown-item dropdown-item-danger"
                        >
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                          </svg>
                          Cancel Request
                        </button>
                      </div>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          
          <div v-if="filteredTransports.length === 0" class="empty-state">
            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2v0a2 2 0 01-2-2v-2a2 2 0 00-2-2H8z" />
            </svg>
            <h3>No transport requests found</h3>
            <p>
              {{ (searchQuery || statusFilter !== 'all' || priorityFilter !== 'all' || typeFilter !== 'all') 
                ? 'Try adjusting your search or filters.' 
                : 'Get started by creating a new transport request.' }}
            </p>
          </div>
        </div>

        <!-- Create/Edit Transport Modal -->
        <div v-if="showTransportModal" class="modal-overlay">
          <div class="modal modal-lg">
            <div class="modal-header">
              <h2 class="modal-title">
                <svg class="modal-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2v0a2 2 0 01-2-2v-2a2 2 0 00-2-2H8z" />
                </svg>
                {{ isEditing ? 'Edit Transport Request' : 'New Transport Request' }}
              </h2>
              <button @click="closeTransportModal" class="modal-close">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <form @submit.prevent="saveTransport" id="transportForm">
              <div class="modal-body">
                <div class="form-grid">
                  <!-- Request Information -->
                  <div class="form-section-header">
                    <h3 class="form-section-title">Request Information</h3>
                  </div>

                  <div class="form-group">
                    <label>Patient *</label>
                    <SearchableSelect
                      v-model="transportForm.patient_id"
                      :options="patientOptions"
                      placeholder="Select patient..."
                      required
                    />
                  </div>

                  <div class="form-group">
                    <label>Transport Type *</label>
                    <SearchableSelect
                      v-model="transportForm.transport_type"
                      :options="transportTypeOptions"
                      placeholder="Select transport type..."
                      required
                    />
                  </div>

                  <div class="form-group">
                    <label>Priority Level *</label>
                    <SearchableSelect
                      v-model="transportForm.priority"
                      :options="priorityOptions"
                      placeholder="Select priority..."
                      required
                    />
                  </div>

                  <div class="form-group">
                    <label>Scheduled Date & Time *</label>
                    <input
                      type="datetime-local"
                      v-model="transportForm.scheduled_time"
                      required
                    />
                  </div>

                  <!-- Location Information -->
                  <div class="form-section-header">
                    <h3 class="form-section-title">Location Information</h3>
                  </div>

                  <div class="form-group form-grid-full">
                    <label>Pickup Location Name *</label>
                    <input
                      type="text"
                      v-model="transportForm.pickup_location"
                      placeholder="e.g., Patient's Home, Hospital Ward 3A"
                      required
                    />
                  </div>

                  <div class="form-group form-grid-full">
                    <label>Pickup Address *</label>
                    <textarea
                      v-model="transportForm.pickup_address"
                      rows="2"
                      placeholder="Complete pickup address..."
                      required
                    ></textarea>
                  </div>

                  <div class="form-group form-grid-full">
                    <label>Destination Location Name *</label>
                    <input
                      type="text"
                      v-model="transportForm.destination_location"
                      placeholder="e.g., Korle-Bu Teaching Hospital, Ridge Hospital"
                      required
                    />
                  </div>

                  <div class="form-group form-grid-full">
                    <label>Destination Address *</label>
                    <textarea
                      v-model="transportForm.destination_address"
                      rows="2"
                      placeholder="Complete destination address..."
                      required
                    ></textarea>
                  </div>

                  <!-- Additional Information -->
                  <div class="form-section-header">
                    <h3 class="form-section-title">Additional Information</h3>
                  </div>

                  <div class="form-group form-grid-full">
                    <label>Special Requirements</label>
                    <textarea
                      v-model="transportForm.special_requirements"
                      rows="3"
                      placeholder="Wheelchair access, oxygen support, stretcher needed, etc..."
                    ></textarea>
                  </div>

                  <div class="form-group form-grid-full">
                    <label>Reason for Transport *</label>
                    <textarea
                      v-model="transportForm.reason"
                      rows="3"
                      placeholder="Medical appointment, emergency treatment, routine check-up..."
                      required
                    ></textarea>
                  </div>

                  <div class="form-group form-grid-full">
                    <label>Contact Person</label>
                    <input
                      type="text"
                      v-model="transportForm.contact_person"
                      placeholder="Name and phone of person to contact"
                    />
                  </div>
                </div>
              </div>

              <div class="modal-actions">
                <button type="button" @click="closeTransportModal" class="btn btn-secondary">
                  Cancel
                </button>
                <button 
                  type="submit" 
                  form="transportForm" 
                  :disabled="isSaving" 
                  class="btn btn-primary"
                >
                  <div v-if="isSaving" class="spinner spinner-sm"></div>
                  {{ isEditing ? 'Update Request' : 'Create Request' }}
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- View Transport Modal -->
        <div v-if="showViewModal && currentTransport" class="modal-overlay">
          <div class="modal modal-lg">
            <div class="modal-header">
              <h2 class="modal-title">
                <svg class="modal-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2v0a2 2 0 01-2-2v-2a2 2 0 00-2-2H8z" />
                </svg>
                Transport Request Details
              </h2>
              <button @click="closeViewModal" class="modal-close">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <div class="modal-body">
              <div class="transport-view">
                <!-- Transport Header -->
                <div class="transport-header">
                  <div class="transport-status">
                    <span :class="'badge badge-lg ' + getStatusBadgeColor(currentTransport.status)">
                      {{ capitalizeFirst(currentTransport.status) }}
                    </span>
                    <span :class="'badge badge-lg ' + getPriorityBadgeColor(currentTransport.priority)">
                      {{ capitalizeFirst(currentTransport.priority) }} Priority
                    </span>
                  </div>
                  <div class="transport-info">
                    <h3>{{ currentTransport.patient_name }}</h3>
                    <p>Requested by: {{ currentTransport.requested_by_name }}</p>
                    <p>{{ formatDateTime(currentTransport.created_at) }}</p>
                  </div>
                </div>

                <!-- Transport Details -->
                <div class="transport-details">
                  <div class="detail-section">
                    <h4>Transport Information</h4>
                    <div class="detail-grid">
                      <div class="detail-item">
                        <label>Type:</label>
                        <span :class="'badge ' + getTypeBadgeColor(currentTransport.transport_type)">
                          {{ capitalizeFirst(currentTransport.transport_type) }}
                        </span>
                      </div>
                      <div class="detail-item">
                        <label>Scheduled Time:</label>
                        <span>{{ formatDateTime(currentTransport.scheduled_time) }}</span>
                      </div>
                      <div class="detail-item">
                        <label>Reason:</label>
                        <span>{{ currentTransport.reason }}</span>
                      </div>
                    </div>
                  </div>

                  <div class="detail-section">
                    <h4>Locations</h4>
                    <div class="location-details">
                      <div class="location-item">
                        <div class="location-header">
                          <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                          </svg>
                          <span>Pickup Location</span>
                        </div>
                        <div class="location-content">
                          <strong>{{ currentTransport.pickup_location }}</strong>
                          <p>{{ currentTransport.pickup_address }}</p>
                        </div>
                      </div>
                      <div class="location-item">
                        <div class="location-header">
                          <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                          </svg>
                          <span>Destination</span>
                        </div>
                        <div class="location-content">
                          <strong>{{ currentTransport.destination_location }}</strong>
                          <p>{{ currentTransport.destination_address }}</p>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div v-if="currentTransport.driver" class="detail-section">
                    <h4>Assigned Driver</h4>
                    <div class="driver-details">
                      <div class="driver-info">
                        <strong>{{ currentTransport.driver.first_name }} {{ currentTransport.driver.last_name }}</strong>
                        <p>License: {{ currentTransport.driver.license_number }}</p>
                        <p>Vehicle: {{ currentTransport.driver.vehicle_number }}</p>
                        <p>Phone: {{ currentTransport.driver.phone }}</p>
                      </div>
                    </div>
                  </div>

                  <div v-if="currentTransport.special_requirements" class="detail-section">
                    <h4>Special Requirements</h4>
                    <p>{{ currentTransport.special_requirements }}</p>
                  </div>
                </div>
              </div>
            </div>

            <div class="modal-actions">
              <button @click="closeViewModal" class="btn btn-secondary">
                Close
              </button>
              <button @click="editFromView" class="btn btn-primary">
                Edit Request
              </button>
            </div>
          </div>
        </div>

        <!-- Assign Driver Modal -->
        <div v-if="showAssignDriverModal && currentTransport" class="modal-overlay">
          <div class="modal modal-lg">
            <div class="modal-header">
              <h3 class="modal-title">
                <svg class="modal-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                Assign Driver to Transport
              </h3>
              <button @click="closeAssignDriverModal" class="modal-close">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
            
            <form @submit.prevent="assignDriver">
              <div class="modal-body">
                <div class="transport-assignment-info">
                  <div class="assignment-header">
                    <h4>Assigning driver to transport:</h4>
                    <div class="transport-card">
                      <div class="transport-info">
                        <span class="patient-name">{{ currentTransport?.patient_name }}</span>
                        <span class="transport-details">{{ currentTransport?.pickup_location }} → {{ currentTransport?.destination_location }}</span>
                        <div class="transport-meta">
                          <span :class="'badge ' + getPriorityBadgeColor(currentTransport?.priority)">
                            {{ capitalizeFirst(currentTransport?.priority) }} Priority
                          </span>
                          <span :class="'badge ' + getTypeBadgeColor(currentTransport?.transport_type)">
                            {{ capitalizeFirst(currentTransport?.transport_type) }}
                          </span>
                        </div>
                        <span class="scheduled-time">{{ formatDateTime(currentTransport?.scheduled_time) }}</span>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="form-grid">
                  <div class="form-group form-grid-full">
                    <label>Select Available Driver *</label>
                    <SearchableSelect
                      v-model="selectedDriverId"
                      :options="availableDriverOptions"
                      placeholder="Search for available drivers..."
                      required
                    />
                    <p class="form-help">
                      {{ availableDriverOptions.length }} available drivers found
                    </p>
                  </div>

                  <div class="form-group form-grid-full">
                    <label>Assignment Notes</label>
                    <textarea
                      v-model="assignmentNotes"
                      rows="3"
                      placeholder="Optional notes about this assignment (e.g., special instructions, patient requirements)..."
                    ></textarea>
                  </div>
                </div>

                <!-- Driver Preview -->
                <div v-if="selectedDriverPreview" class="selected-driver-preview">
                  <h5>Selected Driver:</h5>
                  <div class="driver-preview-card">
                    <div class="driver-avatar-preview">
                      <img :src="selectedDriverPreview.avatar_url" :alt="selectedDriverPreview.full_name" />
                    </div>
                    <div class="driver-info">
                      <div class="driver-name">{{ selectedDriverPreview.first_name }} {{ selectedDriverPreview.last_name }}</div>
                      <div class="driver-details">
                        <span>ID: {{ selectedDriverPreview.driver_id }}</span>
                        <span>Phone: {{ selectedDriverPreview.phone }}</span>
                      </div>
                      <div v-if="selectedDriverPreview.current_vehicle" class="driver-vehicle">
                        <span>Vehicle: {{ selectedDriverPreview.current_vehicle.registration_number }} ({{ selectedDriverPreview.current_vehicle.make }} {{ selectedDriverPreview.current_vehicle.model }})</span>
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
                  :disabled="isSaving || !selectedDriverId"
                  class="btn btn-primary"
                >
                  <div v-if="isSaving" class="spinner spinner-sm"></div>
                  Assign Driver
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
import { apiGet, apiPost, apiPut, apiDelete } from '@/utils/api'

const router = useRouter()
const toast = inject('toast')

// Reactive data
const transports = ref([])
const patients = ref([])
const drivers = ref([])
const loading = ref(true)
const searchQuery = ref('')
const statusFilter = ref('all')
const priorityFilter = ref('all')
const typeFilter = ref('all')

// Modal states
const showTransportModal = ref(false)
const showViewModal = ref(false)
const showAssignDriverModal = ref(false)
const isEditing = ref(false)
const currentTransport = ref(null)
const isSaving = ref(false)
const selectedDriverId = ref('')
const assignmentNotes = ref('')

// Dropdown state
const activeDropdown = ref(null)

// Options for select fields
const transportTypeOptions = [
  { value: 'ambulance', label: 'Ambulance' },
  { value: 'regular', label: 'Regular Transport' }
]

const priorityOptions = [
  { value: 'emergency', label: 'Emergency' },
  { value: 'urgent', label: 'Urgent' },
  { value: 'routine', label: 'Routine' }
]

// Form data
const transportForm = ref({
  patient_id: '',
  transport_type: 'regular',
  priority: 'routine',
  scheduled_time: '',
  pickup_location: '',
  pickup_address: '',
  destination_location: '',
  destination_address: '',
  special_requirements: '',
  reason: '',
  contact_person: ''
})

// Computed properties
const filteredTransports = computed(() => {
  return transports.value.filter(transport => {
    const matchesSearch = !searchQuery.value || 
      transport.patient_name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      transport.pickup_location.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      transport.destination_location.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      transport.driver?.first_name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      transport.driver?.last_name.toLowerCase().includes(searchQuery.value.toLowerCase())
    
    const matchesStatus = statusFilter.value === 'all' || transport.status === statusFilter.value
    const matchesPriority = priorityFilter.value === 'all' || transport.priority === priorityFilter.value
    const matchesType = typeFilter.value === 'all' || transport.transport_type === typeFilter.value
    
    return matchesSearch && matchesStatus && matchesPriority && matchesType
  })
})

const patientOptions = computed(() => {
  return patients.value.map(patient => ({
    value: patient.id,
    label: `${patient.first_name} ${patient.last_name} (${patient.ghana_card_number})`
  }))
})

const availableDriverOptions = computed(() => {
  return drivers.value
    .filter(driver => !driver.is_suspended && driver.is_active)
    .map(driver => ({
      value: driver.id,
      label: `${driver.first_name} ${driver.last_name} - ${driver.driver_id}${driver.current_vehicle ? ` (${driver.current_vehicle})` : ''}`
    }))
})

// Get selected driver details for preview
const selectedDriverPreview = computed(() => {
  if (!selectedDriverId.value) return null
  return drivers.value.find(driver => driver.id == selectedDriverId.value)
})

// Methods
const loadTransports = async () => {
  loading.value = true
  try {
    const data = await apiGet('/api/transports')
    if (data) {
      transports.value = data.data || data
    }
  } catch (error) {
    console.error('Error loading transports:', error)
    toast.showError('Failed to load transport requests.')
  }
  loading.value = false
}

const loadPatients = async () => {
  try {
    const data = await apiGet('/api/users?role=patient')
    if (data) {
      patients.value = data.data || data
    }
  } catch (error) {
    console.error('Error loading patients:', error)
  }
}

const loadDrivers = async () => {
  try {
    const data = await apiGet('/api/drivers')
    if (data) {
      drivers.value = data.data || data
    }
  } catch (error) {
    console.error('Error loading drivers:', error)
  }
}

const getPatientAvatar = (patient) => {
  if (!patient) return 'https://ui-avatars.com/api/?name=Unknown&color=667eea&background=f8f9fa&size=200&font-size=0.6'
  
  return patient.avatar 
    ? `/storage/${patient.avatar}`
    : `https://ui-avatars.com/api/?name=${encodeURIComponent(patient.first_name + ' ' + patient.last_name)}&color=667eea&background=f8f9fa&size=200&font-size=0.6`
}

const getTypeBadgeColor = (type) => {
  const colorMap = {
    'ambulance': 'badge-danger',
    'regular': 'badge-primary'
  }
  return colorMap[type] || 'badge-secondary'
}

const getPriorityBadgeColor = (priority) => {
  const colorMap = {
    'emergency': 'badge-danger',
    'urgent': 'badge-warning',
    'routine': 'badge-success'
  }
  return colorMap[priority] || 'badge-secondary'
}

const getStatusBadgeColor = (status) => {
  const colorMap = {
    'requested': 'badge-warning',
    'assigned': 'badge-info',
    'in_progress': 'badge-primary',
    'completed': 'badge-success',
    'cancelled': 'badge-danger'
  }
  return colorMap[status] || 'badge-secondary'
}

const capitalizeFirst = (str) => {
  return str ? str.charAt(0).toUpperCase() + str.slice(1).replace('_', ' ') : ''
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString()
}

const formatTime = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' })
}

const formatDateTime = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleString()
}

const toggleDropdown = (transportId) => {
  activeDropdown.value = activeDropdown.value === transportId ? null : transportId
}

const openCreateModal = () => {
  isEditing.value = false
  currentTransport.value = null
  resetForm()
  showTransportModal.value = true
}

const openEditModal = (transport) => {
  isEditing.value = true
  currentTransport.value = transport
  populateForm(transport)
  showTransportModal.value = true
  activeDropdown.value = null
}

const openViewModal = (transport) => {
  currentTransport.value = transport
  showViewModal.value = true
  activeDropdown.value = null
}

const openAssignDriverModal = (transport) => {
  currentTransport.value = transport
  selectedDriverId.value = ''
  assignmentNotes.value = ''
  showAssignDriverModal.value = true
  activeDropdown.value = null
}

const closeTransportModal = () => {
  showTransportModal.value = false
}

const closeViewModal = () => {
  showViewModal.value = false
}

const closeAssignDriverModal = () => {
  showAssignDriverModal.value = false
}

const editFromView = () => {
  closeViewModal()
  openEditModal(currentTransport.value)
}

const resetForm = () => {
  transportForm.value = {
    patient_id: '',
    transport_type: 'regular',
    priority: 'routine',
    scheduled_time: '',
    pickup_location: '',
    pickup_address: '',
    destination_location: '',
    destination_address: '',
    special_requirements: '',
    reason: '',
    contact_person: ''
  }
}

const populateForm = (transport) => {
  transportForm.value = {
    patient_id: transport.patient_id,
    transport_type: transport.transport_type,
    priority: transport.priority,
    scheduled_time: transport.scheduled_time ? new Date(transport.scheduled_time).toISOString().slice(0, 16) : '',
    pickup_location: transport.pickup_location,
    pickup_address: transport.pickup_address,
    destination_location: transport.destination_location,
    destination_address: transport.destination_address,
    special_requirements: transport.special_requirements || '',
    reason: transport.reason,
    contact_person: transport.contact_person || ''
  }
}

const saveTransport = async () => {
  isSaving.value = true
  
  try {
    let result
    if (isEditing.value) {
      result = await apiPut(`/api/transports/${currentTransport.value.id}`, transportForm.value)
    } else {
      result = await apiPost('/api/transports', transportForm.value)
    }
    
    if (result) {
      await loadTransports()
      closeTransportModal()
      toast.showSuccess(isEditing.value ? 'Transport request updated successfully!' : 'Transport request created successfully!')
    }
  } catch (error) {
    console.error('Error saving transport request:', error)
    
    // Extract error message from the error
    let errorMessage = 'Failed to save transport request. Please try again.'
    if (error.message && error.message.includes('{')) {
      try {
        const errorData = JSON.parse(error.message)
        if (errorData.errors) {
          const firstError = Object.values(errorData.errors)[0][0]
          errorMessage = firstError
        } else if (errorData.message) {
          errorMessage = errorData.message
        }
      } catch (parseError) {
        errorMessage = error.message
      }
    } else if (error.message) {
      errorMessage = error.message
    }
    
    toast.showError(errorMessage)
  }
  
  isSaving.value = false
}

const assignDriver = async () => {
  isSaving.value = true
  
  try {
    const result = await apiPost(`/api/transports/${currentTransport.value.id}/assign-driver`, {
      driver_id: selectedDriverId.value,
      notes: assignmentNotes.value
    })
    
    if (result) {
      await loadTransports()
      closeAssignDriverModal()
      toast.showSuccess('Driver assigned successfully!')
    }
  } catch (error) {
    console.error('Error assigning driver:', error)
    toast.showError(error.message || 'Failed to assign driver. Please try again.')
  }
  
  isSaving.value = false
}

const startTransport = async (transport) => {
  try {
    const result = await apiPost(`/api/transports/${transport.id}/start`)
    
    if (result) {
      await loadTransports()
      toast.showSuccess('Transport started successfully!')
    }
  } catch (error) {
    console.error('Error starting transport:', error)
    toast.showError('Failed to start transport.')
  }
  activeDropdown.value = null
}

const completeTransport = async (transport) => {
  try {
    const result = await apiPost(`/api/transports/${transport.id}/complete`)
    
    if (result) {
      await loadTransports()
      toast.showSuccess('Transport completed successfully!')
    }
  } catch (error) {
    console.error('Error completing transport:', error)
    toast.showError('Failed to complete transport.')
  }
  activeDropdown.value = null
}

const cancelTransport = async (transport) => {
  if (confirm(`Are you sure you want to cancel this transport request for ${transport.patient_name}?`)) {
    try {
      const result = await apiPost(`/api/transports/${transport.id}/cancel`)
      
      if (result) {
        await loadTransports()
        toast.showSuccess('Transport request cancelled successfully!')
      }
    } catch (error) {
      console.error('Error cancelling transport:', error)
      toast.showError('Failed to cancel transport request.')
    }
  }
  activeDropdown.value = null
}

const exportTransports = async () => {
  try {
    const params = new URLSearchParams()
    
    if (statusFilter.value !== 'all') {
      params.append('status', statusFilter.value)
    }
    
    if (priorityFilter.value !== 'all') {
      params.append('priority', priorityFilter.value)
    }
    
    if (typeFilter.value !== 'all') {
      params.append('type', typeFilter.value)
    }
    
    if (searchQuery.value) {
      params.append('search', searchQuery.value)
    }
    
    const queryString = params.toString()
    const url = `/api/transports/export${queryString ? '?' + queryString : ''}`
    
    // For export, we need to use fetch directly to handle blob response
    const response = await fetch(url, {
      method: 'GET',
      credentials: 'include'
    })
    
    if (response.ok) {
      const contentDisposition = response.headers.get('Content-Disposition')
      let filename = 'transports_export.csv'
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
      
      toast.showSuccess('Transport requests exported successfully!')
    } else {
      throw new Error('Export failed')
    }
  } catch (error) {
    console.error('Error exporting transports:', error)
    toast.showError('Failed to export transport requests. Please try again.')
  }
}

// Close dropdown when clicking outside
const handleClickOutside = (event) => {
  if (!event.target.closest('.action-dropdown')) {
    activeDropdown.value = null
  }
}

// Lifecycle
onMounted(async () => {
  await Promise.all([
    loadTransports(),
    loadPatients(),
    loadDrivers()
  ])
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})

// Watch for filter changes and reload data
watch([statusFilter, priorityFilter, typeFilter, searchQuery], () => {
  if (searchQuery.value) {
    setTimeout(() => {
      loadTransports()
    }, 500)
  } else {
    loadTransports()
  }
}, { deep: true })
</script>

<style scoped>
/* Transport specific styles */
.transport-page {
  min-height: 100vh;
  background: #f8f9fa;
}

.transports-table-container {
  background: white;
  border-radius: 0.75rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  border: 1px solid #e5e7eb;
  overflow: visible;
}

.transports-table {
  width: 100%;
  border-collapse: collapse;
}

.transports-table thead {
  background: #f9fafb;
}

.transports-table th {
  padding: 0.75rem 1rem;
  text-align: left;
  font-size: 0.75rem;
  font-weight: 500;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  border-bottom: 1px solid #e5e7eb;
}

.transports-table tbody tr:hover {
  background: #f9fafb;
}

.transports-table td {
  padding: 1rem;
  white-space: nowrap;
  font-size: 0.875rem;
  border-bottom: 1px solid #e5e7eb;
}

/* Location Info */
.location-info .location-name {
  font-weight: 500;
  color: #1f2937;
}

.location-info .location-address {
  color: #6b7280;
  font-size: 0.875rem;
  max-width: 200px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Schedule Info */
.schedule-info .schedule-date {
  font-weight: 500;
  color: #1f2937;
}

.schedule-info .schedule-time {
  color: #6b7280;
  font-size: 0.875rem;
}

/* Driver Info */
.driver-info .driver-name {
  font-weight: 500;
  color: #1f2937;
}

.driver-info .driver-vehicle {
  color: #6b7280;
  font-size: 0.875rem;
}

/* Transport View Styles */
.transport-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 0.75rem;
  padding: 2rem;
  margin-bottom: 2rem;
  color: white;
}

.transport-status {
  display: flex;
  gap: 1rem;
  margin-bottom: 1rem;
}

.transport-info h3 {
  font-size: 1.75rem;
  font-weight: 700;
  margin: 0 0 0.5rem 0;
}

.transport-info p {
  margin: 0.25rem 0;
  opacity: 0.9;
}

.transport-details {
  space-y: 1.5rem;
}

.detail-section {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 0.75rem;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
}

.detail-section h4 {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1f2937;
  margin: 0 0 1rem 0;
}

.detail-grid {
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

/* Location Details */
.location-details {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
}

.location-item {
  background: #f9fafb;
  padding: 1rem;
  border-radius: 0.5rem;
}

.location-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
  font-weight: 600;
  color: #1f2937;
}

.patient-info {
  display: flex;
  align-items: center;
}

.patient-avatar {
  flex-shrink: 0;
  width: 2.5rem;
  height: 2.5rem;
}

.patient-avatar img {
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 50%;
  object-fit: cover;
}

.patient-details {
  margin-left: 1rem;
}

.patient-name {
  font-weight: 500;
  color: #1f2937;
}

.requested-by {
  color: #6b7280;
  font-size: 0.875rem;
}

.location-content strong {
  color: #1f2937;
  display: block;
  margin-bottom: 0.5rem;
}

.location-content p {
  margin: 0;
  color: #6b7280;
  line-height: 1.4;
}

/* Driver Details */
.driver-details {
  background: #f9fafb;
  padding: 1rem;
  border-radius: 0.5rem;
}

.driver-info strong {
  color: #1f2937;
  display: block;
  margin-bottom: 0.25rem;
}

.driver-info p {
  margin: 0.25rem 0;
  color: #6b7280;
  font-size: 0.875rem;
}

/* Responsive Design */
@media (max-width: 768px) {
  .transports-table {
    font-size: 0.8125rem;
  }
  
  .transports-table th,
  .transports-table td {
    padding: 0.75rem 0.5rem;
  }
  
  .transport-header {
    padding: 1.5rem;
  }
  
  .transport-status {
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .detail-grid {
    grid-template-columns: 1fr;
  }
  
  .location-details {
    grid-template-columns: 1fr;
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
  .transports-table-container {
    overflow-x: auto;
  }
  
  .transports-table {
    min-width: 1000px;
  }

  .driver-stats {
    gap: 1rem;
  }
}

/* Assignment Modal Styles (matching drivers.vue) */
.transport-assignment-info {
  margin-bottom: 1.5rem;
}

.assignment-header h4 {
  margin: 0 0 1rem 0;
  color: #1f2937;
  font-size: 1.125rem;
  font-weight: 600;
}

.transport-card {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 0.75rem;
  padding: 1.5rem;
  color: white;
}

.transport-info .patient-name {
  display: block;
  font-size: 1.25rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
}

.transport-info .transport-details {
  display: block;
  font-size: 1rem;
  opacity: 0.9;
  margin-bottom: 0.75rem;
}

.transport-meta {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
  flex-wrap: wrap;
}

.transport-info .scheduled-time {
  display: block;
  font-size: 0.875rem;
  opacity: 0.8;
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
  gap: 1rem;
  align-items: center;
}

.driver-avatar-preview {
  flex-shrink: 0;
}

.driver-avatar-preview img {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #e5e7eb;
}

.driver-info {
  flex: 1;
}

.driver-info .driver-name {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 0.5rem;
}

.driver-details {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  margin-bottom: 0.5rem;
}

.driver-details span {
  color: #6b7280;
  font-size: 0.875rem;
}

.driver-vehicle span {
  color: #6b7280;
  font-size: 0.875rem;
  font-style: italic;
}

.driver-stats {
  display: flex;
  gap: 2rem;
  align-items: center;
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
</style>