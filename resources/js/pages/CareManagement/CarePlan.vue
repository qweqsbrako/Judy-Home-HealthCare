<template>
  <MainLayout>
    <div class="users-page">
      <!-- Page Header -->
      <div class="page-header">
        <div class="page-header-content">
          <h1>Care Plans Management</h1>
          <p>Create, manage, and track patient care plans with nurse assignments</p>
        </div>
        <div class="page-header-actions">
          <button @click="exportCarePlans" class="btn-modern btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Export
          </button>
          <button @click="openCreateModal" class="btn-modern btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Create Care Plan
          </button>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon blue">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Total Plans</div>
            <div class="stat-value">{{ stats.total_plans }}</div>
            <div class="stat-change positive">All care plans</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon green">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Active Plans</div>
            <div class="stat-value">{{ stats.active_plans }}</div>
            <div class="stat-change positive">Currently active</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon yellow">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Pending Approval</div>
            <div class="stat-value">{{ stats.pending_approval }}</div>
            <div class="stat-change neutral">Awaiting approval</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon purple">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Avg. Completion</div>
            <div class="stat-value">{{ Math.round(stats.completion_rates?.average || 0) }}%</div>
            <div class="stat-change positive">Completion rate</div>
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
            placeholder="Search care plans, patients, nurses, or doctors..."
            v-model="searchQuery"
            class="search-input"
          />
        </div>
        <div class="filters-group">
          <select v-model="statusFilter" class="filter-select">
            <option value="all">All Status</option>
            <option value="draft">Draft</option>
            <option value="pending_approval">Pending Approval</option>
            <!-- <option value="approved">Approved</option> -->
            <option value="active">Active</option>
            <!-- <option value="on_hold">On Hold</option> -->
            <option value="completed">Completed</option>
            <!-- <option value="cancelled">Cancelled</option> -->
          </select>
          <select v-model="careTypeFilter" class="filter-select">
            <option value="all">All Care Types</option>
            <option value="general_care">General Care</option>
            <option value="elderly_care">Elderly Care</option>
            <option value="pediatric_care">Pediatric Care</option>
            <option value="chronic_disease_management">Chronic Disease Management</option>
            <option value="palliative_care">Palliative Care</option>
            <option value="rehabilitation_care">Rehabilitation Care</option>
          </select>

          <select v-model="priorityFilter" class="filter-select">
            <option value="all">All Priorities</option>
            <option value="low">Low</option>
            <option value="medium">Medium</option>
            <option value="high">High</option>
            <option value="critical">Critical</option>
          </select>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="loading-state">
        <div class="loading-spinner"></div>
        <p class="loading-text">Loading care plans...</p>
      </div>

      <!-- Care Plans Table -->
      <div v-else class="users-table-container">
        <div class="table-wrapper">
          <table class="modern-table">
            <thead>
              <tr>
                <th>Patient</th>
                <th>Care Plan</th>
                <!-- <th>Doctor</th> -->
                <th>Assigned Nurses</th>
                <th>Care Type</th>
                <th>Status</th>
                <!-- <th>Priority</th> -->
                <!-- <th>Progress</th> -->
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="plan in carePlans" :key="plan.id">
                <td>
                  <div class="user-cell">
                    <img :src="plan.patient.avatar_url || generateAvatar(plan.patient)" :alt="plan.patient.first_name" class="user-avatar-table" />
                    <div class="user-details-table">
                      <div class="user-name-table">{{ plan.patient.first_name }} {{ plan.patient.last_name }}</div>
                      <div class="user-id-table">#{{ plan.patient.id }}</div>
                    </div>
                  </div>
                </td>
                
                <td>
                  <div class="contact-cell">
                    <div class="contact-primary">{{ plan.title }}</div>
                    <div class="contact-secondary">{{ truncateText(plan.description, 50) }}</div>
                  </div>
                </td>
                
                <!-- <td>
                  <div class="contact-cell">
                      <template v-if="plan.doctor">
                        <div class="contact-primary">Dr. {{ plan.doctor.first_name }} {{ plan.doctor.last_name }}</div>
                        <div class="contact-secondary">{{ formatSpecialization(plan.doctor.specialization) }}</div>
                      </template>
                      <template v-else>
                        <div class="contact-primary" style="color: #94a3b8; font-style: italic;">No doctor assigned</div>
                        <div class="contact-secondary">—</div>
                      </template>
                    </div>
                </td> -->
                
                <td>
                  <div class="nurses-cell">
                    <div v-if="plan.primary_nurse" class="nurse-item">
                      <img :src="plan.primary_nurse.avatar_url || generateAvatar(plan.primary_nurse)" :alt="plan.primary_nurse.first_name" class="nurse-avatar-small" />
                      <div class="nurse-info-small">
                        <div class="nurse-name-small">{{ plan.primary_nurse.first_name }} {{ plan.primary_nurse.last_name }}</div>
                        <div class="nurse-role-small">Primary</div>
                      </div>
                    </div>
                    <div v-if="plan.secondary_nurse" class="nurse-item">
                      <img :src="plan.secondary_nurse.avatar_url || generateAvatar(plan.secondary_nurse)" :alt="plan.secondary_nurse.first_name" class="nurse-avatar-small" />
                      <div class="nurse-info-small">
                        <div class="nurse-name-small">{{ plan.secondary_nurse.first_name }} {{ plan.secondary_nurse.last_name }}</div>
                        <div class="nurse-role-small">Secondary</div>
                      </div>
                    </div>
                    <div v-if="!plan.primary_nurse" class="no-nurse-assigned">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                      </svg>
                      No nurse assigned
                    </div>
                  </div>
                </td>
                
                <td>
                  <span :class="'modern-badge ' + getCareTypeBadgeClass(plan.care_type)">
                    {{ formatCareType(plan.care_type) }}
                  </span>
                </td>
                
                <td>
                  <span :class="'modern-badge ' + getStatusBadgeClass(plan.status)">
                    {{ formatStatus(plan.status) }}
                  </span>
                </td>
                
                <!-- <td>
                  <span :class="'modern-badge ' + getPriorityBadgeClass(plan.priority)">
                    {{ formatPriority(plan.priority) }}
                  </span>
                </td> -->
                
                <!-- <td>
                  <div class="progress-cell">
                    <div class="progress-bar-small">
                      <div class="progress-fill-small" :style="`width: ${plan.completion_percentage}%`"></div>
                    </div>
                    <span class="progress-text-small">{{ plan.completion_percentage }}%</span>
                  </div>
                </td> -->
                
                <td>
                  <div class="action-cell">
                    <button @click="toggleDropdown(plan.id)" class="action-btn">
                      <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                      </svg>
                    </button>
                    <div v-show="activeDropdown === plan.id" class="modern-dropdown">
                      <button @click="viewPlan(plan)" class="dropdown-item-modern">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        View Details
                      </button>
                      
                      <button v-if="canEditPlan(plan)" @click="editPlan(plan)" class="dropdown-item-modern">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Edit Plan
                      </button>
                      
                      <button v-if="plan.status === 'draft'" @click="openSubmitForApprovalModal(plan)" class="dropdown-item-modern success">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Submit for Approval
                      </button>
                      
                      <button v-if="plan.status === 'pending_approval'" @click="openApprovalModal(plan)" class="dropdown-item-modern success">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Approve Plan
                      </button>
                      
                      <button v-if="plan.status === 'active'" @click="openCompleteModal(plan)" class="dropdown-item-modern success">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Mark Complete
                      </button>
                      
                      <div class="dropdown-divider"></div>
                      
                      <button v-if="['draft', 'pending_approval'].includes(plan.status)" @click="openDeleteModal(plan)" class="dropdown-item-modern danger">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Delete Plan
                      </button>
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <div v-if="carePlans.length === 0" class="empty-state">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
          </svg>
          <h3>No care plans found</h3>
          <p>Start by creating a new care plan for your patients.</p>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="lastPage > 1" class="pagination-container">
        <div class="pagination-info">
          Showing {{ Math.min((currentPage - 1) * perPage + 1, total) }} to {{ Math.min(currentPage * perPage, total) }} of {{ total }} care plans
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

      <!-- Create/Edit Modal -->
      <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
        <div class="modal modal-lg">
          <div class="modal-header">
            <h2 class="modal-title">
              {{ isEditing ? 'Edit Care Plan' : 'Create New Care Plan' }}
            </h2>
            <button @click="closeModal" class="modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <form @submit.prevent="savePlan">
            <div class="modal-body">
              <div class="form-grid">
                <!-- Basic Information -->
                <div class="form-section-header">
                  <h3 class="form-section-title">Basic Information</h3>
                </div>
                
                <div class="form-group">
                  <label>Patient <span class="required">*</span></label>
                  <SearchableSelect
                    v-model="form.patient_id"
                    :options="patientOptions"
                    placeholder="Select Patient"
                    :disabled="isEditing"
                  />
                </div>

                <div class="form-group">
                  <label>Doctor <span class="required">*</span></label>
                  <SearchableSelect
                    v-model="form.doctor_id"
                    :options="doctorOptions"
                    placeholder="Select Doctor"
                  />
                </div>

                <div class="form-group form-grid-full">
                  <label>Care Plan Title <span class="required">*</span></label>
                  <input v-model="form.title" type="text" placeholder="Enter care plan title" required />
                </div>

                <div class="form-group form-grid-full">
                  <label>Description <span class="required">*</span></label>
                  <textarea v-model="form.description" rows="3" placeholder="Describe the care plan objectives and overview" required></textarea>
                </div>

                <!-- Nurse Assignment -->
                <div class="form-section-header">
                  <h3 class="form-section-title">Nurse Assignment</h3>
                </div>
                
                <div class="form-group">
                  <label>Primary Nurse</label>
                  <SearchableSelect
                    v-model="form.primary_nurse_id"
                    :options="nurseOptions"
                    placeholder="Select Primary Nurse"
                  />
                </div>

                <div class="form-group">
                  <label>Secondary Nurse</label>
                  <SearchableSelect
                    v-model="form.secondary_nurse_id"
                    :options="secondaryNurseOptions"
                    placeholder="Select Secondary Nurse (Optional)"
                  />
                </div>

                <div class="form-group">
                  <label>Estimated Hours per Day</label>
                  <input v-model.number="form.estimated_hours_per_day" type="number" min="1" max="24" placeholder="Hours per day" />
                </div>

                <div class="form-group form-grid-full">
                  <label>Assignment Notes</label>
                  <textarea v-model="form.assignment_notes" rows="2" placeholder="Special instructions for assigned nurses"></textarea>
                </div>

                <!-- Care Details -->
                <div class="form-section-header">
                  <h3 class="form-section-title">Care Details</h3>
                </div>
                
                <div class="form-group">
                  <label>Care Type <span class="required">*</span></label>
                  <SearchableSelect
                    v-model="form.care_type"
                    :options="careTypeOptions"
                    placeholder="Select Care Type"
                  />
                </div>

                <div class="form-group">
                  <label>Priority <span class="required">*</span></label>
                  <SearchableSelect
                    v-model="form.priority"
                    :options="priorityOptions"
                    placeholder="Select Priority"
                  />
                </div>

                <div class="form-group">
                  <label>Start Date <span class="required">*</span></label>
                  <input v-model="form.start_date" type="date" required />
                </div>

                <div class="form-group">
                  <label>End Date</label>
                  <input v-model="form.end_date" type="date" />
                </div>

                <div class="form-group">
                  <label>Frequency <span class="required">*</span></label>
                  <SearchableSelect
                    v-model="form.frequency"
                    :options="frequencyOptions"
                    placeholder="Select Frequency"
                  />
                </div>

                <div v-if="form.frequency === 'custom'" class="form-group form-grid-full">
                  <label>Custom Frequency Details</label>
                  <textarea v-model="form.custom_frequency_details" rows="2" placeholder="Describe the custom frequency schedule"></textarea>
                </div>

                <!-- Care Tasks -->
                <div class="form-section-header">
                  <h3 class="form-section-title">Care Tasks</h3>
                </div>
                
                <div class="form-group form-grid-full">
                  <div class="care-tasks-container">
                    <div v-for="(task, index) in form.care_tasks" :key="index" class="care-task-item">
                      <div class="task-number">{{ index + 1 }}</div>
                      <input 
                        v-model="form.care_tasks[index]" 
                        type="text" 
                        placeholder="Enter care task description..." 
                        class="task-input"
                        required 
                      />
                      <button 
                        @click="removeTask(index)" 
                        type="button" 
                        class="task-remove-btn"
                        :disabled="form.care_tasks.length === 1"
                      >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                      </button>
                    </div>
                    
                    <button @click="addTask" type="button" class="add-task-btn">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                      </svg>
                      Add Another Task
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <div class="modal-actions">
              <button type="button" @click="closeModal" class="btn btn-secondary">
                Cancel
              </button>
              <button type="submit" :disabled="saving" class="btn btn-primary">
                <div v-if="saving" class="spinner spinner-sm"></div>
                {{ isEditing ? 'Update Plan' : 'Create Plan' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- View Plan Modal -->
      <div v-if="showViewModal && selectedPlan" class="modal-overlay" @click.self="closeViewModal">
        <div class="modal modal-xl">
          <div class="modal-header">
            <h2 class="modal-title">Care Plan Details</h2>
            <button @click="closeViewModal" class="modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="modal-body">
            <div class="user-view-grid">
              <!-- Plan Profile Section -->
              <div class="user-profile-section">
                <div class="plan-icon-large">
                  <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                  </svg>
                </div>
                <h3 class="profile-name-view">{{ selectedPlan.title }}</h3>
                <p class="plan-patient-info">Patient: {{ selectedPlan.patient.first_name }} {{ selectedPlan.patient.last_name }}</p>
                
                <div class="plan-badges-view">
                  <span :class="'modern-badge ' + getStatusBadgeClass(selectedPlan.status)">
                    {{ formatStatus(selectedPlan.status) }}
                  </span>
                  <span :class="'modern-badge ' + getPriorityBadgeClass(selectedPlan.priority)">
                    {{ formatPriority(selectedPlan.priority) }}
                  </span>
                </div>

                <div class="progress-section-view">
                  <div class="progress-header-view">
                    <span>Progress</span>
                    <span class="progress-percentage-view">{{ selectedPlan.completion_percentage }}%</span>
                  </div>
                  <div class="progress-bar-large">
                    <div class="progress-fill-large" :style="`width: ${selectedPlan.completion_percentage}%`"></div>
                  </div>
                </div>
              </div>

              <!-- Details Section -->
              <div class="details-section-view">
                <!-- Basic Information -->
                <div class="details-group">
                  <h4 class="details-header">Plan Information</h4>
                  <div class="details-grid-view">
                    <div class="detail-item-view">
                      <label>Care Type</label>
                      <p>{{ formatCareType(selectedPlan.care_type) }}</p>
                    </div>
                    <div class="detail-item-view">
                      <label>Priority</label>
                      <span :class="'modern-badge ' + getPriorityBadgeClass(selectedPlan.priority)">
                        {{ formatPriority(selectedPlan.priority) }}
                      </span>
                    </div>
                    <div class="detail-item-view">
                      <label>Frequency</label>
                      <p>{{ formatCareType(selectedPlan.frequency) }}</p>
                    </div>
                    <div v-if="selectedPlan.estimated_hours_per_day" class="detail-item-view">
                      <label>Hours per Day</label>
                      <p>{{ selectedPlan.estimated_hours_per_day }} hours</p>
                    </div>
                  </div>

                  <div class="detail-item-view" style="margin-top: 16px;">
                    <label>Description</label>
                    <p>{{ selectedPlan.description }}</p>
                  </div>
                </div>

                <!-- Care Team -->
                <div class="details-group">
                  <h4 class="details-header">Care Team</h4>
                  <div class="details-grid-view">
                    <!-- Only show doctor section if doctor exists -->
                    <div v-if="selectedPlan.doctor" class="detail-item-view">
                      <label>Supervising Doctor</label>
                      <p>Dr. {{ selectedPlan.doctor.first_name }} {{ selectedPlan.doctor.last_name }}</p>
                      <p v-if="selectedPlan.doctor.specialization" style="font-size: 12px; color: #94a3b8;">
                        {{ formatSpecialization(selectedPlan.doctor.specialization) }}
                      </p>
                    </div>
                    
                    <!-- Optional: Show a message when no doctor is assigned -->
                    <div v-else class="detail-item-view">
                      <label>Supervising Doctor</label>
                      <p style="color: #94a3b8; font-style: italic;">Not assigned</p>
                    </div>
                    
                    <div v-if="selectedPlan.primary_nurse" class="detail-item-view">
                      <label>Primary Nurse</label>
                      <p>{{ selectedPlan.primary_nurse.first_name }} {{ selectedPlan.primary_nurse.last_name }}</p>
                      <p style="font-size: 12px; color: #94a3b8;">
                        {{ selectedPlan.primary_nurse.years_experience || 0 }} years experience
                      </p>
                    </div>
                    
                    <div v-if="selectedPlan.secondary_nurse" class="detail-item-view">
                      <label>Secondary Nurse</label>
                      <p>{{ selectedPlan.secondary_nurse.first_name }} {{ selectedPlan.secondary_nurse.last_name }}</p>
                    </div>
                  </div>
                </div>

                <!-- Schedule -->
                <div class="details-group">
                  <h4 class="details-header">Schedule</h4>
                  <div class="details-grid-view">
                    <div class="detail-item-view">
                      <label>Start Date</label>
                      <p>{{ formatDate(selectedPlan.start_date) }}</p>
                    </div>
                    <div class="detail-item-view">
                      <label>End Date</label>
                      <p>{{ selectedPlan.end_date ? formatDate(selectedPlan.end_date) : 'Ongoing' }}</p>
                    </div>
                  </div>
                </div>

                <!-- Care Tasks -->
                <div v-if="selectedPlan.care_tasks && selectedPlan.care_tasks.length" class="details-group">
                  <h4 class="details-header">Care Tasks</h4>
                  <div class="care-tasks-view">
                    <div v-for="(task, index) in selectedPlan.care_tasks" :key="index" class="care-task-view-item">
                      <div class="task-number-view">{{ index + 1 }}</div>
                      <div class="task-content-view">{{ task }}</div>
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
            <button v-if="canEditPlan(selectedPlan)" @click="editPlan(selectedPlan)" class="btn btn-primary">
              Edit Plan
            </button>
          </div>
        </div>
      </div>

      <!-- Confirmation Modals -->
      <div v-if="showSubmitForApprovalModal && actioningPlan" class="modal-overlay" @click.self="closeSubmitForApprovalModal">
        <div class="modal modal-sm">
          <div class="modal-header">
            <h3 class="modal-title">Submit for Approval</h3>
            <button @click="closeSubmitForApprovalModal" class="modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          
          <div class="modal-body">
            <p>Submit this care plan for approval?</p>
            <p style="font-size: 13px; color: #64748b; margin-top: 12px;">
              The plan will be reviewed by administrators before activation.
            </p>
          </div>

          <div class="modal-actions">
            <button @click="closeSubmitForApprovalModal" class="btn btn-secondary">Cancel</button>
            <button @click="submitForApproval" :disabled="saving" class="btn btn-primary">
              <div v-if="saving" class="spinner spinner-sm"></div>
              Submit
            </button>
          </div>
        </div>
      </div>

      <div v-if="showApprovalModal && actioningPlan" class="modal-overlay" @click.self="closeApprovalModal">
        <div class="modal modal-sm">
          <div class="modal-header">
            <h3 class="modal-title">Approve Care Plan</h3>
            <button @click="closeApprovalModal" class="modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          
          <div class="modal-body">
            <p>Approve this care plan?</p>
          </div>

          <div class="modal-actions">
            <button @click="closeApprovalModal" class="btn btn-secondary">Cancel</button>
            <button @click="approvePlan" :disabled="saving" class="btn btn-primary">
              <div v-if="saving" class="spinner spinner-sm"></div>
              Approve
            </button>
          </div>
        </div>
      </div>

      <div v-if="showCompleteModal && actioningPlan" class="modal-overlay" @click.self="closeCompleteModal">
        <div class="modal modal-sm">
          <div class="modal-header">
            <h3 class="modal-title">Complete Care Plan</h3>
            <button @click="closeCompleteModal" class="modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          
          <div class="modal-body">
            <p>Mark this care plan as complete?</p>
            <p style="font-size: 13px; color: #64748b; margin-top: 12px;">
              This action cannot be undone.
            </p>
          </div>

          <div class="modal-actions">
            <button @click="closeCompleteModal" class="btn btn-secondary">Cancel</button>
            <button @click="completePlan" :disabled="saving" class="btn btn-primary">
              <div v-if="saving" class="spinner spinner-sm"></div>
              Complete
            </button>
          </div>
        </div>
      </div>

      <div v-if="showDeleteModal && actioningPlan" class="modal-overlay" @click.self="closeDeleteModal">
        <div class="modal modal-sm">
          <div class="modal-header">
            <h3 class="modal-title">Delete Care Plan</h3>
            <button @click="closeDeleteModal" class="modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          
          <div class="modal-body">
            <p>Delete this care plan?</p>
            <p style="font-size: 13px; color: #ef4444; margin-top: 12px; font-weight: 500;">
              ⚠️ This action cannot be undone.
            </p>
          </div>

          <div class="modal-actions">
            <button @click="closeDeleteModal" class="btn btn-secondary">Cancel</button>
            <button @click="deletePlan" :disabled="saving" class="btn btn-danger">
              <div v-if="saving" class="spinner spinner-sm"></div>
              Delete
            </button>
          </div>
        </div>
      </div>
      
      <Toast />
    </div>
  </MainLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, inject, watch } from 'vue'
import { useRouter } from 'vue-router'
import MainLayout from '../../layout/MainLayout.vue'
import Toast from '../../common/components/Toast.vue'
import SearchableSelect from '../../common/components/SearchableSelect.vue'
import * as carePlansService from '../../services/carePlansService'

const router = useRouter()
const toast = inject('toast')

// Reactive data
const carePlans = ref([])
const loading = ref(true)
const saving = ref(false)
const searchQuery = ref('')
const statusFilter = ref('all')
const careTypeFilter = ref('all')
const priorityFilter = ref('all')
const activeDropdown = ref(null)

// Pagination state - FIXED
const currentPage = ref(1)
const lastPage = ref(1)
const perPage = ref(15)
const total = ref(0)

// Stats
const stats = ref({
  total_plans: 0,
  active_plans: 0,
  pending_approval: 0,
  completion_rates: { average: 0 }
})

// Modal states
const showModal = ref(false)
const showViewModal = ref(false)
const isEditing = ref(false)
const selectedPlan = ref(null)

// Confirmation modals
const showSubmitForApprovalModal = ref(false)
const showApprovalModal = ref(false)
const showCompleteModal = ref(false)
const showDeleteModal = ref(false)
const actioningPlan = ref(null)

// Data lists
const patients = ref([])
const doctors = ref([])
const nurses = ref([])

// Form data
const form = ref({
  patient_id: '',
  doctor_id: '',
  primary_nurse_id: '',
  secondary_nurse_id: '',
  title: '',
  description: '',
  care_type: '',
  priority: '',
  start_date: '',
  end_date: '',
  frequency: '',
  custom_frequency_details: '',
  care_tasks: [''],
  min_years_experience: 0,
  assignment_notes: '',
  assignment_type: '',
  estimated_hours_per_day: null
})

// Computed options
const patientOptions = computed(() => {
  if (!Array.isArray(patients.value)) return []
  return patients.value.map(patient => ({
    value: patient.id,
    label: `${patient.first_name} ${patient.last_name} - ${patient.email}`
  }))
})

const doctorOptions = computed(() => {
  if (!Array.isArray(doctors.value)) return []
  return doctors.value.map(doctor => ({
    value: doctor.id,
    label: `Dr. ${doctor.first_name} ${doctor.last_name}${doctor.specialization ? ` - ${formatSpecialization(doctor.specialization)}` : ''}`
  }))
})

const nurseOptions = computed(() => {
  if (!Array.isArray(nurses.value)) return []
  return nurses.value.map(nurse => ({
    value: nurse.id,
    label: `${nurse.first_name} ${nurse.last_name} - ${nurse.years_experience || 0}y exp`
  }))
})

const secondaryNurseOptions = computed(() => {
  if (!Array.isArray(nurses.value)) return []
  return nurses.value
    .filter(nurse => nurse.id !== form.value.primary_nurse_id)
    .map(nurse => ({
      value: nurse.id,
      label: `${nurse.first_name} ${nurse.last_name} - ${nurse.years_experience || 0}y exp`
    }))
})

const careTypeOptions = computed(() => [
  { value: 'general_care', label: 'General Care' },
  { value: 'elderly_care', label: 'Elderly Care' },
  { value: 'pediatric_care', label: 'Pediatric Care' },
  { value: 'chronic_disease_management', label: 'Chronic Disease Management' },
  { value: 'palliative_care', label: 'Palliative Care' },
  { value: 'rehabilitation_care', label: 'Rehabilitation Care' }
]);


const priorityOptions = computed(() => [
  { value: 'low', label: 'Low' },
  { value: 'medium', label: 'Medium' },
  { value: 'high', label: 'High' },
  { value: 'critical', label: 'Critical' }
])

const assignmentTypeOptions = computed(() => [
  { value: 'full_time', label: 'Full Time' },
  { value: 'part_time', label: 'Part Time' },
  { value: 'on_call', label: 'On Call' },
  { value: 'shift_based', label: 'Shift Based' }
])

const frequencyOptions = computed(() => [
  { value: 'once_daily', label: 'Once Daily' },
  { value: 'twice_daily', label: 'Twice Daily' },
  { value: 'three_times_daily', label: 'Three Times Daily' },
  { value: 'weekly', label: 'Weekly' },
  { value: 'twice_weekly', label: 'Twice Weekly' },
  { value: 'as_needed', label: 'As Needed' },
  { value: 'custom', label: 'Custom' }
])

// FIXED: Load care plans with proper pagination handling
const loadCarePlans = async (page = 1) => {
  loading.value = true
  try {
    const params = {
      page: page,
      per_page: perPage.value,
      search: searchQuery.value || '',
      status: statusFilter.value,
      care_type: careTypeFilter.value,
      priority: priorityFilter.value
    }
    
    console.log('Loading care plans with params:', params)
    
    const response = await carePlansService.getCarePlans(params)
    
    console.log('Care plans response:', response)
    
    if (response.success) {
      // FIXED: The paginated data is in response.data
      const paginatedData = response.data
      
      // Extract the care plans array
      carePlans.value = paginatedData.data || []
      
      // FIXED: Extract pagination info from the paginated response
      currentPage.value = paginatedData.current_page || 1
      lastPage.value = paginatedData.last_page || 1
      perPage.value = paginatedData.per_page || 15
      total.value = paginatedData.total || 0
      
      console.log('Pagination state:', {
        currentPage: currentPage.value,
        lastPage: lastPage.value,
        perPage: perPage.value,
        total: total.value
      })
    }
  } catch (error) {
    console.error('Error loading care plans:', error)
    toast.showError('Failed to load care plans. Please try again.')
  }
  loading.value = false
}

const loadStatistics = async () => {
  try {
    const response = await carePlansService.getStatistics()
    
    if (response.success) {
      stats.value = response.data || {}
    }
  } catch (error) {
    console.error('Error loading statistics:', error)
  }
}

const loadPatients = async () => {
  try {
    const response = await carePlansService.getPatients()
    
    if (response.success) {
      patients.value = response.data || []
    }
  } catch (error) {
    console.error('Error loading patients:', error)
    patients.value = []
  }
}

const loadDoctors = async () => {
  try {
    const response = await carePlansService.getDoctors()
    
    if (response.success) {
      doctors.value = response.data || []
    }
  } catch (error) {
    console.error('Error loading doctors:', error)
    doctors.value = []
  }
}

const loadNurses = async () => {
  try {
    const response = await carePlansService.getNurses()
    
    if (response.success) {
      nurses.value = response.data || []
    }
  } catch (error) {
    console.error('Error loading nurses:', error)
    nurses.value = []
  }
}

const toggleDropdown = (planId) => {
  activeDropdown.value = activeDropdown.value === planId ? null : planId
}

const openCreateModal = () => {
  isEditing.value = false
  resetForm()
  showModal.value = true
  loadPatients()
  loadDoctors()
  loadNurses()
}

const editPlan = (plan) => {
  isEditing.value = true
  selectedPlan.value = plan
  fillForm(plan)
  showModal.value = true
  showViewModal.value = false
  loadPatients()
  loadDoctors()
  loadNurses()
}

const viewPlan = (plan) => {
  selectedPlan.value = plan
  showViewModal.value = true
  activeDropdown.value = null
}

const closeModal = () => {
  showModal.value = false
  resetForm()
}

const closeViewModal = () => {
  showViewModal.value = false
  selectedPlan.value = null
}

const openSubmitForApprovalModal = (plan) => {
  actioningPlan.value = plan
  showSubmitForApprovalModal.value = true
  activeDropdown.value = null
}

const openApprovalModal = (plan) => {
  actioningPlan.value = plan
  showApprovalModal.value = true
  activeDropdown.value = null
}

const openCompleteModal = (plan) => {
  actioningPlan.value = plan
  showCompleteModal.value = true
  activeDropdown.value = null
}

const openDeleteModal = (plan) => {
  actioningPlan.value = plan
  showDeleteModal.value = true
  activeDropdown.value = null
}

const closeSubmitForApprovalModal = () => {
  showSubmitForApprovalModal.value = false
  actioningPlan.value = null
}

const closeApprovalModal = () => {
  showApprovalModal.value = false
  actioningPlan.value = null
}

const closeCompleteModal = () => {
  showCompleteModal.value = false
  actioningPlan.value = null
}

const closeDeleteModal = () => {
  showDeleteModal.value = false
  actioningPlan.value = null
}

const submitForApproval = async () => {
  if (!actioningPlan.value) return
  
  saving.value = true
  try {
    const response = await carePlansService.submitForApproval(actioningPlan.value.id)
    
    if (response.success) {
      await loadCarePlans(currentPage.value)
      await loadStatistics()
      closeSubmitForApprovalModal()
      toast.showSuccess('Care plan submitted for approval successfully!')
    }
  } catch (error) {
    console.error('Error submitting care plan:', error)
    toast.showError(error.message || 'Failed to submit care plan for approval.')
  }
  saving.value = false
}

const approvePlan = async () => {
  if (!actioningPlan.value) return
  
  saving.value = true
  try {
    const response = await carePlansService.approveCarePlan(actioningPlan.value.id)
    
    if (response.success) {
      await loadCarePlans(currentPage.value)
      await loadStatistics()
      closeApprovalModal()
      toast.showSuccess('Care plan approved successfully!')
    }
  } catch (error) {
    console.error('Error approving care plan:', error)
    toast.showError(error.message || 'Failed to approve care plan.')
  }
  saving.value = false
}

const completePlan = async () => {
  if (!actioningPlan.value) return
  
  saving.value = true
  try {
    const response = await carePlansService.completeCarePlan(actioningPlan.value.id)
    
    if (response.success) {
      await loadCarePlans(currentPage.value)
      await loadStatistics()
      closeCompleteModal()
      toast.showSuccess('Care plan completed successfully!')
    }
  } catch (error) {
    console.error('Error completing care plan:', error)
    toast.showError(error.message || 'Failed to complete care plan.')
  }
  saving.value = false
}

const deletePlan = async () => {
  if (!actioningPlan.value) return
  
  saving.value = true
  try {
    const response = await carePlansService.deleteCarePlan(actioningPlan.value.id)
    
    if (response.success) {
      await loadCarePlans(currentPage.value)
      await loadStatistics()
      closeDeleteModal()
      toast.showSuccess('Care plan deleted successfully!')
    }
  } catch (error) {
    console.error('Error deleting care plan:', error)
    toast.showError(error.message || 'Failed to delete care plan.')
  }
  saving.value = false
}

const resetForm = () => {
  form.value = {
    patient_id: '',
    doctor_id: '',
    primary_nurse_id: '',
    secondary_nurse_id: '',
    title: '',
    description: '',
    care_type: '',
    priority: '',
    start_date: '',
    end_date: '',
    frequency: '',
    custom_frequency_details: '',
    care_tasks: [''],
    min_years_experience: 0,
    assignment_notes: '',
    assignment_type: '',
    estimated_hours_per_day: null
  }
}

const fillForm = (plan) => {
  form.value = {
    patient_id: plan.patient_id,
    doctor_id: plan.doctor_id,
    primary_nurse_id: plan.primary_nurse_id || '',
    secondary_nurse_id: plan.secondary_nurse_id || '',
    title: plan.title || '',
    description: plan.description || '',
    care_type: plan.care_type || '',
    priority: plan.priority || '',
    start_date: plan.start_date ? plan.start_date.split('T')[0] : '',
    end_date: plan.end_date ? plan.end_date.split('T')[0] : '',
    frequency: plan.frequency || '',
    custom_frequency_details: plan.custom_frequency_details || '',
    care_tasks: Array.isArray(plan.care_tasks) && plan.care_tasks.length > 0 ? plan.care_tasks : [''],
    min_years_experience: plan.min_years_experience || 0,
    assignment_notes: plan.assignment_notes || '',
    assignment_type: plan.assignment_type || '',
    estimated_hours_per_day: plan.estimated_hours_per_day || null
  }
}

const addTask = () => {
  form.value.care_tasks.push('')
}

const removeTask = (index) => {
  if (form.value.care_tasks.length > 1) {
    form.value.care_tasks.splice(index, 1)
  }
}

const savePlan = async () => {
  saving.value = true
  try {
    const response = isEditing.value 
      ? await carePlansService.updateCarePlan(selectedPlan.value.id, form.value)
      : await carePlansService.createCarePlan(form.value)
    
    if (response.success) {
      closeModal()
      await loadCarePlans(currentPage.value)
      await loadStatistics()
      toast.showSuccess(
        isEditing.value 
          ? 'Care plan updated successfully!' 
          : 'Care plan created successfully!'
      )
    }
  } catch (error) {
    console.error('Error saving care plan:', error)
    toast.showError(error.message || 'Failed to save care plan. Please try again.')
  }
  saving.value = false
}

const exportCarePlans = async () => {
  try {
    const filters = {
      status: statusFilter.value,
      care_type: careTypeFilter.value,
      priority: priorityFilter.value,
      search: searchQuery.value
    }
    
    const { blob, filename } = await carePlansService.exportCarePlans(filters)
    
    const downloadUrl = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = downloadUrl
    link.download = filename
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(downloadUrl)
    
    toast.showSuccess('Care plans exported successfully!')
  } catch (error) {
    console.error('Error exporting care plans:', error)
    toast.showError(error.message || 'Failed to export care plans. Please try again.')
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

const formatCareType = (type) => {
  if (!type) return ''
  return type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatStatus = (status) => {
  if (!status) return ''
  return status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatPriority = (priority) => {
  if (!priority) return ''
  return priority.charAt(0).toUpperCase() + priority.slice(1)
}

const formatSpecialization = (spec) => {
  if (!spec) return 'General'
  return spec.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const truncateText = (text, length) => {
  if (!text) return ''
  return text.length > length ? text.substring(0, length) + '...' : text
}

const generateAvatar = (user) => {
  const name = `${user.first_name || ''} ${user.last_name || ''}`
  return `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&color=667eea&background=f8f9fa&size=200&font-size=0.6`
}

const getStatusBadgeClass = (status) => {
  const classes = {
    'draft': 'badge-secondary',
    'pending_approval': 'badge-warning',
    'active': 'badge-success',
    'completed': 'badge-success',
  }
  return classes[status] || 'badge-secondary'
}

const getPriorityBadgeClass = (priority) => {
  const classes = {
    'low': 'badge-secondary',
    'medium': 'badge-info',
    'high': 'badge-warning',
    'critical': 'badge-danger'
  }
  return classes[priority] || 'badge-secondary'
}

const getCareTypeBadgeClass = () => {
  return 'badge-primary'
}

const canEditPlan = (plan) => {
  if (!plan) return false
  return ['draft', 'pending_approval'].includes(plan.status)
}

// FIXED: Pagination methods
const goToPage = (page) => {
  if (page >= 1 && page <= lastPage.value) {
    loadCarePlans(page)
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

// Debounce timer for search
let searchDebounceTimer = null

// Watch for search query changes with debounce
watch(searchQuery, () => {
  clearTimeout(searchDebounceTimer)
  searchDebounceTimer = setTimeout(() => {
    currentPage.value = 1
    loadCarePlans(1)
  }, 500)
})

// Watch for filter changes (instant reload)
watch([statusFilter, careTypeFilter, priorityFilter], () => {
  currentPage.value = 1
  loadCarePlans(1)
})

// Lifecycle
onMounted(async () => {
  try {
    await Promise.all([
      loadCarePlans(),
      loadStatistics()
    ])
    
    document.addEventListener('click', handleClickOutside)
  } catch (error) {
    console.error('Error during component mount:', error)
  }
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
  clearTimeout(searchDebounceTimer)
})
</script>

<style scoped>
/* Copy ALL styles from the original file - keeping them exactly the same */
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
  grid-template-columns: repeat(4, 1fr); /* Force 4 equal columns */
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
  color: #64748b;
}

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
  max-width: 100%;
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

.nurses-cell {
  min-width: 180px;
}

.nurse-item {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
}

.nurse-item:last-child {
  margin-bottom: 0;
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

.progress-cell {
  min-width: 100px;
}

.progress-bar-small {
  background: #e5e7eb;
  border-radius: 4px;
  overflow: hidden;
  height: 6px;
  margin-bottom: 4px;
}

.progress-fill-small {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  height: 100%;
  transition: width 0.3s ease;
}

.progress-text-small {
  font-size: 12px;
  color: #64748b;
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

.required {
  color: #ef4444;
  font-weight: 700;
  margin-left: 2px;
}

.care-tasks-container {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.care-task-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 12px;
  background: #f8fafc;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
}

.task-number {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  background: #667eea;
  color: white;
  border-radius: 50%;
  font-size: 14px;
  font-weight: 600;
  flex-shrink: 0;
}

.task-input {
  flex: 1;
  padding: 10px 14px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
}

.task-input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.task-remove-btn {
  width: 36px;
  height: 36px;
  background: #fee2e2;
  color: #dc2626;
  border: 1px solid #fecaca;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.task-remove-btn:hover:not(:disabled) {
  background: #fecaca;
  color: #b91c1c;
}

.task-remove-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.task-remove-btn svg {
  width: 18px;
  height: 18px;
}

.add-task-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 12px;
  background: white;
  border: 2px dashed #e2e8f0;
  border-radius: 10px;
  color: #64748b;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.add-task-btn:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
  color: #334155;
}

.add-task-btn svg {
  width: 18px;
  height: 18px;
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

.plan-icon-large {
  width: 80px;
  height: 80px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 16px;
}

.plan-icon-large svg {
  width: 40px;
  height: 40px;
  color: white;
}

.profile-name-view {
  font-size: 20px;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 8px 0;
  letter-spacing: -0.4px;
}

.plan-patient-info {
  font-size: 14px;
  color: #64748b;
  margin-bottom: 16px;
}

.plan-badges-view {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  justify-content: center;
  margin-bottom: 20px;
}

.progress-section-view {
  width: 100%;
  margin-top: 20px;
}

.progress-header-view {
  display: flex;
  justify-content: space-between;
  margin-bottom: 8px;
  font-size: 14px;
  font-weight: 600;
  color: #334155;
}

.progress-percentage-view {
  color: #10b981;
}

.progress-bar-large {
  background: #e5e7eb;
  border-radius: 8px;
  overflow: hidden;
  height: 12px;
}

.progress-fill-large {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  height: 100%;
  transition: width 0.3s ease;
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

.care-tasks-view {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.care-task-view-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 12px;
  background: white;
  border-radius: 10px;
  border-left: 4px solid #667eea;
}

.task-number-view {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  background: #667eea;
  color: white;
  border-radius: 50%;
  font-size: 12px;
  font-weight: 600;
  flex-shrink: 0;
}

.task-content-view {
  flex: 1;
  color: #334155;
  line-height: 1.5;
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

@media (max-width: 1024px) {
  .user-view-grid {
    grid-template-columns: 1fr;
  }
  
  .form-grid {
    grid-template-columns: 1fr;
  }
}


@media (max-width: 1200px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr); /* 2 columns on tablets */
    width: 95%;
  }

  .filters-section{
    width: 95%;
  }
  
}

@media (max-width: 1440px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr); 
    width: 95%;
  }
  .filters-section{
    width: 95%;
  }
}

@media (max-width: 768px) {
  .users-page {
    padding: 16px;
  }
  
  .page-header {
    flex-direction: column;
    gap: 16px;
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
  
  .details-grid-view {
    grid-template-columns: 1fr;
  }
}
</style>