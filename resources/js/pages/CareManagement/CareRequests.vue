<template>
  <MainLayout>
    <div class="users-page">
      <!-- Page Header -->
      <div class="page-header">
        <div class="page-header-content">
          <h1>Care Requests Management</h1>
          <p>Manage home care assessment requests and assignments</p>
        </div>
        <div class="page-header-actions">
          <button @click="exportCareRequests" class="btn-modern btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Export
          </button>
          <button @click="refreshRequests" class="btn-modern btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Refresh
          </button>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon orange">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Pending Payment</div>
            <div class="stat-value">{{ stats.pending_payment || 0 }}</div>
            <div class="stat-change neutral">Awaiting assessment fee</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon green">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Payment Received</div>
            <div class="stat-value">{{ stats.payment_received || 0 }}</div>
            <div class="stat-change positive">Ready for assignment</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon blue">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Active Care</div>
            <div class="stat-value">{{ stats.care_active || 0 }}</div>
            <div class="stat-change positive">Services ongoing</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon purple">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Total Revenue</div>
            <div class="stat-value">GHS {{ formatMoney(stats.total_revenue || 0) }}</div>
            <div class="stat-change positive">All payments</div>
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
            placeholder="Search by patient name, phone, or ID..."
            v-model="searchQuery"
            class="search-input"
          />
        </div>
        <div class="filters-group">
          <select v-model="statusFilter" class="filter-select">
            <option value="all">All Status</option>
            <option value="pending_payment">Pending Payment</option>
            <option value="payment_received">Payment Received</option>
            <option value="nurse_assigned">Nurse Assigned</option>
            <option value="assessment_scheduled">Assessment Scheduled</option>
            <option value="assessment_completed">Assessment Completed</option>
            <option value="under_review">Under Review</option>
            <option value="awaiting_care_payment">Awaiting Care Payment</option>
            <option value="care_payment_received">Care Payment Received</option>
            <option value="care_active">Care Active</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
          </select>
          
          <select v-model="careTypeFilter" class="filter-select">
            <option value="all">All Care Types</option>
            <option value="general_nursing">General Nursing</option>
            <option value="elderly_care">Elderly Care</option>
            <option value="post_surgical">Post-Surgical</option>
            <option value="chronic_disease">Chronic Disease</option>
            <option value="palliative_care">Palliative Care</option>
            <option value="rehabilitation">Rehabilitation</option>
            <option value="wound_care">Wound Care</option>
            <option value="medication_management">Medication Management</option>
          </select>

          <select v-model="urgencyFilter" class="filter-select">
            <option value="all">All Urgency</option>
            <option value="routine">Routine</option>
            <option value="urgent">Urgent</option>
            <option value="emergency">Emergency</option>
          </select>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="loading-state">
        <div class="loading-spinner"></div>
        <p class="loading-text">Loading care requests...</p>
      </div>

      <!-- Care Requests Table -->
      <div v-else class="users-table-container">
        <div class="table-wrapper">
          <table class="modern-table">
            <thead>
              <tr>
                <th>Patient</th>
                <th>Care Type</th>
                <!-- <th>Urgency</th> -->
                <th>Status</th>
                <th>Assigned Nurse</th>
                <th>Payment Status</th>
                <th>Requested</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="request in careRequests" :key="request.id">
             
                <td>
                  <div class="user-cell">
                    <img :src="request.patient?.avatar_url || generateAvatar(request.patient)" :alt="request.patient?.first_name" class="user-avatar-table" />
                    <div class="user-details-table">
                      <div class="user-name-table">{{ request.patient?.first_name }} {{ request.patient?.last_name }}</div>
                      <div class="user-id-table">{{ request.patient?.phone }}</div>
                    </div>
                  </div>
                </td>
                
                <td>
                  <span :class="'modern-badge ' + getCareTypeBadgeClass(request.care_type)">
                    {{ formatCareType(request.care_type) }}
                  </span>
                </td>
                
                <!-- <td>
                  <span :class="'modern-badge ' + getUrgencyBadgeClass(request.urgency_level)">
                    {{ formatUrgency(request.urgency_level) }}
                  </span>
                </td> -->
                
                <td>
                  <span :class="'modern-badge ' + getStatusBadgeClass(request.status)">
                    {{ formatStatus(request.status) }}
                  </span>
                </td>
                
                <td>
                  <div v-if="request.assigned_nurse" class="nurse-cell">
                    <img :src="request.assigned_nurse.avatar_url || generateAvatar(request.assigned_nurse)" :alt="request.assigned_nurse.first_name" class="nurse-avatar-small" />
                    <div class="nurse-info-small">
                      <div class="nurse-name-small">{{ request.assigned_nurse.first_name }} {{ request.assigned_nurse.last_name }}</div>
                      <div class="nurse-role-small">Assigned Nurse</div>
                    </div>
                  </div>
                  <div v-else class="no-nurse-assigned">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                    Not assigned
                  </div>
                </td>
                
                <td>
                  <div class="payment-status-cell">
                    <div v-if="request.assessment_payment" class="payment-item">
                      <span class="payment-label">Assessment:</span>
                      <span :class="'payment-badge ' + getPaymentStatusClass(request.assessment_payment.status)">
                        {{ formatPaymentStatus(request.assessment_payment.status) }}
                      </span>
                    </div>
                    <div v-if="request.care_payment" class="payment-item">
                      <span class="payment-label">Care Fee:</span>
                      <span :class="'payment-badge ' + getPaymentStatusClass(request.care_payment.status)">
                        {{ formatPaymentStatus(request.care_payment.status) }}
                      </span>
                    </div>
                    <div v-if="!request.assessment_payment && !request.care_payment" class="no-payment">
                      —
                    </div>
                  </div>
                </td>
                
                <td>
                  <div class="date-cell">{{ formatDate(request.created_at) }}</div>
                </td>
                
                <td>
                  <div class="action-cell">
                    <button @click="toggleDropdown(request.id)" class="action-btn">
                      <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                      </svg>
                    </button>
                    <div v-show="activeDropdown === request.id" class="modern-dropdown">
                      <button @click="viewRequest(request)" class="dropdown-item-modern">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        View Details
                      </button>
                      
                      <button v-if="request.status === 'payment_received'" @click="openAssignNurseModal(request)" class="dropdown-item-modern success">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                        Assign Nurse
                      </button>
                      
                      <button v-if="request.status === 'nurse_assigned' || request.status === 'assessment_scheduled'" @click="openScheduleAssessmentModal(request)" class="dropdown-item-modern">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Schedule Assessment
                      </button>
                      
                      <button v-if="request.status === 'under_review' || request.status === 'assessment_completed'" @click="openIssueCareCostModal(request)" class="dropdown-item-modern success">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Issue Care Cost
                      </button>
                      
                      <button v-if="request.status === 'care_payment_received'" @click="openStartCareModal(request)" class="dropdown-item-modern success">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Start Care
                      </button>
                      
                      <div class="dropdown-divider"></div>
                      
                      <button v-if="canReject(request)" @click="openRejectModal(request)" class="dropdown-item-modern danger">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Reject Request
                      </button>
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <div v-if="careRequests.length === 0" class="empty-state">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
          </svg>
          <h3>No care requests found</h3>
          <p>Care requests will appear here when patients submit them.</p>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="lastPage > 1" class="pagination-container">
        <div class="pagination-info">
          Showing {{ Math.min((currentPage - 1) * perPage + 1, total) }} to {{ Math.min(currentPage * perPage, total) }} of {{ total }} requests
        </div>
        <div class="pagination-controls">
          <button 
            @click="prevPage" 
            :disabled="currentPage === 1"
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
              :class="['pagination-page', { active: page === currentPage }]"
            >
              {{ page }}
            </button>
          </div>
          
          <button 
            @click="nextPage" 
            :disabled="currentPage === lastPage"
            class="pagination-btn"
          >
            Next
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>
        </div>
      </div>

      <!-- View Request Modal -->
      <div v-if="showViewModal && selectedRequest" class="modal-overlay" @click.self="closeViewModal">
        <div class="modal modal-xl">
          <div class="modal-header">
            <h2 class="modal-title">Care Request Details</h2>
            <button @click="closeViewModal" class="modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="modal-body">
            <div class="user-view-grid">
              <!-- Request Profile Section -->
              <div class="user-profile-section">
                <div class="request-icon-large">
                  <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                  </svg>
                </div>
                <h3 class="profile-name-view">Request #{{ selectedRequest.id }}</h3>
                <p class="request-patient-info">{{ selectedRequest.patient?.first_name }} {{ selectedRequest.patient?.last_name }}</p>
                
                <div class="request-badges-view">
                  <span :class="'modern-badge ' + getStatusBadgeClass(selectedRequest.status)">
                    {{ formatStatus(selectedRequest.status) }}
                  </span>
                  <span :class="'modern-badge ' + getUrgencyBadgeClass(selectedRequest.urgency_level)">
                    {{ formatUrgency(selectedRequest.urgency_level) }}
                  </span>
                </div>
              </div>

              <!-- Details Section -->
              <div class="details-section-view">
                <!-- Patient Information -->
                <div class="details-group">
                  <h4 class="details-header">Patient Information</h4>
                  <div class="details-grid-view">
                    <div class="detail-item-view">
                      <label>Name</label>
                      <p>{{ selectedRequest.patient?.first_name }} {{ selectedRequest.patient?.last_name }}</p>
                    </div>
                    <div class="detail-item-view">
                      <label>Phone</label>
                      <p>{{ selectedRequest.patient?.phone }}</p>
                    </div>
                    <div class="detail-item-view">
                      <label>Email</label>
                      <p>{{ selectedRequest.patient?.email }}</p>
                    </div>
                    <div class="detail-item-view">
                      <label>City</label>
                      <p>{{ selectedRequest.city || 'N/A' }}</p>
                    </div>
                  </div>
                  <div class="detail-item-view" style="margin-top: 16px;">
                    <label>Service Address</label>
                    <p>{{ selectedRequest.service_address }}</p>
                  </div>
                </div>

                <!-- Request Details -->
                <div class="details-group">
                  <h4 class="details-header">Request Details</h4>
                  <div class="details-grid-view">
                    <div class="detail-item-view">
                      <label>Care Type</label>
                      <span :class="'modern-badge ' + getCareTypeBadgeClass(selectedRequest.care_type)">
                        {{ formatCareType(selectedRequest.care_type) }}
                      </span>
                    </div>
                    <div class="detail-item-view">
                      <label>Urgency</label>
                      <span :class="'modern-badge ' + getUrgencyBadgeClass(selectedRequest.urgency_level)">
                        {{ formatUrgency(selectedRequest.urgency_level) }}
                      </span>
                    </div>
                    <div v-if="selectedRequest.preferred_start_date" class="detail-item-view">
                      <label>Preferred Start Date</label>
                      <p>{{ formatDate(selectedRequest.preferred_start_date) }}</p>
                    </div>
                    <div v-if="selectedRequest.preferred_time" class="detail-item-view">
                      <label>Preferred Time</label>
                      <p>{{ formatPreferredTime(selectedRequest.preferred_time) }}</p>
                    </div>
                  </div>

                  <div class="detail-item-view" style="margin-top: 16px;">
                    <label>Description</label>
                    <p>{{ selectedRequest.description }}</p>
                  </div>

                  <div v-if="selectedRequest.special_requirements" class="detail-item-view" style="margin-top: 16px;">
                    <label>Special Requirements</label>
                    <p>{{ selectedRequest.special_requirements }}</p>
                  </div>
                </div>

                <!-- Assignment -->
                <div v-if="selectedRequest.assigned_nurse" class="details-group">
                  <h4 class="details-header">Assignment</h4>
                  <div class="details-grid-view">
                    <div class="detail-item-view">
                      <label>Assigned Nurse</label>
                      <p>{{ selectedRequest.assigned_nurse.first_name }} {{ selectedRequest.assigned_nurse.last_name }}</p>
                    </div>
                    <div v-if="selectedRequest.assessment_scheduled_at" class="detail-item-view">
                      <label>Assessment Scheduled</label>
                      <p>{{ formatDateTime(selectedRequest.assessment_scheduled_at) }}</p>
                    </div>
                  </div>
                </div>

                <!-- Payment Information -->
                <div class="details-group">
                  <h4 class="details-header">Payment Information</h4>
                  <div class="payment-details">
                    <div v-if="selectedRequest.assessment_payment" class="payment-detail-item">
                      <div class="payment-detail-header">
                        <span class="payment-type">Assessment Fee</span>
                        <span :class="'payment-badge ' + getPaymentStatusClass(selectedRequest.assessment_payment.status)">
                          {{ formatPaymentStatus(selectedRequest.assessment_payment.status) }}
                        </span>
                      </div>
                      <div class="payment-amount">{{ selectedRequest.assessment_payment.currency }} {{ selectedRequest.assessment_payment.total_amount }}</div>
                      <div v-if="selectedRequest.assessment_payment.reference_number" class="payment-ref">
                        Ref: {{ selectedRequest.assessment_payment.reference_number }}
                      </div>
                    </div>

                    <div v-if="selectedRequest.care_payment" class="payment-detail-item">
                      <div class="payment-detail-header">
                        <span class="payment-type">Care Fee</span>
                        <span :class="'payment-badge ' + getPaymentStatusClass(selectedRequest.care_payment.status)">
                          {{ formatPaymentStatus(selectedRequest.care_payment.status) }}
                        </span>
                      </div>
                      <div class="payment-amount">{{ selectedRequest.care_payment.currency }} {{ selectedRequest.care_payment.total_amount }}</div>
                      <div v-if="selectedRequest.care_payment.description" class="payment-description">
                        {{ selectedRequest.care_payment.description }}
                      </div>
                    </div>

                    <div v-if="!selectedRequest.assessment_payment && !selectedRequest.care_payment" class="no-payment-info">
                      No payment information available
                    </div>
                  </div>
                </div>

                <!-- Timeline -->
                <div class="details-group">
                  <h4 class="details-header">Timeline</h4>
                  <div class="timeline">
                    <div class="timeline-item">
                      <div class="timeline-marker completed"></div>
                      <div class="timeline-content">
                        <div class="timeline-title">Request Created</div>
                        <div class="timeline-date">{{ formatDateTime(selectedRequest.created_at) }}</div>
                      </div>
                    </div>
                    
                    <div v-if="selectedRequest.assessment_scheduled_at" class="timeline-item">
                      <div :class="['timeline-marker', isStatusCompleted(selectedRequest, 'assessment_scheduled') ? 'completed' : '']"></div>
                      <div class="timeline-content">
                        <div class="timeline-title">Assessment Scheduled</div>
                        <div class="timeline-date">{{ formatDateTime(selectedRequest.assessment_scheduled_at) }}</div>
                      </div>
                    </div>
                    
                    <div v-if="selectedRequest.assessment_completed_at" class="timeline-item">
                      <div class="timeline-marker completed"></div>
                      <div class="timeline-content">
                        <div class="timeline-title">Assessment Completed</div>
                        <div class="timeline-date">{{ formatDateTime(selectedRequest.assessment_completed_at) }}</div>
                      </div>
                    </div>
                    
                    <div v-if="selectedRequest.care_started_at" class="timeline-item">
                      <div class="timeline-marker completed"></div>
                      <div class="timeline-content">
                        <div class="timeline-title">Care Started</div>
                        <div class="timeline-date">{{ formatDateTime(selectedRequest.care_started_at) }}</div>
                      </div>
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
          </div>
        </div>
      </div>

      <!-- Assign Nurse Modal -->
      <div v-if="showAssignNurseModal && actioningRequest" class="modal-overlay" @click.self="closeAssignNurseModal">
        <div class="modal modal-md">
          <div class="modal-header">
            <h3 class="modal-title">Assign Nurse</h3>
            <button @click="closeAssignNurseModal" class="modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          
          <form @submit.prevent="assignNurse">
            <div class="modal-body">
              <div class="form-group">
                <label>Select Nurse <span class="required">*</span></label>
                <SearchableSelect
                  v-model="assignNurseForm.nurse_id"
                  :options="nurseOptions"
                  placeholder="Select a nurse"
                />
              </div>
            </div>

            <div class="modal-actions">
              <button type="button" @click="closeAssignNurseModal" class="btn btn-secondary">Cancel</button>
              <button type="submit" :disabled="saving" class="btn btn-primary">
                <div v-if="saving" class="spinner spinner-sm"></div>
                Assign Nurse
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Schedule Assessment Modal -->
      <div v-if="showScheduleModal && actioningRequest" class="modal-overlay" @click.self="closeScheduleModal">
        <div class="modal modal-md">
          <div class="modal-header">
            <h3 class="modal-title">Schedule Assessment</h3>
            <button @click="closeScheduleModal" class="modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          
          <form @submit.prevent="scheduleAssessment">
            <div class="modal-body">
              <div class="form-group">
                <label>Assessment Date & Time <span class="required">*</span></label>
                <input v-model="scheduleForm.scheduled_at" type="datetime-local" required />
              </div>
            </div>

            <div class="modal-actions">
              <button type="button" @click="closeScheduleModal" class="btn btn-secondary">Cancel</button>
              <button type="submit" :disabled="saving" class="btn btn-primary">
                <div v-if="saving" class="spinner spinner-sm"></div>
                Schedule
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Issue Care Cost Modal -->
      <div v-if="showIssueCareCostModal && actioningRequest" class="modal-overlay" @click.self="closeIssueCareCostModal">
        <div class="modal modal-lg">
          <div class="modal-header">
            <h3 class="modal-title">Issue Care Cost</h3>
            <button @click="closeIssueCareCostModal" class="modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          
          <form @submit.prevent="issueCareCost">
            <div class="modal-body">
              <div class="form-grid">
                <div class="form-group form-grid-full">
                  <label>Care Plan Details <span class="required">*</span></label>
                  <textarea v-model="careCostForm.care_plan_details" rows="4" placeholder="Describe the care plan and services..." required></textarea>
                </div>
                
                <div class="form-group">
                  <label>Amount (GHS) <span class="required">*</span></label>
                  <input v-model.number="careCostForm.amount" type="number" step="0.01" min="1" placeholder="0.00" required />
                </div>
                
                <div class="form-group">
                  <label>Duration (Days)</label>
                  <input v-model.number="careCostForm.duration_days" type="number" min="1" placeholder="Optional" />
                </div>
                
                <div class="form-group">
                  <label>Sessions per Week</label>
                  <input v-model.number="careCostForm.sessions_per_week" type="number" min="1" placeholder="Optional" />
                </div>
              </div>
            </div>

            <div class="modal-actions">
              <button type="button" @click="closeIssueCareCostModal" class="btn btn-secondary">Cancel</button>
              <button type="submit" :disabled="saving" class="btn btn-primary">
                <div v-if="saving" class="spinner spinner-sm"></div>
                Issue Care Cost
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Start Care Modal -->
      <div v-if="showStartCareModal && actioningRequest" class="modal-overlay" @click.self="closeStartCareModal">
        <div class="modal modal-sm">
          <div class="modal-header">
            <h3 class="modal-title">Start Care Services</h3>
            <button @click="closeStartCareModal" class="modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          
          <div class="modal-body">
            <p>Start care services for this request?</p>
            <p style="font-size: 13px; color: #64748b; margin-top: 12px;">
              The care services will begin immediately.
            </p>
          </div>

          <div class="modal-actions">
            <button @click="closeStartCareModal" class="btn btn-secondary">Cancel</button>
            <button @click="startCare" :disabled="saving" class="btn btn-primary">
              <div v-if="saving" class="spinner spinner-sm"></div>
              Start Care
            </button>
          </div>
        </div>
      </div>

      <!-- Reject Modal -->
      <div v-if="showRejectModal && actioningRequest" class="modal-overlay" @click.self="closeRejectModal">
        <div class="modal modal-md">
          <div class="modal-header">
            <h3 class="modal-title">Reject Care Request</h3>
            <button @click="closeRejectModal" class="modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          
          <form @submit.prevent="rejectRequest">
            <div class="modal-body">
              <div class="form-group">
                <label>Rejection Reason <span class="required">*</span></label>
                <textarea v-model="rejectForm.reason" rows="4" placeholder="Please provide a reason for rejection..." required></textarea>
              </div>
              <p style="font-size: 13px; color: #ef4444; margin-top: 12px; font-weight: 500;">
                ⚠️ This action cannot be undone.
              </p>
            </div>

            <div class="modal-actions">
              <button type="button" @click="closeRejectModal" class="btn btn-secondary">Cancel</button>
              <button type="submit" :disabled="saving" class="btn btn-danger">
                <div v-if="saving" class="spinner spinner-sm"></div>
                Reject Request
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
import * as careRequestsService from '../../services/careRequestsService'

