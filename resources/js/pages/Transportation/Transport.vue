<template>
  <MainLayout>
    <div class="transport-page">
      <!-- Page Header -->
      <div class="page-header">
        <div class="page-header-content">
          <h1>Transport Management</h1>
          <p>Manage patient transportation requests and assignments</p>
        </div>
        <div class="page-header-actions">
          <button @click="exportTransports" class="btn-modern btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Export
          </button>
          <button @click="openCreateModal" class="btn-modern btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            New Transport Request
          </button>
        </div>
      </div>

      <!-- Stats Grid -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon blue">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/>
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Total Requests</div>
            <div class="stat-value">{{ statistics.total_requests || 0 }}</div>
            <div class="stat-change positive">{{ statistics.today_requests || 0 }} today</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon yellow">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Pending Requests</div>
            <div class="stat-value">{{ statistics.pending_requests || 0 }}</div>
            <div class="stat-change neutral">Awaiting assignment</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon purple">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Active Transports</div>
            <div class="stat-value">{{ statistics.active_transports || 0 }}</div>
            <div class="stat-change positive">In progress</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon green">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Completed Today</div>
            <div class="stat-value">{{ statistics.completed_today || 0 }}</div>
            <div class="stat-change positive">Successfully delivered</div>
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
            placeholder="Search by patient, location, driver..."
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

      <!-- Loading State -->
      <div v-if="loading" class="loading-state">
        <div class="loading-spinner"></div>
        <p class="loading-text">Loading transport requests...</p>
      </div>

      <!-- Transport Table -->
      <div v-else-if="!loading" class="transport-table-container">
        <div v-if="transports.data && transports.data.length > 0" class="table-wrapper">
          <table class="modern-table">
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
              <tr v-for="transport in transports.data" :key="transport.id">
                <td>
                  <div class="user-cell">
                    <img :src="getPatientAvatar(transport.patient)" class="user-avatar-table" />
                    <div class="user-details-table">
                      <div class="user-name-table">{{ transport.patient_name }}</div>
                      <div class="user-id-table">By: {{ transport.requested_by_name }}</div>
                    </div>
                  </div>
                </td>
                
                <td>
                  <span class="modern-badge" :class="getTypeBadgeColor(transport.transport_type)">
                    {{ capitalizeFirst(transport.transport_type) }}
                  </span>
                </td>
                
                <td>
                  <span class="modern-badge" :class="getPriorityBadgeColor(transport.priority)">
                    {{ capitalizeFirst(transport.priority) }}
                  </span>
                </td>
                
                <td>
                  <div class="contact-cell">
                    <div class="contact-primary">{{ transport.pickup_location }}</div>
                    <div class="contact-secondary">{{ truncateText(transport.pickup_address, 30) }}</div>
                  </div>
                </td>
                
                <td>
                  <div class="contact-cell">
                    <div class="contact-primary">{{ transport.destination_location }}</div>
                    <div class="contact-secondary">{{ truncateText(transport.destination_address, 30) }}</div>
                  </div>
                </td>
                
                <td>
                  <div class="contact-cell">
                    <div class="contact-primary">{{ formatDate(transport.scheduled_time) }}</div>
                    <div class="contact-secondary">{{ formatTime(transport.scheduled_time) }}</div>
                  </div>
                </td>
                
                <td>
                  <div v-if="transport.driver" class="contact-cell">
                    <div class="contact-primary">{{ transport.driver.first_name }} {{ transport.driver.last_name }}</div>
                    <div class="contact-secondary">{{ transport.driver.phone }}</div>
                  </div>
                  <div v-else class="text-secondary">Not assigned</div>
                </td>
                
                <td>
                  <span class="modern-badge" :class="getStatusBadgeColor(transport.status)">
                    {{ capitalizeFirst(transport.status) }}
                  </span>
                </td>
                
                <td>
                  <div class="action-cell">
                    <button @click="toggleDropdown(transport.id)" class="action-btn">
                      <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                      </svg>
                    </button>
                    
                    <div v-show="activeDropdown === transport.id" class="modern-dropdown">
                      <button @click="openViewModal(transport)" class="dropdown-item-modern">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        View Details
                      </button>
                      
                      <button 
                        v-if="['requested', 'assigned'].includes(transport.status)"
                        @click="openEditModal(transport)" 
                        class="dropdown-item-modern"
                      >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Edit Request
                      </button>
                      
                      <div class="dropdown-divider"></div>
                      
                      <button
                        v-if="transport.status === 'requested'"
                        @click="openAssignDriverModal(transport)"
                        class="dropdown-item-modern success"
                      >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        Assign Driver
                      </button>
                      
                      <button
                        v-if="transport.status === 'assigned'"
                        @click="handleStartTransport(transport)"
                        class="dropdown-item-modern success"
                      >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Start Transport
                      </button>
                      
                      <button
                        v-if="transport.status === 'in_progress'"
                        @click="openCompleteModal(transport)"
                        class="dropdown-item-modern success"
                      >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Complete Transport
                      </button>
                      
                      <div v-if="['requested', 'assigned'].includes(transport.status)" class="dropdown-divider"></div>
                      
                      <button
                        v-if="['requested', 'assigned'].includes(transport.status)"
                        @click="handleCancelTransport(transport)"
                        class="dropdown-item-modern danger"
                      >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
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

          <!-- Pagination -->
          <div v-if="transports.last_page > 1" class="pagination-container">
            <div class="pagination-info">
              Showing {{ (transports.current_page - 1) * transports.per_page + 1 }} to {{ Math.min(transports.current_page * transports.per_page, transports.total) }} of {{ transports.total }} transport requests
            </div>
            <div class="pagination-controls">
              <button 
                @click="prevPage" 
                :disabled="transports.current_page === 1"
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
                  :class="['pagination-page', { active: page === transports.current_page }]"
                >
                  {{ page }}
                </button>
              </div>
              
              <button 
                @click="nextPage" 
                :disabled="transports.current_page === transports.last_page"
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
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
          </svg>
          <h3>No transport requests found</h3>
          <p>
            {{ hasActiveFilters ? 'Try adjusting your search or filters.' : 'Start by creating a new transport request.' }}
          </p>
          <button @click="openCreateModal" class="btn btn-primary">
            Create First Request
          </button>
        </div>
      </div>

      <!-- Create/Edit Transport Modal -->
      <div v-if="showTransportModal" class="modal-overlay" @click.self="closeTransportModal">
        <div class="modal modal-lg">
          <div class="modal-header">
            <h2 class="modal-title">
              {{ isEditing ? 'Edit Transport Request' : 'New Transport Request' }}
            </h2>
            <button @click="closeTransportModal" class="modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <form @submit.prevent="saveTransport">
            <div class="modal-body">
              <div class="form-grid">
                <!-- Request Information -->
                <div class="form-section-header">
                  <h3 class="form-section-title">Request Information</h3>
                </div>

                <div class="form-group">
                  <label>Patient <span class="required">*</span></label>
                  <SearchableSelect
                    v-model="transportForm.patient_id"
                    :options="patientOptions"
                    placeholder="Select patient..."
                    required
                  />
                </div>

                <div class="form-group">
                  <label>Transport Type <span class="required">*</span></label>
                  <SearchableSelect
                    v-model="transportForm.transport_type"
                    :options="transportTypeOptions"
                    placeholder="Select transport type..."
                    required
                  />
                </div>

                <div class="form-group">
                  <label>Priority Level <span class="required">*</span></label>
                  <SearchableSelect
                    v-model="transportForm.priority"
                    :options="priorityOptions"
                    placeholder="Select priority..."
                    required
                  />
                </div>

                <div class="form-group">
                  <label>Scheduled Date & Time <span class="required">*</span></label>
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
                  <label>Pickup Location Name <span class="required">*</span></label>
                  <input
                    type="text"
                    v-model="transportForm.pickup_location"
                    placeholder="e.g., Patient's Home, Hospital Ward 3A"
                    required
                  />
                </div>

                <div class="form-group form-grid-full">
                  <label>Pickup Address <span class="required">*</span></label>
                  <textarea
                    v-model="transportForm.pickup_address"
                    rows="2"
                    placeholder="Complete pickup address..."
                    required
                  ></textarea>
                </div>

                <div class="form-group form-grid-full">
                  <label>Destination Location Name <span class="required">*</span></label>
                  <input
                    type="text"
                    v-model="transportForm.destination_location"
                    placeholder="e.g., Korle-Bu Teaching Hospital, Ridge Hospital"
                    required
                  />
                </div>

                <div class="form-group form-grid-full">
                  <label>Destination Address <span class="required">*</span></label>
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
                  <label>Reason for Transport <span class="required">*</span></label>
                  <textarea
                    v-model="transportForm.reason"
                    rows="3"
                    placeholder="Medical appointment, emergency treatment, routine check-up..."
                    required
                  ></textarea>
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
              <button type="submit" :disabled="isSaving" class="btn btn-primary">
                <div v-if="isSaving" class="spinner spinner-sm"></div>
                {{ isEditing ? 'Update Request' : 'Create Request' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- View Transport Modal -->
      <div v-if="showViewModal && currentTransport" class="modal-overlay" @click.self="closeViewModal">
        <div class="modal modal-xl">
          <div class="modal-header">
            <h2 class="modal-title">Transport Request Details</h2>
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
                  :src="getPatientAvatar(currentTransport.patient)"
                  class="profile-avatar-large"
                />
                <h3 class="profile-name-view">
                  {{ currentTransport.patient_name }}
                </h3>
                <span class="modern-badge badge-info">Request #{{ currentTransport.id }}</span>
                
                <div class="profile-contact-view">
                  <div class="contact-item-view">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span>Requested by: {{ currentTransport.requested_by_name }}</span>
                  </div>
                  
                  <div class="contact-item-view">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>{{ formatDateTime(currentTransport.created_at) }}</span>
                  </div>
                </div>
                
                <div class="status-badges-view">
                  <span class="modern-badge" :class="getStatusBadgeColor(currentTransport.status)">
                    {{ capitalizeFirst(currentTransport.status) }}
                  </span>
                  <span class="modern-badge" :class="getPriorityBadgeColor(currentTransport.priority)">
                    {{ capitalizeFirst(currentTransport.priority) }} Priority
                  </span>
                  <span class="modern-badge" :class="getTypeBadgeColor(currentTransport.transport_type)">
                    {{ capitalizeFirst(currentTransport.transport_type) }}
                  </span>
                </div>
              </div>

              <div class="details-section-view">
                <div class="details-group">
                  <h4 class="details-header">Transport Information</h4>
                  <div class="details-grid-view">
                    <div class="detail-item-view">
                      <label>Scheduled Time</label>
                      <p>{{ formatDateTime(currentTransport.scheduled_time) }}</p>
                    </div>
                    <div class="detail-item-view">
                      <label>Reason</label>
                      <p>{{ currentTransport.reason }}</p>
                    </div>
                  </div>
                </div>

                <div class="details-group">
                  <h4 class="details-header">Location Details</h4>
                  <div class="location-details">
                    <div class="location-item">
                      <div class="location-header">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
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
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
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

                <div v-if="currentTransport.driver" class="details-group">
                  <h4 class="details-header">Assigned Driver</h4>
                  <div class="assigned-vehicle-card">
                    <div class="vehicle-details">
                      <h5>{{ currentTransport.driver.first_name }} {{ currentTransport.driver.last_name }}</h5>
                      <p>Phone: {{ currentTransport.driver.phone }}</p>
                      <p v-if="currentTransport.driver.current_vehicle">Vehicle: {{ currentTransport.driver.current_vehicle.registration_number }}</p>
                    </div>
                    <span class="modern-badge badge-success">Assigned</span>
                  </div>
                </div>

                <div v-if="currentTransport.special_requirements" class="details-group">
                  <h4 class="details-header">Special Requirements</h4>
                  <p class="notes-text">{{ currentTransport.special_requirements }}</p>
                </div>

                <div v-if="currentTransport.contact_person" class="details-group">
                  <h4 class="details-header">Contact Person</h4>
                  <p class="notes-text">{{ currentTransport.contact_person }}</p>
                </div>
              </div>
            </div>
          </div>

          <div class="modal-actions">
            <button @click="closeViewModal" class="btn btn-secondary">Close</button>
            <button 
              v-if="['requested', 'assigned'].includes(currentTransport.status)"
              @click="editFromView" 
              class="btn btn-primary"
            >
              Edit Request
            </button>
          </div>
        </div>
      </div>

      <!-- Assign Driver Modal -->
      <div v-if="showAssignDriverModal && currentTransport" class="modal-overlay" @click.self="closeAssignDriverModal">
        <div class="modal modal-lg">
          <div class="modal-header">
            <h3 class="modal-title">Assign Driver to Transport</h3>
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
                  <h4>Assigning driver to transport:</h4>
                  <div class="driver-card">
                    <div class="driver-info">
                      <span class="driver-name">{{ currentTransport.patient_name }}</span>
                      <span class="driver-details">{{ currentTransport.pickup_location }} → {{ currentTransport.destination_location }}</span>
                      <div class="transport-meta">
                        <span class="modern-badge badge-info">{{ capitalizeFirst(currentTransport.priority) }} Priority</span>
                        <span class="modern-badge badge-success">{{ capitalizeFirst(currentTransport.transport_type) }}</span>
                      </div>
                      <span class="driver-details">{{ formatDateTime(currentTransport.scheduled_time) }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <div class="form-grid">
                <div class="form-group form-grid-full">
                  <label>Select Available Driver <span class="required">*</span></label>
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
                  <div class="driver-avatar-section">
                    <img :src="generateAvatar(selectedDriverPreview)" class="driver-avatar-preview" />
                  </div>
                  <div class="vehicle-info">
                    <div class="vehicle-reg">{{ selectedDriverPreview.first_name }} {{ selectedDriverPreview.last_name }}</div>
                    <div class="vehicle-details">
                      <span>ID: {{ selectedDriverPreview.driver_id }}</span>
                      <span v-if="selectedDriverPreview.phone">Phone: {{ selectedDriverPreview.phone }}</span>
                    </div>
                    <div v-if="selectedDriverPreview.current_vehicle" class="vehicle-details">
                      <span>Vehicle: {{ selectedDriverPreview.current_vehicle.registration_number }} ({{ selectedDriverPreview.current_vehicle.vehicle_type }})</span>
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

      <!-- Complete Transport Modal -->
      <div v-if="showCompleteModal && currentTransport" class="modal-overlay" @click.self="closeCompleteModal">
        <div class="modal modal-sm">
          <div class="modal-header">
            <h3 class="modal-title">Complete Transport</h3>
            <button @click="closeCompleteModal" class="modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <form @submit.prevent="completeTransport">
            <div class="modal-body">
              <p>Mark transport for <strong>{{ currentTransport.patient_name }}</strong> as completed?</p>
              
              <div class="form-grid">
                <div class="form-group form-grid-full">
                  <label>Actual Cost (Optional)</label>
                  <input
                    type="number"
                    v-model="completionForm.actual_cost"
                    step="0.01"
                    min="0"
                    placeholder="Enter actual cost if different from estimate"
                  />
                </div>

                <div class="form-group form-grid-full">
                  <label>Rating (Optional)</label>
                  <select v-model="completionForm.rating">
                    <option value="">Select rating</option>
                    <option value="5">5 - Excellent</option>
                    <option value="4">4 - Good</option>
                    <option value="3">3 - Average</option>
                    <option value="2">2 - Below Average</option>
                    <option value="1">1 - Poor</option>
                  </select>
                </div>

                <div class="form-group form-grid-full">
                  <label>Feedback (Optional)</label>
                  <textarea
                    v-model="completionForm.feedback"
                    rows="3"
                    placeholder="Any comments about the transport..."
                  ></textarea>
                </div>
              </div>
            </div>

            <div class="modal-actions">
              <button type="button" @click="closeCompleteModal" class="btn btn-secondary">
                Cancel
              </button>
              <button type="submit" :disabled="isSaving" class="btn btn-primary">
                <div v-if="isSaving" class="spinner spinner-sm"></div>
                Complete Transport
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
import * as transportService from '../../services/transportService'

const toast = inject('toast')

// Reactive data
const transports = ref({ data: [], total: 0, per_page: 15, current_page: 1, last_page: 1 })
const patients = ref([])
const drivers = ref([])
const statistics = ref({})
const loading = ref(true)
const loadingDrivers = ref(false)
const searchQuery = ref('')
const statusFilter = ref('all')
const priorityFilter = ref('all')
const typeFilter = ref('all')

// Modal states
const showTransportModal = ref(false)
const showViewModal = ref(false)
const showAssignDriverModal = ref(false)
const showCompleteModal = ref(false)
const isEditing = ref(false)
const currentTransport = ref(null)
const isSaving = ref(false)

// Dropdown state
const activeDropdown = ref(null)

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

const assignmentForm = ref({
  driver_id: '',
  notes: ''
})

const completionForm = ref({
  actual_cost: '',
  rating: '',
  feedback: ''
})

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

// Computed properties
const hasActiveFilters = computed(() => {
  return searchQuery.value || statusFilter.value !== 'all' ||
    priorityFilter.value !== 'all' || typeFilter.value !== 'all'
})

const patientOptions = computed(() => {
  return patients.value.map(patient => ({
    value: patient.id,
    label: `${patient.first_name} ${patient.last_name}${patient.ghana_card_number ? ' (' + patient.ghana_card_number + ')' : ''}`,
    searchText: `${patient.first_name} ${patient.last_name} ${patient.ghana_card_number || ''} ${patient.phone || ''}`.toLowerCase()
  }))
})

const driverOptions = computed(() => {
  return drivers.value
    .filter(driver => !driver.is_suspended && driver.is_active)
    .map(driver => ({
      value: driver.id,
      label: `${driver.first_name} ${driver.last_name} - ${driver.driver_id}${driver.current_vehicle ? ` (${driver.current_vehicle.registration_number})` : ''}`,
      searchText: `${driver.first_name} ${driver.last_name} ${driver.driver_id} ${driver.phone}`.toLowerCase()
    }))
})

const selectedDriverPreview = computed(() => {
  if (!assignmentForm.value.driver_id) return null
  return drivers.value.find(driver => driver.id == assignmentForm.value.driver_id)
})

// Methods
const loadTransports = async (page = 1) => {
  loading.value = true
  try {
    const params = {
      page: page,
      per_page: 15,
      search: searchQuery.value || undefined,
      status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
      priority: priorityFilter.value !== 'all' ? priorityFilter.value : undefined,
      type: typeFilter.value !== 'all' ? typeFilter.value : undefined
    }
    
    Object.keys(params).forEach(key => params[key] === undefined && delete params[key])
    
    const response = await transportService.getTransports(params)
    
    console.log('Transport API Response:', response)
    
    if (response.success && response.data) {
      if (response.pagination) {
        transports.value = {
          data: response.data || [],
          total: response.pagination.total || 0,
          current_page: response.pagination.current_page || 1,
          last_page: response.pagination.last_page || 1,
          per_page: response.pagination.per_page || 15
        }
      } else if (Array.isArray(response.data)) {
        transports.value = {
          data: response.data,
          total: response.data.length,
          current_page: 1,
          last_page: 1,
          per_page: 15
        }
      } else {
        transports.value = {
          data: response.data || [],
          total: 0,
          current_page: 1,
          last_page: 1,
          per_page: 15
        }
      }
    } else if (response.data && typeof response.data === 'object') {
      transports.value = {
        data: response.data.data || response.data || [],
        total: response.data.total || 0,
        current_page: response.data.current_page || 1,
        last_page: response.data.last_page || 1,
        per_page: response.data.per_page || 15
      }
    } else {
      transports.value = {
        data: Array.isArray(response) ? response : [],
        total: Array.isArray(response) ? response.length : 0,
        current_page: 1,
        last_page: 1,
        per_page: 15
      }
    }
    
    console.log('Processed transports:', transports.value)
  } catch (error) {
    console.error('Error loading transports:', error)
    toast.showError('Failed to load transport requests')
    transports.value = { data: [], total: 0, current_page: 1, last_page: 1, per_page: 15 }
  }
  loading.value = false
}

const loadPatients = async () => {
  try {
    const response = await transportService.getPatients()
    patients.value = response.data || response || []
  } catch (error) {
    console.error('Error loading patients:', error)
  }
}

const loadDrivers = async () => {
  loadingDrivers.value = true
  try {
    const response = await transportService.getAvailableDrivers()
    drivers.value = response.data || response || []
  } catch (error) {
    console.error('Error loading drivers:', error)
    toast.showError('Failed to load available drivers')
  }
  loadingDrivers.value = false
}

const getStatistics = async () => {
  try {
    const response = await transportService.getDashboardStats()
    console.log('Statistics Response:', response)
    
    // Handle different response structures
    if (response.success && response.data) {
      statistics.value = response.data
    } else if (response.data) {
      statistics.value = response.data
    } else {
      statistics.value = response || {}
    }
    
    console.log('Processed statistics:', statistics.value)
  } catch (error) {
    console.error('Error loading statistics:', error)
    statistics.value = {
      total_requests: 0,
      today_requests: 0,
      pending_requests: 0,
      active_transports: 0,
      completed_today: 0
    }
  }
}

const getPatientAvatar = (patient) => {
  if (!patient) {
    return 'https://ui-avatars.com/api/?name=N+A&color=667eea&background=f8f9fa&size=200&font-size=0.6'
  }
  
  if (patient.avatar) {
    return `/storage/${patient.avatar}`
  }
  
  const name = `${patient.first_name || ''} ${patient.last_name || ''}`
  return `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&color=667eea&background=f8f9fa&size=200&font-size=0.6`
}

const generateAvatar = (driver) => {
  if (!driver) {
    return 'https://ui-avatars.com/api/?name=N+A&color=667eea&background=f8f9fa&size=200&font-size=0.6'
  }
  const name = `${driver.first_name || ''} ${driver.last_name || ''}`
  return `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&color=667eea&background=f8f9fa&size=200&font-size=0.6`
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

const truncateText = (text, maxLength) => {
  if (!text) return 'N/A'
  return text.length > maxLength ? text.substring(0, maxLength) + '...' : text
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatTime = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatDateTime = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatDateForInput = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  if (isNaN(date.getTime())) return ''
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  const hours = String(date.getHours()).padStart(2, '0')
  const minutes = String(date.getMinutes()).padStart(2, '0')
  return `${year}-${month}-${day}T${hours}:${minutes}`
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

const openAssignDriverModal = async (transport) => {
  currentTransport.value = transport
  assignmentForm.value = { driver_id: '', notes: '' }
  showAssignDriverModal.value = true
  await loadDrivers()
  activeDropdown.value = null
}

const openCompleteModal = (transport) => {
  currentTransport.value = transport
  completionForm.value = { actual_cost: '', rating: '', feedback: '' }
  showCompleteModal.value = true
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

const closeCompleteModal = () => {
  showCompleteModal.value = false
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
    scheduled_time: formatDateForInput(transport.scheduled_time),
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
    if (isEditing.value) {
      await transportService.updateTransport(currentTransport.value.id, transportForm.value)
    } else {
      await transportService.createTransport(transportForm.value)
    }
    
    await Promise.all([loadTransports(transports.value.current_page), getStatistics()])
    closeTransportModal()
    toast.showSuccess(isEditing.value ? 'Transport request updated successfully!' : 'Transport request created successfully!')
  } catch (error) {
    console.error('Error saving transport:', error)
    toast.showError(error.message || 'Failed to save transport request')
  }
  
  isSaving.value = false
}

const assignDriver = async () => {
  isSaving.value = true
  
  try {
    await transportService.assignDriver(currentTransport.value.id, assignmentForm.value)
    await Promise.all([loadTransports(transports.value.current_page), getStatistics()])
    closeAssignDriverModal()
    toast.showSuccess('Driver assigned successfully!')
  } catch (error) {
    console.error('Error assigning driver:', error)
    toast.showError(error.message || 'Failed to assign driver')
  }
  
  isSaving.value = false
}

const handleStartTransport = async (transport) => {
  try {
    await transportService.startTransport(transport.id)
    await Promise.all([loadTransports(transports.value.current_page), getStatistics()])
    toast.showSuccess('Transport started successfully!')
  } catch (error) {
    console.error('Error starting transport:', error)
    toast.showError(error.message || 'Failed to start transport')
  }
  activeDropdown.value = null
}

const completeTransport = async () => {
  isSaving.value = true
  
  try {
    await transportService.completeTransport(currentTransport.value.id, completionForm.value)
    await Promise.all([loadTransports(transports.value.current_page), getStatistics()])
    closeCompleteModal()
    toast.showSuccess('Transport completed successfully!')
  } catch (error) {
    console.error('Error completing transport:', error)
    toast.showError(error.message || 'Failed to complete transport')
  }
  
  isSaving.value = false
}

const handleCancelTransport = async (transport) => {
  if (confirm(`Are you sure you want to cancel the transport request for ${transport.patient_name}?`)) {
    try {
      await transportService.cancelTransport(transport.id)
      await Promise.all([loadTransports(transports.value.current_page), getStatistics()])
      toast.showSuccess('Transport request cancelled successfully!')
    } catch (error) {
      console.error('Error cancelling transport:', error)
      toast.showError(error.message || 'Failed to cancel transport request')
    }
  }
  activeDropdown.value = null
}

const exportTransports = async () => {
  try {
    toast.showInfo('Preparing export...')
    
    const filters = {
      status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
      priority: priorityFilter.value !== 'all' ? priorityFilter.value : undefined,
      type: typeFilter.value !== 'all' ? typeFilter.value : undefined,
      search: searchQuery.value || undefined
    }
    
    Object.keys(filters).forEach(key => filters[key] === undefined && delete filters[key])
    
    const { blob, filename } = await transportService.exportTransports(filters)
    
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = filename
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
    
    toast.showSuccess('Transport requests exported successfully!')
  } catch (error) {
    console.error('Error exporting transports:', error)
    toast.showError('Failed to export transport requests')
  }
}

const goToPage = (page) => {
  if (page >= 1 && page <= transports.value.last_page) {
    loadTransports(page)
  }
}

const nextPage = () => {
  if (transports.value.current_page < transports.value.last_page) {
    goToPage(transports.value.current_page + 1)
  }
}

const prevPage = () => {
  if (transports.value.current_page > 1) {
    goToPage(transports.value.current_page - 1)
  }
}

const getPaginationPages = () => {
  const pages = []
  const maxVisible = 5
  let start = Math.max(1, transports.value.current_page - Math.floor(maxVisible / 2))
  let end = Math.min(transports.value.last_page, start + maxVisible - 1)
  
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
    loadTransports(1)
  }, 500)
})

watch([statusFilter, priorityFilter, typeFilter], () => {
  loadTransports(1)
})

watch(() => assignmentForm.value.driver_id, (newDriverId) => {
  if (newDriverId) {
    console.log('Selected driver:', selectedDriverPreview.value)
  }
})

onMounted(async () => {
  try {
    await Promise.all([
      loadTransports(),
      loadPatients(),
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

.transport-page {
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
.transport-table-container {
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
  margin-bottom: -8px;
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

.location-details {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 16px;
}

.location-item {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 16px;
}

.location-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
  font-weight: 600;
  color: #0f172a;
}

.location-header svg {
  width: 20px;
  height: 20px;
  color: #667eea;
}

.location-content strong {
  display: block;
  color: #0f172a;
  margin-bottom: 6px;
}

.location-content p {
  margin: 0;
  color: #64748b;
  font-size: 14px;
  line-height: 1.5;
}

.assigned-vehicle-card {
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
  margin: 2px 0;
  font-size: 14px;
}

.notes-text {
  background: white;
  padding: 16px;
  border-radius: 8px;
  line-height: 1.6;
  color: #334155;
  margin: 0;
}

/* Assignment Modal */
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

.transport-meta {
  display: flex;
  gap: 8px;
  margin-bottom: 10px;
  flex-wrap: wrap;
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

.driver-avatar-section {
  flex-shrink: 0;
}

.driver-avatar-preview {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #e2e8f0;
}

.vehicle-info {
  flex: 1;
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

  .location-details {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .transport-page {
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