const toast = inject('toast')

// Reactive data
const careRequests = ref([])
const loading = ref(true)
const saving = ref(false)
const searchQuery = ref('')
const statusFilter = ref('all')
const careTypeFilter = ref('all')
const urgencyFilter = ref('all')
const activeDropdown = ref(null)

// Pagination
const currentPage = ref(1)
const lastPage = ref(1)
const perPage = ref(15)
const total = ref(0)

// Stats
const stats = ref({
  pending_payment: 0,
  payment_received: 0,
  care_active: 0,
  total_revenue: 0
})

// Modal states
const showViewModal = ref(false)
const showAssignNurseModal = ref(false)
const showScheduleModal = ref(false)
const showIssueCareCostModal = ref(false)
const showStartCareModal = ref(false)
const showRejectModal = ref(false)

const selectedRequest = ref(null)
const actioningRequest = ref(null)

// Data lists
const nurses = ref([])

// Forms
const assignNurseForm = ref({
  nurse_id: ''
})

const scheduleForm = ref({
  scheduled_at: ''
})

const careCostForm = ref({
  amount: null,
  care_plan_details: '',
  duration_days: null,
  sessions_per_week: null
})

const rejectForm = ref({
  reason: ''
})

// Computed
const nurseOptions = computed(() => {
  if (!Array.isArray(nurses.value)) return []
  return nurses.value.map(nurse => ({
    value: nurse.id,
    label: `${nurse.first_name} ${nurse.last_name}${nurse.years_experience ? ` - ${nurse.years_experience}y exp` : ''}`
  }))
})

// Load data
const loadCareRequests = async (page = 1) => {
  loading.value = true
  try {
    const params = {
      page: page,
      per_page: perPage.value,
      search: searchQuery.value || '',
      status: statusFilter.value,
      care_type: careTypeFilter.value,
      urgency_level: urgencyFilter.value
    }
    
    const response = await careRequestsService.getCareRequests(params)
    
    if (response.success) {
      careRequests.value = response.data || []
      currentPage.value = response.pagination?.current_page || 1
      lastPage.value = response.pagination?.last_page || 1
      perPage.value = response.pagination?.per_page || 15
      total.value = response.pagination?.total || 0
    }
  } catch (error) {
    console.error('Error loading care requests:', error)
    toast.showError('Failed to load care requests')
  }
  loading.value = false
}

const loadStatistics = async () => {
  try {
    const response = await careRequestsService.getStatistics()
    if (response.success) {
      stats.value = response.data || {}
    }
  } catch (error) {
    console.error('Error loading statistics:', error)
  }
}

const loadNurses = async () => {
  try {
    const response = await careRequestsService.getAvailableNurses()
    if (response.success) {
      nurses.value = response.data || []
    }
  } catch (error) {
    console.error('Error loading nurses:', error)
  }
}

// Actions
const toggleDropdown = (requestId) => {
  activeDropdown.value = activeDropdown.value === requestId ? null : requestId
}

const viewRequest = (request) => {
  selectedRequest.value = request
  showViewModal.value = true
  activeDropdown.value = null
}

const closeViewModal = () => {
  showViewModal.value = false
  selectedRequest.value = null
}

const openAssignNurseModal = (request) => {
  actioningRequest.value = request
  assignNurseForm.value = { nurse_id: '' }
  showAssignNurseModal.value = true
  activeDropdown.value = null
  loadNurses()
}

const closeAssignNurseModal = () => {
  showAssignNurseModal.value = false
  actioningRequest.value = null
}

const assignNurse = async () => {
  if (!actioningRequest.value) return
  
  saving.value = true
  try {
    const response = await careRequestsService.assignNurse(
      actioningRequest.value.id,
      assignNurseForm.value
    )
    
    if (response.success) {
      await loadCareRequests(currentPage.value)
      await loadStatistics()
      closeAssignNurseModal()
      toast.showSuccess('Nurse assigned successfully!')
    }
  } catch (error) {
    toast.showError(error.message || 'Failed to assign nurse')
  }
  saving.value = false
}

const openScheduleAssessmentModal = (request) => {
  actioningRequest.value = request
  scheduleForm.value = { scheduled_at: '' }
  showScheduleModal.value = true
  activeDropdown.value = null
}

const closeScheduleModal = () => {
  showScheduleModal.value = false
  actioningRequest.value = null
}

const scheduleAssessment = async () => {
  if (!actioningRequest.value) return
  
  saving.value = true
  try {
    const response = await careRequestsService.scheduleAssessment(
      actioningRequest.value.id,
      scheduleForm.value
    )
    
    if (response.success) {
      await loadCareRequests(currentPage.value)
      await loadStatistics()
      closeScheduleModal()
      toast.showSuccess('Assessment scheduled successfully!')
    }
  } catch (error) {
    toast.showError(error.message || 'Failed to schedule assessment')
  }
  saving.value = false
}

const openIssueCareCostModal = (request) => {
  actioningRequest.value = request
  careCostForm.value = {
    amount: null,
    care_plan_details: '',
    duration_days: null,
    sessions_per_week: null
  }
  showIssueCareCostModal.value = true
  activeDropdown.value = null
}

const closeIssueCareCostModal = () => {
  showIssueCareCostModal.value = false
  actioningRequest.value = null
}

const issueCareCost = async () => {
  if (!actioningRequest.value) return
  
  saving.value = true
  try {
    const response = await careRequestsService.issueCareCost(
      actioningRequest.value.id,
      careCostForm.value
    )
    
    if (response.success) {
      await loadCareRequests(currentPage.value)
      await loadStatistics()
      closeIssueCareCostModal()
      toast.showSuccess('Care cost issued successfully!')
    }
  } catch (error) {
    toast.showError(error.message || 'Failed to issue care cost')
  }
  saving.value = false
}

const openStartCareModal = (request) => {
  actioningRequest.value = request
  showStartCareModal.value = true
  activeDropdown.value = null
}

const closeStartCareModal = () => {
  showStartCareModal.value = false
  actioningRequest.value = null
}

const startCare = async () => {
  if (!actioningRequest.value) return
  
  saving.value = true
  try {
    const response = await careRequestsService.startCare(actioningRequest.value.id)
    
    if (response.success) {
      await loadCareRequests(currentPage.value)
      await loadStatistics()
      closeStartCareModal()
      toast.showSuccess('Care services started successfully!')
    }
  } catch (error) {
    toast.showError(error.message || 'Failed to start care')
  }
  saving.value = false
}

const openRejectModal = (request) => {
  actioningRequest.value = request
  rejectForm.value = { reason: '' }
  showRejectModal.value = true
  activeDropdown.value = null
}

const closeRejectModal = () => {
  showRejectModal.value = false
  actioningRequest.value = null
}

const rejectRequest = async () => {
  if (!actioningRequest.value) return
  
  saving.value = true
  try {
    const response = await careRequestsService.rejectRequest(
      actioningRequest.value.id,
      rejectForm.value
    )
    
    if (response.success) {
      await loadCareRequests(currentPage.value)
      await loadStatistics()
      closeRejectModal()
      toast.showSuccess('Request rejected successfully')
    }
  } catch (error) {
    toast.showError(error.message || 'Failed to reject request')
  }
  saving.value = false
}

const refreshRequests = () => {
  loadCareRequests(currentPage.value)
  loadStatistics()
}

const exportCareRequests = async () => {
  try {
    const filters = {
      status: statusFilter.value,
      care_type: careTypeFilter.value,
      urgency_level: urgencyFilter.value,
      search: searchQuery.value
    }
    
    const { blob, filename } = await careRequestsService.exportCareRequests(filters)
    
    const downloadUrl = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = downloadUrl
    link.download = filename
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(downloadUrl)
    
    toast.showSuccess('Care requests exported successfully!')
  } catch (error) {
    toast.showError(error.message || 'Failed to export care requests')
  }
}

// Utility functions
const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatDateTime = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatCareType = (type) => {
  if (!type) return ''
  return type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatStatus = (status) => {
  if (!status) return ''
  return status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatUrgency = (urgency) => {
  if (!urgency) return ''
  return urgency.charAt(0).toUpperCase() + urgency.slice(1)
}

const formatPaymentStatus = (status) => {
  if (!status) return ''
  return status.charAt(0).toUpperCase() + status.slice(1)
}

const formatPreferredTime = (time) => {
  if (!time) return ''
  return time.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatMoney = (amount) => {
  return new Intl.NumberFormat('en-GH').format(amount || 0)
}

const generateAvatar = (user) => {
  if (!user) return ''
  const name = `${user.first_name || ''} ${user.last_name || ''}`
  return `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&color=667eea&background=f8f9fa&size=200&font-size=0.6`
}

const getStatusBadgeClass = (status) => {
  const classes = {
    'pending_payment': 'badge-warning',
    'payment_received': 'badge-success',
    'nurse_assigned': 'badge-info',
    'assessment_scheduled': 'badge-info',
    'assessment_completed': 'badge-success',
    'under_review': 'badge-warning',
    'awaiting_care_payment': 'badge-warning',
    'care_payment_received': 'badge-success',
    'care_active': 'badge-success',
    'completed': 'badge-secondary',
    'cancelled': 'badge-danger',
    'rejected': 'badge-danger'
  }
  return classes[status] || 'badge-secondary'
}

const getUrgencyBadgeClass = (urgency) => {
  const classes = {
    'routine': 'badge-secondary',
    'urgent': 'badge-warning',
    'emergency': 'badge-danger'
  }
  return classes[urgency] || 'badge-secondary'
}

const getCareTypeBadgeClass = () => {
  return 'badge-primary'
}

const getPaymentStatusClass = (status) => {
  const classes = {
    'pending': 'payment-pending',
    'processing': 'payment-processing',
    'completed': 'payment-completed',
    'failed': 'payment-failed'
  }
  return classes[status] || ''
}

const canReject = (request) => {
  const rejectableStatuses = ['payment_received', 'nurse_assigned', 'assessment_scheduled']
  return rejectableStatuses.includes(request.status)
}

const isStatusCompleted = (request, statusCheck) => {
  const statusOrder = [
    'pending_payment',
    'payment_received',
    'nurse_assigned',
    'assessment_scheduled',
    'assessment_completed',
    'under_review',
    'awaiting_care_payment',
    'care_payment_received',
    'care_active',
    'completed'
  ]
  
  const currentIndex = statusOrder.indexOf(request.status)
  const checkIndex = statusOrder.indexOf(statusCheck)
  
  return currentIndex >= checkIndex
}

// Pagination
const goToPage = (page) => {
  if (page >= 1 && page <= lastPage.value) {
    loadCareRequests(page)
  }
}

const nextPage = () => {
  if (currentPage.value < lastPage.value) {
    goToPage(currentPage.value + 1)
  }
}

const prevPage = () => {
  if (currentPage.value > 1) {
    goToPage(currentPage.value - 1)
  }
}

const getPaginationPages = () => {
  const pages = []
  const maxVisible = 5
  let start = Math.max(1, currentPage.value - Math.floor(maxVisible / 2))
  let end = Math.min(lastPage.value, start + maxVisible - 1)
  
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

// Watchers
let searchDebounceTimer = null

watch(searchQuery, () => {
  clearTimeout(searchDebounceTimer)
  searchDebounceTimer = setTimeout(() => {
    currentPage.value = 1
    loadCareRequests(1)
  }, 500)
})

watch([statusFilter, careTypeFilter, urgencyFilter], () => {
  currentPage.value = 1
  loadCareRequests(1)
})

// Lifecycle
onMounted(async () => {
  await Promise.all([
    loadCareRequests(),
    loadStatistics()
  ])
  
  document.addEventListener('click', handleClickOutside)
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

/* Copy all the styles from the Care Plans page */
/* [All the CSS from your provided code should go here] */

/* Additional styles specific to care requests */
.id-cell {
  font-weight: 600;
  color: #667eea;
  font-size: 14px;
}

.date-cell {
  font-size: 13px;
  color: #64748b;
}

.nurse-cell {
  display: flex;
  align-items: center;
  gap: 8px;
}

.payment-status-cell {
  display: flex;
  flex-direction: column;
  gap: 6px;
  min-width: 150px;
}

.payment-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
}

.payment-label {
  color: #64748b;
  font-weight: 500;
}

.payment-badge {
  padding: 4px 8px;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 600;
}

.payment-badge.payment-pending {
  background: #fef3c7;
  color: #92400e;
}

.payment-badge.payment-processing {
  background: #dbeafe;
  color: #1e40af;
}

.payment-badge.payment-completed {
  background: #d1fae5;
  color: #065f46;
}

.payment-badge.payment-failed {
  background: #fee2e2;
  color: #991b1b;
}

.no-payment {
  color: #94a3b8;
}

.request-icon-large {
  width: 80px;
  height: 80px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 16px;
}

.request-icon-large svg {
  width: 40px;
  height: 40px;
  color: white;
}

.request-patient-info {
  font-size: 14px;
  color: #64748b;
  margin-bottom: 16px;
}

.request-badges-view {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  justify-content: center;
  margin-bottom: 20px;
}

.payment-details {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.payment-detail-item {
  background: white;
  padding: 16px;
  border-radius: 12px;
  border-left: 4px solid #667eea;
}

.payment-detail-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.payment-type {
  font-weight: 600;
  color: #0f172a;
  font-size: 14px;
}

.payment-amount {
  font-size: 24px;
  font-weight: 700;
  color: #667eea;
  margin-bottom: 4px;
}

.payment-ref,
.payment-description {
  font-size: 12px;
  color: #64748b;
}

.no-payment-info {
  text-align: center;
  padding: 32px;
  color: #94a3b8;
  font-style: italic;
}

.timeline {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.timeline-item {
  display: flex;
  gap: 16px;
  position: relative;
}

.timeline-item:not(:last-child)::before {
  content: '';
  position: absolute;
  left: 11px;
  top: 28px;
  bottom: -20px;
  width: 2px;
  background: #e2e8f0;
}

.timeline-marker {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  border: 3px solid #e2e8f0;
  background: white;
  flex-shrink: 0;
  position: relative;
  z-index: 1;
}

.timeline-marker.completed {
  background: #667eea;
  border-color: #667eea;
}

.timeline-content {
  flex: 1;
  padding-top: 2px;
}

.timeline-title {
  font-weight: 600;
  color: #0f172a;
  margin-bottom: 4px;
}

.timeline-date {
  font-size: 13px;
  color: #64748b;
}

.stat-icon.orange {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.modal-md {
  max-width: 600px;
}

/* Include all other styles from the Care Plans page */
.users-page {
  padding: 32px;
  background: #f8fafc;
  min-height: 100vh;
}

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
  color: #64748b;
}

/* Continue with all remaining styles from Care Plans page... */
/* This is a condensed version - include all styles from your original code */

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

.users-table-container {
  background: white;
  border-radius: 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  border: 1px solid #f1f5f9;
  overflow: visible;
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

.nurse-avatar-small {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #e2e8f0;
  flex-shrink: 0;
}

.nurse-info-small {
  flex: 1;
}

.nurse-name-small {
  font-size: 13px;
  font-weight: 500;
  color: #334155;
}

.nurse-role-small {
  font-size: 11px;
  color: #94a3b8;
}

.no-nurse-assigned {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  color: #94a3b8;
}

.no-nurse-assigned svg {
  width: 16px;
  height: 16px;
}

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

/* Include all pagination, modal, form, and other styles from Care Plans page */
/* [Add all remaining styles...] */

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

.form-grid-full {
  grid-column: 1 / -1;
}

.required {
  color: #ef4444;
  font-weight: 700;
  margin-left: 2px;
}

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

.profile-name-view {
  font-size: 20px;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 8px 0;
  letter-spacing: -0.4px;
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

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 10px 20px;
  border: none;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
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

/* Medium-Large Screens (13-15 inch laptops: 1366px - 1440px) */
@media (max-width: 1440px) {
  .progress-notes-page {
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

  .modal-icon {
    width: 22px;
    height: 22px;
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

  .form-section-title {
    font-size: 16px;
  }

  .note-header-card {
    padding: 18px;
  }

  .note-section {
    padding: 18px;
  }

  .section-title {
    font-size: 15px;
  }
}

/* Smaller Laptops (1200px - 1366px) */
@media (max-width: 1366px) {
  .progress-notes-page {
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
}

/* Tablets and Small Laptops (1024px and below) */
@media (max-width: 1024px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .filters-group {
    flex-wrap: wrap;
  }

  .filter-select {
    min-width: 120px;
  }
}

/* Mobile (768px and below) */
@media (max-width: 768px) {
  .progress-notes-page {
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
  
  .stats-grid {
    grid-template-columns: 1fr;
    gap: 14px;
    padding: 0;
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
    min-width: 900px;
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

  .patient-nurse-info {
    grid-template-columns: 1fr;
  }

  .vitals-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .observations-grid {
    grid-template-columns: 1fr;
  }

  .signature-section {
    grid-template-columns: 1fr;
  }
}

/* Small Mobile (480px and below) */
@media (max-width: 480px) {
  .progress-notes-page {
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

  .modal-icon {
    width: 20px;
    height: 20px;
  }

  .modern-table {
    min-width: 800px;
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

  .form-section-title {
    font-size: 15px;
  }

  .checkbox-text {
    font-size: 13px;
  }

  .intervention-input {
    font-size: 12px;
    padding: 8px 10px;
  }

  .note-header-card,
  .note-section {
    padding: 16px;
  }

  .section-title {
    font-size: 14px;
  }

  .vitals-grid {
    grid-template-columns: 1fr;
  }

  .intervention-item {
    padding: 10px;
  }

  .intervention-item strong {
    font-size: 12px;
  }

  .intervention-item span {
    font-size: 13px;
  }
}

/* Extra Small (360px and below) */
@media (max-width: 360px) {
  .progress-notes-page {
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
    min-width: 700px;
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