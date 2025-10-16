<template>
  <MainLayout>
    <div class="care-assignments-page">
      <!-- Page Header -->
      <div class="page-header">
        <div class="header-content">
          <h1 class="page-title">Care Assignments</h1>
          <p class="page-subtitle">Assign nurses to patient care plans and track assignment progress</p>
        </div>
        <div class="header-actions">
        <button @click="openNurseMatchingModal" class="btn btn-secondary">
            <SearchIcon class="w-4 h-4 mr-2" />
            Find Suitable Nurses
        </button>
        <button @click="openCreateModal" class="btn btn-primary">
            <PlusIcon class="w-4 h-4 mr-2" />
            Create Assignment
        </button>
        </div>

      </div>

      <!-- Statistics Cards -->
      <div class="stats-grid mb-8">
        <div class="stat-card">
          <div class="stat-content">
            <div class="stat-value">{{ statistics.total_assignments || 0 }}</div>
            <div class="stat-label">Total Assignments</div>
          </div>
          <div class="stat-icon bg-blue-100 text-blue-600">
            <UsersIcon class="w-6 h-6" />
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-content">
            <div class="stat-value">{{ statistics.active_assignments || 0 }}</div>
            <div class="stat-label">Active Assignments</div>
          </div>
          <div class="stat-icon bg-green-100 text-green-600">
            <CheckCircleIcon class="w-6 h-6" />
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-content">
            <div class="stat-value">{{ statistics.pending_assignments || 0 }}</div>
            <div class="stat-label">Pending Response</div>
          </div>
          <div class="stat-icon bg-yellow-100 text-yellow-600">
            <ClockIcon class="w-6 h-6" />
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-content">
            <div class="stat-value">{{ Math.round(statistics.response_times?.acceptance_rate || 0) }}%</div>
            <div class="stat-label">Acceptance Rate</div>
          </div>
          <div class="stat-icon bg-purple-100 text-purple-600">
            <TrendingUpIcon class="w-6 h-6" />
          </div>
        </div>
      </div>

      <!-- Filters Section -->
      <div class="filters-section">
        <div class="filters-content">
          <div class="search-wrapper">
            <SearchIcon class="search-icon" />
            <input
              v-model="filters.search"
              type="text"
              placeholder="Search assignments, patients, or nurses..."
              class="search-input"
              @input="debouncedSearch"
            />
          </div>
          
          <div class="filters-group">
            <select v-model="filters.status" class="filter-select" @change="loadAssignments">
              <option value="all">All Status</option>
              <option v-for="(label, value) in filterOptions.statuses" :key="value" :value="value">
                {{ label }}
              </option>
            </select>
            
            <select v-model="filters.assignment_type" class="filter-select" @change="loadAssignments">
              <option value="all">All Types</option>
              <option v-for="(label, value) in filterOptions.assignment_types" :key="value" :value="value">
                {{ label }}
              </option>
            </select>
            
            <select v-model="filters.priority_level" class="filter-select" @change="loadAssignments">
              <option value="all">All Priorities</option>
              <option value="low">Low Priority</option>
              <option value="medium">Medium Priority</option>
              <option value="high">High Priority</option>
              <option value="urgent">Urgent Priority</option>
            </select>

            <select v-model="filters.is_emergency" class="filter-select" @change="loadAssignments">
              <option value="all">All Assignments</option>
              <option value="true">Emergency Only</option>
              <option value="false">Regular Only</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="loading-state">
        <div class="loading-spinner"></div>
        <p class="loading-text">Loading care assignments...</p>
      </div>

      <!-- Assignments Table -->
      <div v-else class="assignments-table-container">
        <div v-if="assignments.data && assignments.data.length > 0" class="table-wrapper">
          <table class="assignments-table">
            <thead>
              <tr>
                <th>Patient & Care Plan</th>
                <th>Primary Nurse</th>
                <th>Assignment Type</th>
                <th>Status</th>
                <th>Priority</th>
                <th>Match Score</th>
                <th>Dates</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="assignment in assignments.data" :key="assignment.id" class="table-row">
                <td>
                  <div class="patient-care-info">
                    <div class="patient-info">
                      <div class="patient-name">{{ assignment.patient.first_name }} {{ assignment.patient.last_name }}</div>
                      <div class="care-plan-title">{{ truncateText(assignment.care_plan.title, 40) }}</div>
                    </div>
                    <div class="care-badges">
                      <span class="badge mini" :class="getCareTypeBadgeClass(assignment.care_plan.care_type)">
                        {{ formatCareType(assignment.care_plan.care_type) }}
                      </span>
                      <span v-if="assignment.is_emergency" class="badge mini badge-danger">
                        <AlertTriangleIcon class="w-3 h-3 mr-1" />
                        Emergency
                      </span>
                    </div>
                  </div>
                </td>
                
                <td>
                  <div class="nurse-info">
                    <div class="nurse-avatar">
                      <img :src="assignment.primary_nurse.avatar_url || generateAvatar(assignment.primary_nurse)" :alt="assignment.primary_nurse.first_name" />
                    </div>
                    <div class="nurse-details">
                      <div class="nurse-name">{{ assignment.primary_nurse.first_name }} {{ assignment.primary_nurse.last_name }}</div>
                      <div class="nurse-experience">{{ assignment.primary_nurse.years_experience || 0 }}y exp</div>
                      <div v-if="assignment.secondary_nurse" class="secondary-nurse">
                        +{{ assignment.secondary_nurse.first_name }} {{ assignment.secondary_nurse.last_name }}
                      </div>
                    </div>
                  </div>
                </td>
                
                <td>
                  <div class="assignment-type-info">
                    <span class="badge" :class="getAssignmentTypeBadgeClass(assignment.assignment_type)">
                      {{ formatAssignmentType(assignment.assignment_type) }}
                    </span>
                    <div class="intensity-level">{{ formatIntensityLevel(assignment.intensity_level) }}</div>
                  </div>
                </td>
                
                <td>
                  <div class="status-info">
                    <span class="badge" :class="getStatusBadgeClass(assignment.status)">
                      {{ formatStatus(assignment.status) }}
                    </span>
                    <div v-if="assignment.response_time_hours && isPendingResponse(assignment)" class="response-time">
                      {{ assignment.response_time_hours }}h response
                    </div>
                  </div>
                </td>
                
                <td>
                  <span class="badge" :class="getPriorityBadgeClass(assignment.priority_level)">
                    {{ formatPriority(assignment.priority_level) }}
                  </span>
                </td>
                
                <td>
                  <div class="match-score-info">
                    <div class="match-score">
                      <span class="score-value" :class="getMatchScoreClass(assignment.overall_match_score)">
                        {{ assignment.overall_match_score || 0 }}%
                      </span>
                    </div>
                    <div class="match-breakdown">
                      <div class="score-bar">
                        <div class="score-fill" :style="`width: ${assignment.overall_match_score || 0}%`"></div>
                      </div>
                    </div>
                  </div>
                </td>
                
                <td>
                  <div class="date-info">
                    <div class="start-date">{{ formatDate(assignment.start_date) }}</div>
                    <div v-if="assignment.end_date" class="end-date">to {{ formatDate(assignment.end_date) }}</div>
                    <div v-else class="end-date text-gray-500">Ongoing</div>
                  </div>
                </td>
                
                <td>
                  <div class="action-dropdown" style="position: relative;">
                    <button
                      @click.stop="toggleDropdown(assignment.id)"
                      class="btn btn-secondary btn-sm"
                      style="min-width: auto; padding: 0.5rem;"
                    >
                      <MoreVerticalIcon class="w-4 h-4" />
                    </button>
                    
                    <div v-show="activeDropdown === assignment.id" class="dropdown-menu" style="z-index: 9999; position: absolute; right: 0; top: 100%; background: white; border: 1px solid #ccc; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); min-width: 180px; margin-top: 8px;">
                      <button
                        @click.stop="viewAssignment(assignment)"
                        class="dropdown-item"
                        style="display: flex; align-items: center; width: 100%; padding: 8px 12px; background: none; border: none; text-align: left; cursor: pointer; color: #374151; text-decoration: none; border-radius: 4px;"
                        @mouseover="$event.target.style.backgroundColor = '#f9fafb'"
                        @mouseout="$event.target.style.backgroundColor = 'transparent'"
                      >
                        <EyeIcon style="width: 16px; height: 16px; margin-right: 8px;" />
                        View Details
                      </button>
                      
                      <button v-if="canEditAssignment(assignment)"
                        @click.stop="editAssignment(assignment)"
                        class="dropdown-item"
                        style="display: flex; align-items: center; width: 100%; padding: 8px 12px; background: none; border: none; text-align: left; cursor: pointer; color: #374151; text-decoration: none; border-radius: 4px;"
                        @mouseover="$event.target.style.backgroundColor = '#f9fafb'"
                        @mouseout="$event.target.style.backgroundColor = 'transparent'"
                      >
                        <EditIcon style="width: 16px; height: 16px; margin-right: 8px;" />
                        Edit Assignment
                      </button>
                      
                      <!-- Approve Assignment -->
                      <button 
                        @click.stop="openApprovalModal(assignment)"
                        class="dropdown-item"
                        style="display: flex; align-items: center; width: 100%; padding: 8px 12px; background: none; border: none; text-align: left; cursor: pointer; color: #0ea5e9; text-decoration: none; border-radius: 4px;"
                        @mouseover="$event.target.style.backgroundColor = '#f0f9ff'"
                        @mouseout="$event.target.style.backgroundColor = 'transparent'"
                      >
                        <CheckIcon style="width: 16px; height: 16px; margin-right: 8px;" />
                        Approve Assignment
                      </button>

                      <!-- Accept Assignment -->
                      <button
                        @click.stop="openAcceptModal(assignment)"
                        class="dropdown-item"
                        style="display: flex; align-items: center; width: 100%; padding: 8px 12px; background: none; border: none; text-align: left; cursor: pointer; color: #059669; text-decoration: none; border-radius: 4px;"
                        @mouseover="$event.target.style.backgroundColor = '#f0fdf4'"
                        @mouseout="$event.target.style.backgroundColor = 'transparent'"
                      >
                        <CheckIcon style="width: 16px; height: 16px; margin-right: 8px;" />
                        Accept Assignment
                      </button>
                      
                      <!-- Activate Assignment -->
                      <button
                        @click.stop="openActivateModal(assignment)"
                        class="dropdown-item"
                        style="display: flex; align-items: center; width: 100%; padding: 8px 12px; background: none; border: none; text-align: left; cursor: pointer; color: #059669; text-decoration: none; border-radius: 4px;"
                        @mouseover="$event.target.style.backgroundColor = '#f0fdf4'"
                        @mouseout="$event.target.style.backgroundColor = 'transparent'"
                      >
                        <PlayIcon style="width: 16px; height: 16px; margin-right: 8px;" />
                        Activate
                      </button>
                      
                      <!-- Reassign -->
                      <button 
                        @click.stop="openReassignModal(assignment)"
                        class="dropdown-item"
                        style="display: flex; align-items: center; width: 100%; padding: 8px 12px; background: none; border: none; text-align: left; cursor: pointer; color: #d97706; text-decoration: none; border-radius: 4px;"
                        @mouseover="$event.target.style.backgroundColor = '#fffbeb'"
                        @mouseout="$event.target.style.backgroundColor = 'transparent'"
                      >
                        <RefreshCcwIcon style="width: 16px; height: 16px; margin-right: 8px;" />
                        Reassign
                      </button>
                      
                      <!-- Complete Assignment -->
                      <button 
                        @click.stop="openCompleteModal(assignment)"
                        class="dropdown-item"
                        style="display: flex; align-items: center; width: 100%; padding: 8px 12px; background: none; border: none; text-align: left; cursor: pointer; color: #d97706; text-decoration: none; border-radius: 4px;"
                        @mouseover="$event.target.style.backgroundColor = '#fffbeb'"
                        @mouseout="$event.target.style.backgroundColor = 'transparent'"
                      >
                        <CheckCircleIcon style="width: 16px; height: 16px; margin-right: 8px;" />
                        Mark Complete
                      </button>
                      
                      <div style="height: 1px; background: #e5e7eb; margin: 4px 0;"></div>
                      
                      <!-- Delete -->
                      <button 
                        @click.stop="openDeleteModalWithConfirm(assignment)"
                        class="dropdown-item"
                        style="display: flex; align-items: center; width: 100%; padding: 8px 12px; background: none; border: none; text-align: left; cursor: pointer; color: #dc2626; text-decoration: none; border-radius: 4px;"
                        @mouseover="$event.target.style.backgroundColor = '#fef2f2'"
                        @mouseout="$event.target.style.backgroundColor = 'transparent'"
                      >
                        <TrashIcon style="width: 16px; height: 16px; margin-right: 8px;" />
                        Delete Assignment
                      </button>
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-else class="empty-state">
          <UsersIcon class="empty-icon" />
          <h3>No care assignments found</h3>
          <p>
            {{ (filters.search || filters.status !== 'all' || filters.assignment_type !== 'all' || filters.priority_level !== 'all') 
              ? 'Try adjusting your search or filters.' 
              : 'Start by creating assignments for approved care plans.' }}
          </p>
          <button v-if="!filters.search && filters.status === 'all'" @click="openCreateModal" class="btn btn-primary mt-4">
            Create First Assignment
          </button>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="assignments.data && assignments.data.length > 0" class="pagination-container">
        <div class="pagination-info">
          Showing {{ assignments.from }} to {{ assignments.to }} of {{ assignments.total }} results
        </div>
        <div class="pagination-controls">
          <button 
            @click="changePage(assignments.current_page - 1)" 
            :disabled="!assignments.prev_page_url"
            class="btn btn-secondary btn-sm"
          >
            Previous
          </button>
          <span class="pagination-current">Page {{ assignments.current_page }} of {{ assignments.last_page }}</span>
          <button 
            @click="changePage(assignments.current_page + 1)" 
            :disabled="!assignments.next_page_url"
            class="btn btn-secondary btn-sm"
          >
            Next
          </button>
        </div>
      </div>

      <!-- Create/Edit Modal -->
      <div v-if="showModal" class="modal-overlay">
        <div class="modal large">
          <div class="modal-header">
            <h2 class="modal-title">
              <UsersIcon class="modal-icon" />
              {{ isEditing ? 'Edit Care Assignment' : 'Create New Assignment' }}
            </h2>
            <button @click="closeModal" class="modal-close">
              <XIcon class="w-5 h-5" />
            </button>
          </div>

          <form @submit.prevent="saveAssignment" class="modal-body">
            <div class="form-container">
              <!-- Basic Information -->
              <div class="form-section">
                <h3 class="form-section-title">Assignment Information</h3>
                <div class="form-grid">
                  <div class="form-group">
                    <label>Care Plan *</label>
                    <SearchableSelect
                      v-if="carePlanOptions.length > 0"
                      v-model="form.care_plan_id"
                      :options="carePlanOptions"
                      placeholder="Select Care Plan"
                      :disabled="isEditing"
                    />
                    <select v-else v-model="form.care_plan_id" :disabled="isEditing" required>
                      <option value="">Loading care plans...</option>
                    </select>
                  </div>

                  <div class="form-group">
                    <label>Primary Nurse *</label>
                    <SearchableSelect
                      v-if="nurseOptions.length > 0"
                      v-model="form.primary_nurse_id"
                      :options="nurseOptions"
                      placeholder="Select Primary Nurse"
                      :disabled="isEditing"
                    />
                    <select v-else v-model="form.primary_nurse_id" :disabled="isEditing" required>
                      <option value="">Loading nurses...</option>
                    </select>
                  </div>

                  <div class="form-group">
                    <label>Secondary Nurse</label>
                    <SearchableSelect
                      v-if="secondaryNurseOptions.length > 0"
                      v-model="form.secondary_nurse_id"
                      :options="secondaryNurseOptions"
                      placeholder="Select Secondary Nurse (Optional)"
                    />
                    <select v-else v-model="form.secondary_nurse_id">
                      <option value="">No secondary nurse</option>
                    </select>
                  </div>

                  <div class="form-group">
                    <label>Assignment Type *</label>
                    <select v-model="form.assignment_type" required>
                      <option value="">Select Assignment Type</option>
                      <option v-for="(label, value) in filterOptions.assignment_types" :key="value" :value="value">
                        {{ label }}
                      </option>
                    </select>
                  </div>

                  <div class="form-group">
                    <label>Start Date *</label>
                    <input v-model="form.start_date" type="date" required />
                  </div>

                  <div class="form-group">
                    <label>End Date</label>
                    <input v-model="form.end_date" type="date" />
                  </div>
                </div>
              </div>

              <!-- Assignment Details -->
              <div class="form-section">
                <h3 class="form-section-title">Assignment Details</h3>
                <div class="form-grid">
                  <div class="form-group">
                    <label>Intensity Level *</label>
                    <select v-model="form.intensity_level" required>
                      <option value="">Select Intensity</option>
                      <option v-for="(label, value) in filterOptions.intensity_levels" :key="value" :value="value">
                        {{ label }}
                      </option>
                    </select>
                  </div>

                  <div class="form-group">
                    <label>Priority Level *</label>
                    <select v-model="form.priority_level" required>
                      <option value="">Select Priority</option>
                      <option value="low">Low Priority</option>
                      <option value="medium">Medium Priority</option>
                      <option value="high">High Priority</option>
                      <option value="urgent">Urgent Priority</option>
                    </select>
                  </div>

                  <div class="form-group">
                    <label>Estimated Hours per Day</label>
                    <input v-model.number="form.estimated_hours_per_day" type="number" min="1" max="24" />
                  </div>

                  <div class="form-group">
                    <label>Total Estimated Hours</label>
                    <input v-model.number="form.total_estimated_hours" type="number" min="1" />
                  </div>
                </div>

                <div class="form-group">
                  <label class="checkbox-label">
                    <input v-model="form.is_emergency" type="checkbox" />
                    <span class="checkmark"></span>
                    <span class="checkbox-text">This is an emergency assignment</span>
                  </label>
                </div>
              </div>

              <!-- Notes -->
              <div class="form-section">
                <h3 class="form-section-title">Notes & Requirements</h3>
                <div class="form-group">
                  <label>Assignment Notes</label>
                  <textarea v-model="form.assignment_notes" rows="3" placeholder="Additional notes about this assignment"></textarea>
                </div>

                <div class="form-group">
                  <label>Special Requirements</label>
                  <textarea v-model="form.special_requirements" rows="3" placeholder="Any special requirements or considerations"></textarea>
                </div>
              </div>
            </div>
          </form>

          <div class="modal-actions">
            <button @click="closeModal" type="button" class="btn btn-secondary">Cancel</button>
            <button @click="saveAssignment" :disabled="saving" class="btn btn-primary">
              <div v-if="saving" class="spinner spinner-sm mr-2"></div>
              {{ isEditing ? 'Update Assignment' : 'Create Assignment' }}
            </button>
          </div>
        </div>
      </div>

      <!-- View Assignment Modal -->
      <div v-if="showViewModal" class="modal-overlay">
        <div class="modal large">
          <div class="modal-header">
            <h2 class="modal-title">
              <EyeIcon class="modal-icon" />
              Assignment Details
            </h2>
            <button @click="closeViewModal" class="modal-close">
              <XIcon class="w-5 h-5" />
            </button>
          </div>

          <div v-if="selectedAssignment" class="modal-body">
            <div class="assignment-view-grid">
              <!-- Assignment Overview -->
              <div class="assignment-overview-card">
                <h3 class="card-title">{{ selectedAssignment.care_plan.title }}</h3>
                <p class="card-patient">Patient: {{ selectedAssignment.patient.first_name }} {{ selectedAssignment.patient.last_name }}</p>
                
                <div class="assignment-badges">
                  <span class="badge" :class="getStatusBadgeClass(selectedAssignment.status)">
                    {{ formatStatus(selectedAssignment.status) }}
                  </span>
                  <span class="badge" :class="getPriorityBadgeClass(selectedAssignment.priority_level)">
                    {{ formatPriority(selectedAssignment.priority_level) }}
                  </span>
                  <span class="badge" :class="getAssignmentTypeBadgeClass(selectedAssignment.assignment_type)">
                    {{ formatAssignmentType(selectedAssignment.assignment_type) }}
                  </span>
                  <span v-if="selectedAssignment.is_emergency" class="badge badge-danger">
                    Emergency
                  </span>
                </div>

                <div class="match-score-section">
                  <div class="match-score-header">
                    <span>Overall Match Score</span>
                    <span class="score-value" :class="getMatchScoreClass(selectedAssignment.overall_match_score)">
                      {{ selectedAssignment.overall_match_score || 0 }}%
                    </span>
                  </div>
                  <div class="score-bar large">
                    <div class="score-fill" :style="`width: ${selectedAssignment.overall_match_score || 0}%`"></div>
                  </div>
                  
                  <div class="score-breakdown-detailed">
                    <div class="breakdown-item">
                      <span>Skill Match</span>
                      <span>{{ selectedAssignment.skill_match_score || 0 }}%</span>
                    </div>
                    <div class="breakdown-item">
                      <span>Location Match</span>
                      <span>{{ selectedAssignment.location_match_score || 0 }}%</span>
                    </div>
                    <div class="breakdown-item">
                      <span>Availability</span>
                      <span>{{ selectedAssignment.availability_match_score || 0 }}%</span>
                    </div>
                    <div class="breakdown-item">
                      <span>Workload Balance</span>
                      <span>{{ selectedAssignment.workload_balance_score || 0 }}%</span>
                    </div>

                    <div v-if="selectedAssignment.admin_override" class="details-section">
                        <h4 class="section-header">
                            <AlertTriangleIcon class="w-5 h-5 mr-2 text-yellow-500" />
                            Administrative Override
                        </h4>
                        <div class="detail-item">
                            <span class="detail-label">Override Reason</span>
                            <p class="detail-value">{{ selectedAssignment.admin_override_reason }}</p>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Override Date</span>
                            <p class="detail-value">{{ formatDate(selectedAssignment.admin_override_at) }}</p>
                        </div>
                        </div>
                  </div>
                </div>
              </div>

              <!-- Assignment Details -->
              <div class="assignment-details">
                <div class="details-section">
                  <h4 class="section-header">
                    <UserIcon class="w-5 h-5 mr-2" />
                    Assigned Nurses
                  </h4>
                  <div class="nurse-cards">
                    <div class="nurse-card primary">
                      <div class="nurse-card-header">
                        <img :src="selectedAssignment.primary_nurse.avatar_url || generateAvatar(selectedAssignment.primary_nurse)" />
                        <div>
                          <h5>{{ selectedAssignment.primary_nurse.first_name }} {{ selectedAssignment.primary_nurse.last_name }}</h5>
                          <span class="nurse-role">Primary Nurse</span>
                        </div>
                      </div>
                      <div class="nurse-card-details">
                        <div class="detail-item">
                          <span class="detail-label">Experience</span>
                          <span>{{ selectedAssignment.primary_nurse.years_experience || 0 }} years</span>
                        </div>
                        <div class="detail-item">
                          <span class="detail-label">Specialization</span>
                          <span>{{ selectedAssignment.primary_nurse.specialization || 'General' }}</span>
                        </div>
                      </div>
                    </div>

                    <div v-if="selectedAssignment.secondary_nurse" class="nurse-card secondary">
                      <div class="nurse-card-header">
                        <img :src="selectedAssignment.secondary_nurse.avatar_url || generateAvatar(selectedAssignment.secondary_nurse)" />
                        <div>
                          <h5>{{ selectedAssignment.secondary_nurse.first_name }} {{ selectedAssignment.secondary_nurse.last_name }}</h5>
                          <span class="nurse-role">Secondary Nurse</span>
                        </div>
                      </div>
                      <div class="nurse-card-details">
                        <div class="detail-item">
                          <span class="detail-label">Experience</span>
                          <span>{{ selectedAssignment.secondary_nurse.years_experience || 0 }} years</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="details-section">
                  <h4 class="section-header">
                    <CalendarIcon class="w-5 h-5 mr-2" />
                    Assignment Schedule
                  </h4>
                  <div class="details-grid">
                    <div class="detail-item">
                      <span class="detail-label">Start Date</span>
                      <p class="detail-value">{{ formatDate(selectedAssignment.start_date) }}</p>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">End Date</span>
                      <p class="detail-value">{{ selectedAssignment.end_date ? formatDate(selectedAssignment.end_date) : 'Ongoing' }}</p>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">Estimated Hours/Day</span>
                      <p class="detail-value">{{ selectedAssignment.estimated_hours_per_day || 'Not specified' }}</p>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">Total Estimated Hours</span>
                      <p class="detail-value">{{ selectedAssignment.total_estimated_hours || 'Not specified' }}</p>
                    </div>
                  </div>
                </div>

                <div v-if="selectedAssignment.assignment_notes || selectedAssignment.special_requirements" class="details-section">
                  <h4 class="section-header">
                    <FileTextIcon class="w-5 h-5 mr-2" />
                    Notes & Requirements
                  </h4>
                  <div v-if="selectedAssignment.assignment_notes" class="detail-item">
                    <span class="detail-label">Assignment Notes</span>
                    <p class="detail-value">{{ selectedAssignment.assignment_notes }}</p>
                  </div>
                  <div v-if="selectedAssignment.special_requirements" class="detail-item">
                    <span class="detail-label">Special Requirements</span>
                    <p class="detail-value">{{ selectedAssignment.special_requirements }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="modal-actions">
            <button @click="closeViewModal" class="btn btn-secondary">Close</button>
            <button v-if="canEditAssignment(selectedAssignment)" @click="editAssignment(selectedAssignment)" class="btn btn-primary">
              Edit Assignment
            </button>
          </div>
        </div>
      </div>

      <!-- Nurse Matching Modal -->
      <div v-if="showNurseMatchingModal" class="modal-overlay">
        <div class="modal large">
          <div class="modal-header">
            <h2 class="modal-title">
              <SearchIcon class="modal-icon" />
              Find Suitable Nurses
            </h2>
            <button @click="closeNurseMatchingModal" class="modal-close">
              <XIcon class="w-5 h-5" />
            </button>
          </div>

          <div class="modal-body">
            <div class="nurse-matching-form">
              <div class="form-grid">
                <div class="form-group">
                  <label>Care Plan *</label>
                  <select v-model="nurseMatchingForm.care_plan_id" required>
                    <option value="">Select Care Plan</option>
                    <option v-for="plan in carePlans" :key="plan.id" :value="plan.id">
                      {{ plan.title }} - {{ plan.patient.first_name }} {{ plan.patient.last_name }}
                    </option>
                  </select>
                </div>
                
                <div class="form-group">
                  <label>Start Date *</label>
                  <input v-model="nurseMatchingForm.start_date" type="date" required />
                </div>
                
                <div class="form-group">
                  <label>End Date</label>
                  <input v-model="nurseMatchingForm.end_date" type="date" />
                </div>
              </div>
              
              <button @click="findSuitableNurses" :disabled="loadingMatches" class="btn btn-primary">
                <div v-if="loadingMatches" class="spinner spinner-sm mr-2"></div>
                <SearchIcon class="w-4 h-4 mr-2" />
                Find Matching Nurses
              </button>
            </div>

            <div v-if="loadingMatches" class="loading-state">
              <div class="loading-spinner"></div>
              <p class="loading-text">Finding suitable nurses...</p>
            </div>

            <div v-if="suitableNurses.length > 0" class="nurses-results">
              <h3>Suitable Nurses Found ({{ suitableNurses.length }})</h3>
              <div class="nurses-grid">
                <div v-for="result in suitableNurses" :key="result.nurse.id" class="nurse-match-card">
                  <div class="nurse-match-header">
                    <img :src="result.nurse.avatar_url || generateAvatar(result.nurse)" />
                    <div class="nurse-match-info">
                      <h4>{{ result.nurse.first_name }} {{ result.nurse.last_name }}</h4>
                      <p>{{ result.nurse.years_experience || 0 }}y experience</p>
                      <p>{{ result.current_assignments }} active assignments</p>
                    </div>
                    <div class="overall-match">
                      <span class="match-percentage" :class="getMatchScoreClass(result.match_scores.overall_match)">
                        {{ result.match_scores.overall_match }}%
                      </span>
                      <span class="match-label">Match</span>
                    </div>
                  </div>
                  
                  <div class="match-details">
                    <div class="match-breakdown">
                      <div class="match-item">
                        <span>Skills</span>
                        <span>{{ result.match_scores.skill_match }}%</span>
                      </div>
                      <div class="match-item">
                        <span>Location</span>
                        <span>{{ result.match_scores.location_match }}%</span>
                      </div>
                      <div class="match-item">
                        <span>Availability</span>
                        <span>{{ result.match_scores.availability_match }}%</span>
                      </div>
                      <div class="match-item">
                        <span>Workload</span>
                        <span>{{ result.match_scores.workload_balance }}%</span>
                      </div>
                    </div>
                  </div>
                  
                  <div class="nurse-match-actions">
                    <button @click="createAssignmentForNurse(result.nurse)" class="btn btn-primary btn-sm">
                      Create Assignment
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <div v-else-if="!loadingMatches && nurseMatchingForm.care_plan_id && nurseMatchingForm.start_date" class="empty-state">
              <SearchIcon class="empty-icon" />
              <h3>No suitable nurses found</h3>
              <p>Try adjusting your criteria or check nurse availability.</p>
            </div>
          </div>

          <div class="modal-actions">
            <button @click="closeNurseMatchingModal" class="btn btn-secondary">Close</button>
          </div>
        </div>
      </div>

      <!-- Approve Assignment Modal -->
      <div v-if="showApprovalModal && actioningAssignment" class="modal-overlay">
        <div class="modal modal-sm">
          <div class="modal-header">
            <h3 class="modal-title">
              <CheckIcon class="modal-icon" style="color: #0ea5e9;" />
              Approve Assignment
            </h3>
            <button @click="closeApprovalModal" class="modal-close">
              <XIcon class="w-5 h-5" />
            </button>
          </div>
          
          <div class="modal-body">
            <p>
              Are you sure you want to approve this assignment for <strong>{{ actioningAssignment.patient.first_name }} {{ actioningAssignment.patient.last_name }}</strong>?
            </p>
            <p class="text-sm text-gray-600 mt-2">
              Once approved, the assigned nurse will be notified and can accept or decline the assignment.
            </p>
          </div>

          <div class="modal-actions">
            <button @click="closeApprovalModal" class="btn btn-secondary">
              Cancel
            </button>
            <button @click="approveAssignment" :disabled="saving" class="btn btn-primary">
              <div v-if="saving" class="spinner spinner-sm mr-2"></div>
              Approve Assignment
            </button>
          </div>
        </div>
      </div>

      <!-- Accept Assignment Modal -->
      <div v-if="showAcceptModal && actioningAssignment" class="modal-overlay">
        <div class="modal modal-sm">
          <div class="modal-header">
            <h3 class="modal-title">
              <CheckIcon class="modal-icon" style="color: #059669;" />
              Accept Assignment
            </h3>
            <button @click="closeAcceptModal" class="modal-close">
              <XIcon class="w-5 h-5" />
            </button>
          </div>
          
          <div class="modal-body">
            <p>
              Are you sure you want to accept this assignment for <strong>{{ actioningAssignment.patient.first_name }} {{ actioningAssignment.patient.last_name }}</strong>?
            </p>
            <p class="text-sm text-gray-600 mt-2">
              Once accepted, you will be notified to begin care activities.
            </p>
          </div>

          <div class="modal-actions">
            <button @click="closeAcceptModal" class="btn btn-secondary">
              Cancel
            </button>
            <button @click="acceptAssignment" :disabled="saving" class="btn btn-primary">
              <div v-if="saving" class="spinner spinner-sm mr-2"></div>
              Accept Assignment
            </button>
          </div>
        </div>
      </div>

    <!-- Activate Assignment Modal -->
    <div v-if="showActivateModal && actioningAssignment" class="modal-overlay">
    <div class="modal modal-sm">
        <div class="modal-header">
        <h3 class="modal-title">
            <PlayIcon class="modal-icon" style="color: #059669;" />
            Activate Assignment
        </h3>
        <button @click="closeActivateModal" class="modal-close">
            <XIcon class="w-5 h-5" />
        </button>
        </div>
        
        <div class="modal-body">
        <p>
            Are you sure you want to activate this assignment for <strong>{{ actioningAssignment.patient.first_name }} {{ actioningAssignment.patient.last_name }}</strong>?
        </p>
        
        <!-- Show warning for admin override -->
        <div v-if="['pending', 'nurse_review'].includes(actioningAssignment.status)" 
            class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mt-3">
            <div class="flex">

            <div class="ml-3">
                <h3 class="text-sm font-medium text-yellow-800">
                Admin Override
                </h3>
                <div class="mt-2 text-sm text-yellow-700">
                <p>This will activate the assignment without nurse review/acceptance. This action will be logged as an administrative override.</p>
                </div>
            </div>
            </div>
        </div>
        
        <p class="text-sm text-gray-600 mt-2">
            Once activated, care activities can begin and nurses can start logging their work.
        </p>
        </div>

        <div class="modal-actions">
        <button @click="closeActivateModal" class="btn btn-secondary">
            Cancel
        </button>
        <button @click="activateAssignment" :disabled="saving" class="btn btn-primary">
            <div v-if="saving" class="spinner spinner-sm mr-2"></div>
            {{ ['pending', 'nurse_review'].includes(actioningAssignment.status) ? 'Override & Activate' : 'Activate Assignment' }}
        </button>
        </div>
    </div>
    </div>

      <!-- Complete Assignment Modal -->
      <div v-if="showCompleteModal && actioningAssignment" class="modal-overlay">
        <div class="modal modal-sm">
          <div class="modal-header">
            <h3 class="modal-title">
              <CheckCircleIcon class="modal-icon" style="color: #d97706;" />
              Complete Assignment
            </h3>
            <button @click="closeCompleteModal" class="modal-close">
              <XIcon class="w-5 h-5" />
            </button>
          </div>
          
          <div class="modal-body">
            <p>
              Are you sure you want to mark this assignment as complete for <strong>{{ actioningAssignment.patient.first_name }} {{ actioningAssignment.patient.last_name }}</strong>?
            </p>
            <p class="text-sm text-gray-600 mt-2">
              This will end the assignment and mark it as finished. This action cannot be undone.
            </p>
          </div>

          <div class="modal-actions">
            <button @click="closeCompleteModal" class="btn btn-secondary">
              Cancel
            </button>
            <button @click="completeAssignment" :disabled="saving" class="btn btn-danger">
              <div v-if="saving" class="spinner spinner-sm mr-2"></div>
              Mark Complete
            </button>
          </div>
        </div>
      </div>

      <!-- Reassign Modal -->
      <div v-if="showReassignModal && actioningAssignment" class="modal-overlay">
        <div class="modal modal-lg">
          <div class="modal-header">
            <h3 class="modal-title">
              <RefreshCcwIcon class="modal-icon" style="color: #d97706;" />
              Reassign Care Assignment
            </h3>
            <button @click="closeReassignModal" class="modal-close">
              <XIcon class="w-5 h-5" />
            </button>
          </div>
          
          <div class="modal-body">
            <p class="mb-4">
              Reassigning assignment for <strong>{{ actioningAssignment.patient.first_name }} {{ actioningAssignment.patient.last_name }}</strong>
            </p>

            <div class="form-section">
              <div class="form-group">
                <label>Select New Primary Nurse *</label>
                <SearchableSelect
                  v-if="nurseOptions.length > 0"
                  v-model="reassignForm.new_nurse_id"
                  :options="nurseOptions.filter(n => n.value !== actioningAssignment.primary_nurse_id)"
                  placeholder="Select New Primary Nurse"
                />
                <select v-else v-model="reassignForm.new_nurse_id" required>
                  <option value="">Loading nurses...</option>
                </select>
              </div>

              <div class="form-group">
                <label>Reassignment Reason *</label>
                <textarea 
                  v-model="reassignForm.reassignment_reason" 
                  rows="3" 
                  placeholder="Please explain the reason for reassignment..."
                  required
                ></textarea>
              </div>

              <div v-if="reassignForm.new_nurse_id" class="form-group">
                <label>Assignment Notes (Optional)</label>
                <textarea 
                  v-model="reassignForm.assignment_notes" 
                  rows="2" 
                  placeholder="Any additional notes for the new nurse..."
                ></textarea>
              </div>
            </div>
          </div>

          <div class="modal-actions">
            <button @click="closeReassignModal" class="btn btn-secondary">
              Cancel
            </button>
            <button @click="reassignAssignment" :disabled="saving || !reassignForm.new_nurse_id || !reassignForm.reassignment_reason" class="btn btn-warning">
              <div v-if="saving" class="spinner spinner-sm mr-2"></div>
              Reassign Assignment
            </button>
          </div>
        </div>
      </div>

      <!-- Delete Assignment Modal -->
      <div v-if="showDeleteModal && actioningAssignment" class="modal-overlay">
        <div class="modal delete-modal">
          <div class="modal-header modal-header-danger">
            <h3 class="modal-title">
              <TrashIcon class="modal-icon modal-icon-danger" />
              Delete Care Assignment
            </h3>
            <button @click="closeDeleteModal" class="modal-close">
              <XIcon class="w-5 h-5" />
            </button>
          </div>
          
          <div class="modal-body">
            <p>
              Are you sure you want to delete the assignment for <strong>{{ actioningAssignment.patient.first_name }} {{ actioningAssignment.patient.last_name }}</strong>?
            </p>
            <p class="text-sm text-red-600 mt-2 font-medium">
              ⚠️ This action cannot be undone. All assignment data will be permanently removed.
            </p>
          </div>

          <div class="modal-actions">
            <button @click="closeDeleteModal" class="btn btn-secondary">
              Cancel
            </button>
            <button @click="deleteAssignment" :disabled="saving" class="btn btn-danger">
              <div v-if="saving" class="spinner spinner-sm mr-2"></div>
              Delete Assignment
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Toast Component -->
    <Toast />
  </MainLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed, inject } from 'vue'
import MainLayout from '../../layout/MainLayout.vue'
import Toast from '../../common/components/Toast.vue'
import SearchableSelect from '../../common/components/SearchableSelect.vue'
import {
  PlusIcon,
  SearchIcon,
  EyeIcon,
  EditIcon,
  TrashIcon,
  MoreVerticalIcon,
  CheckIcon,
  PlayIcon,
  CheckCircleIcon,
  UsersIcon,
  XIcon,
  ClockIcon,
  TrendingUpIcon,
  UserIcon,
  CalendarIcon,
  FileTextIcon,
  RefreshCcwIcon,
  AlertTriangleIcon
} from 'lucide-vue-next'

// Toast injection with fallback
const toast = inject('toast', {
  showSuccess: (msg) => console.log('Success:', msg),
  showError: (msg) => console.log('Error:', msg)
})

// Reactive data
const loading = ref(false)
const saving = ref(false)
const loadingMatches = ref(false)
const assignments = ref({ data: [] })
const statistics = ref({})
const filterOptions = ref({
  statuses: {},
  assignment_types: {},
  intensity_levels: {}
})
const nurses = ref([])
const carePlans = ref([])
const suitableNurses = ref([])
const activeDropdown = ref(null)
const showModal = ref(false)
const showViewModal = ref(false)
const showNurseMatchingModal = ref(false)
const isEditing = ref(false)
const selectedAssignment = ref(null)

// Confirmation modals
const showApprovalModal = ref(false)
const showAcceptModal = ref(false)
const showActivateModal = ref(false)
const showCompleteModal = ref(false)
const showReassignModal = ref(false)
const showDeleteModal = ref(false)
const actioningAssignment = ref(null)

// Filters
const filters = ref({
  search: '',
  status: 'all',
  assignment_type: 'all',
  priority_level: 'all',
  is_emergency: 'all',
  page: 1,
  per_page: 15
})

// Form data
const form = ref({
  care_plan_id: '',
  primary_nurse_id: '',
  secondary_nurse_id: '',
  assignment_type: '',
  start_date: '',
  end_date: '',
  assignment_notes: '',
  special_requirements: '',
  estimated_hours_per_day: null,
  total_estimated_hours: null,
  intensity_level: '',
  priority_level: '',
  is_emergency: false
})

// Reassign form
const reassignForm = ref({
  new_nurse_id: '',
  reassignment_reason: '',
  assignment_notes: ''
})

// Nurse matching form
const nurseMatchingForm = ref({
  care_plan_id: '',
  start_date: '',
  end_date: ''
})

// Computed options for SearchableSelect
const carePlanOptions = computed(() => {
  if (!Array.isArray(carePlans.value)) return []
  return carePlans.value.map(plan => ({
    value: plan.id,
    label: `${plan.title} - ${plan.patient.first_name} ${plan.patient.last_name}`
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

// Get current user role
const getCurrentUserRole = () => {
  const user = JSON.parse(localStorage.getItem('user') || '{}')
  return user.role || 'patient'
}

// Debounced search
const debouncedSearch = (() => {
  let timeout
  return () => {
    clearTimeout(timeout)
    timeout = setTimeout(() => {
      loadAssignments()
    }, 300)
  }
})()

// API Helper Function
const apiCall = async (url, options = {}) => {
  const token = localStorage.getItem('auth_token')
  
  if (!token) {
    console.error('No auth token found')
    window.location.href = '/login'
    return null
  }

  const defaultOptions = {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    }
  }

  const mergedOptions = {
    ...defaultOptions,
    ...options,
    headers: {
      ...defaultOptions.headers,
      ...options.headers
    }
  }

  try {
    const response = await fetch(url, mergedOptions)
    
    if (response.status === 401) {
      console.error('Authentication failed')
      localStorage.removeItem('auth_token')
      window.location.href = '/login'
      return null
    }

    return await response.json()
  } catch (error) {
    console.error('API call failed:', error)
    throw error
  }
}

// Methods
const loadAssignments = async () => {
  loading.value = true
  try {
    const params = new URLSearchParams({
      search: filters.value.search || '',
      status: filters.value.status,
      assignment_type: filters.value.assignment_type,
      priority_level: filters.value.priority_level,
      is_emergency: filters.value.is_emergency,
      page: filters.value.page.toString(),
      per_page: filters.value.per_page.toString()
    })
    
    const data = await apiCall(`/api/care-assignments?${params}`)
    
    if (data && data.success) {
      assignments.value = data.data || { data: [] }
      filterOptions.value = data.filters || {
        statuses: {},
        assignment_types: {},
        intensity_levels: {}
      }
    }
  } catch (error) {
    console.error('Error loading assignments:', error)
    toast.showError('Failed to load assignments. Please try again.')
  } finally {
    loading.value = false
  }
}

const loadStatistics = async () => {
  try {
    const data = await apiCall('/api/care-assignments/data/statistics')
    
    if (data && data.success) {
      statistics.value = data.data || {}
    }
  } catch (error) {
    console.error('Error loading statistics:', error)
  }
}

const loadNurses = async () => {
  try {
    const data = await apiCall('/api/care-assignments/data/nurses')
    
    if (data && data.success) {
      nurses.value = data.data || []
    }
  } catch (error) {
    console.error('Error loading nurses:', error)
    nurses.value = []
  }
}

const loadCarePlans = async () => {
  try {
    const data = await apiCall('/api/care-assignments/data/approved-care-plans')
    
    if (data && data.success) {
      carePlans.value = data.data || []
    }
  } catch (error) {
    console.error('Error loading care plans:', error)
    carePlans.value = []
  }
}

const findSuitableNurses = async () => {
  if (!nurseMatchingForm.value.care_plan_id || !nurseMatchingForm.value.start_date) {
    toast.showError('Please select a care plan and start date.')
    return
  }
  
  loadingMatches.value = true
  try {
    const params = new URLSearchParams(nurseMatchingForm.value)
    const data = await apiCall(`/api/care-assignments/find-suitable-nurses?${params}`)
    
    if (data && data.success) {
      suitableNurses.value = data.data || []
      if (suitableNurses.value.length === 0) {
        toast.showError('No suitable nurses found for the selected criteria.')
      }
    }
  } catch (error) {
    console.error('Error finding suitable nurses:', error)
    toast.showError('Error finding suitable nurses. Please try again.')
  } finally {
    loadingMatches.value = false
  }
}

const changePage = (page) => {
  if (page >= 1 && page <= (assignments.value.last_page || 1)) {
    filters.value.page = page
    loadAssignments()
  }
}

const toggleDropdown = (assignmentId) => {
  activeDropdown.value = activeDropdown.value === assignmentId ? null : assignmentId
}

const openCreateModal = () => {
  isEditing.value = false
  resetForm()
  showModal.value = true
  loadNurses()
  loadCarePlans()
}

const openNurseMatchingModal = () => {
  showNurseMatchingModal.value = true
  loadCarePlans()
  suitableNurses.value = []
}

const editAssignment = (assignment) => {
  isEditing.value = true
  selectedAssignment.value = assignment
  fillForm(assignment)
  showModal.value = true
  showViewModal.value = false
  loadNurses()
  loadCarePlans()
}

const viewAssignment = (assignment) => {
  selectedAssignment.value = assignment
  showViewModal.value = true
  activeDropdown.value = null
}

const closeModal = () => {
  showModal.value = false
  resetForm()
}

const closeViewModal = () => {
  showViewModal.value = false
  selectedAssignment.value = null
}

const closeNurseMatchingModal = () => {
  showNurseMatchingModal.value = false
  nurseMatchingForm.value = {
    care_plan_id: '',
    start_date: '',
    end_date: ''
  }
  suitableNurses.value = []
}

const createAssignmentForNurse = (nurse) => {
  // Pre-fill form with nurse matching data
  form.value.care_plan_id = nurseMatchingForm.value.care_plan_id
  form.value.primary_nurse_id = nurse.id
  form.value.start_date = nurseMatchingForm.value.start_date
  form.value.end_date = nurseMatchingForm.value.end_date
  
  closeNurseMatchingModal()
  openCreateModal()
}

// Modal opening methods
const openApprovalModal = (assignment) => {
  actioningAssignment.value = assignment
  showApprovalModal.value = true
  activeDropdown.value = null
}

const openAcceptModal = (assignment) => {
  actioningAssignment.value = assignment
  showAcceptModal.value = true
  activeDropdown.value = null
}

const openActivateModal = (assignment) => {
  actioningAssignment.value = assignment
  showActivateModal.value = true
  activeDropdown.value = null
}

const openCompleteModal = (assignment) => {
  actioningAssignment.value = assignment
  showCompleteModal.value = true
  activeDropdown.value = null
}

const openReassignModal = (assignment) => {
  actioningAssignment.value = assignment
  showReassignModal.value = true
  activeDropdown.value = null
  
  // Reset reassign form
  reassignForm.value = {
    new_nurse_id: '',
    reassignment_reason: '',
    assignment_notes: ''
  }
  
  // Load nurses if needed
  if (nurses.value.length === 0) {
    loadNurses()
  }
}

const openDeleteModalWithConfirm = (assignment) => {
  actioningAssignment.value = assignment
  showDeleteModal.value = true
  activeDropdown.value = null
}

// Modal closing methods
const closeApprovalModal = () => {
  showApprovalModal.value = false
  actioningAssignment.value = null
}

const closeAcceptModal = () => {
  showAcceptModal.value = false
  actioningAssignment.value = null
}

const closeActivateModal = () => {
  showActivateModal.value = false
  actioningAssignment.value = null
}

const closeCompleteModal = () => {
  showCompleteModal.value = false
  actioningAssignment.value = null
}

const closeReassignModal = () => {
  showReassignModal.value = false
  actioningAssignment.value = null
  reassignForm.value = {
    new_nurse_id: '',
    reassignment_reason: '',
    assignment_notes: ''
  }
}

const closeDeleteModal = () => {
  showDeleteModal.value = false
  actioningAssignment.value = null
}

const resetForm = () => {
  form.value = {
    care_plan_id: '',
    primary_nurse_id: '',
    secondary_nurse_id: '',
    assignment_type: '',
    start_date: '',
    end_date: '',
    assignment_notes: '',
    special_requirements: '',
    estimated_hours_per_day: null,
    total_estimated_hours: null,
    intensity_level: '',
    priority_level: '',
    is_emergency: false
  }
}

const fillForm = (assignment) => {
  form.value = {
    care_plan_id: assignment.care_plan_id,
    primary_nurse_id: assignment.primary_nurse_id,
    secondary_nurse_id: assignment.secondary_nurse_id || '',
    assignment_type: assignment.assignment_type,
    start_date: assignment.start_date?.split('T')[0] || '',
    end_date: assignment.end_date?.split('T')[0] || '',
    assignment_notes: assignment.assignment_notes || '',
    special_requirements: assignment.special_requirements || '',
    estimated_hours_per_day: assignment.estimated_hours_per_day,
    total_estimated_hours: assignment.total_estimated_hours,
    intensity_level: assignment.intensity_level,
    priority_level: assignment.priority_level,
    is_emergency: assignment.is_emergency || false
  }
}

const saveAssignment = async () => {
  saving.value = true
  try {
    const url = isEditing.value 
      ? `/api/care-assignments/${selectedAssignment.value.id}` 
      : '/api/care-assignments'
    const method = isEditing.value ? 'PUT' : 'POST'
    
    const data = await apiCall(url, {
      method,
      body: JSON.stringify(form.value)
    })
    
    if (data && data.success) {
      closeModal()
      await loadAssignments()
      await loadStatistics()
      toast.showSuccess(
        isEditing.value 
          ? 'Assignment updated successfully!' 
          : 'Assignment created successfully!'
      )
    } else {
      toast.showError(data?.message || 'Failed to save assignment. Please try again.')
    }
  } catch (error) {
    console.error('Error saving assignment:', error)
    toast.showError('An error occurred while saving the assignment.')
  } finally {
    saving.value = false
  }
}

const approveAssignment = async () => {
  if (!actioningAssignment.value) return
  
  saving.value = true
  try {
    const data = await apiCall(`/api/care-assignments/${actioningAssignment.value.id}/approve`, {
      method: 'POST'
    })
    
    if (data && data.success) {
      await loadAssignments()
      await loadStatistics()
      closeApprovalModal()
      toast.showSuccess('Assignment approved successfully!')
    } else {
      toast.showError(data?.message || 'Failed to approve assignment.')
    }
  } catch (error) {
    console.error('Error approving assignment:', error)
    toast.showError('An error occurred while approving the assignment.')
  } finally {
    saving.value = false
  }
}

const acceptAssignment = async () => {
  if (!actioningAssignment.value) return
  
  saving.value = true
  try {
    const data = await apiCall(`/api/care-assignments/${actioningAssignment.value.id}/accept`, {
      method: 'POST'
    })
    
    if (data && data.success) {
      await loadAssignments()
      await loadStatistics()
      closeAcceptModal()
      toast.showSuccess('Assignment accepted successfully!')
    } else {
      toast.showError(data?.message || 'Failed to accept assignment.')
    }
  } catch (error) {
    console.error('Error accepting assignment:', error)
    toast.showError('An error occurred while accepting the assignment.')
  } finally {
    saving.value = false
  }
}

const activateAssignment = async () => {
  if (!actioningAssignment.value) return
  
  saving.value = true
  try {
    const data = await apiCall(`/api/care-assignments/${actioningAssignment.value.id}/activate`, {
      method: 'POST'
    })
    
    if (data && data.success) {
      await loadAssignments()
      await loadStatistics()
      closeActivateModal()
      toast.showSuccess('Assignment activated successfully!')
    } else {
      toast.showError(data?.message || 'Failed to activate assignment.')
    }
  } catch (error) {
    console.error('Error activating assignment:', error)
    toast.showError('An error occurred while activating the assignment.')
  } finally {
    saving.value = false
  }
}

const completeAssignment = async () => {
  if (!actioningAssignment.value) return
  
  saving.value = true
  try {
    const data = await apiCall(`/api/care-assignments/${actioningAssignment.value.id}/complete`, {
      method: 'POST'
    })
    
    if (data && data.success) {
      await loadAssignments()
      await loadStatistics()
      closeCompleteModal()
      toast.showSuccess('Assignment completed successfully!')
    } else {
      toast.showError(data?.message || 'Failed to complete assignment.')
    }
  } catch (error) {
    console.error('Error completing assignment:', error)
    toast.showError('An error occurred while completing the assignment.')
  } finally {
    saving.value = false
  }
}

const reassignAssignment = async () => {
  if (!actioningAssignment.value) return
  
  saving.value = true
  try {
    const data = await apiCall(`/api/care-assignments/${actioningAssignment.value.id}/reassign`, {
      method: 'POST',
      body: JSON.stringify(reassignForm.value)
    })
    
    if (data && data.success) {
      await loadAssignments()
      await loadStatistics()
      closeReassignModal()
      toast.showSuccess('Assignment reassigned successfully!')
    } else {
      toast.showError(data?.message || 'Failed to reassign assignment.')
    }
  } catch (error) {
    console.error('Error reassigning assignment:', error)
    toast.showError('An error occurred while reassigning the assignment.')
  } finally {
    saving.value = false
  }
}

const deleteAssignment = async () => {
  if (!actioningAssignment.value) return
  
  saving.value = true
  try {
    const data = await apiCall(`/api/care-assignments/${actioningAssignment.value.id}`, {
      method: 'DELETE'
    })
    
    if (data && data.success) {
      await loadAssignments()
      await loadStatistics()
      closeDeleteModal()
      toast.showSuccess('Assignment deleted successfully!')
    } else {
      toast.showError(data?.message || 'Failed to delete assignment.')
    }
  } catch (error) {
    console.error('Error deleting assignment:', error)
    toast.showError('An error occurred while deleting the assignment.')
  } finally {
    saving.value = false
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

const formatAssignmentType = (type) => {
  if (!type) return ''
  return type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatIntensityLevel = (level) => {
  if (!level) return ''
  return level.charAt(0).toUpperCase() + level.slice(1)
}

const truncateText = (text, length) => {
  if (!text) return ''
  return text.length > length ? text.substring(0, length) + '...' : text
}

const generateAvatar = (user) => {
  const name = `${user.first_name || ''} ${user.last_name || ''}`
  return `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&color=667eea&background=f8f9fa&size=200&font-size=0.6`
}

const isPendingResponse = (assignment) => {
  return ['pending', 'nurse_review'].includes(assignment.status)
}

// Badge classes
const getStatusBadgeClass = (status) => {
  const classes = {
    'pending': 'badge-warning',
    'nurse_review': 'badge-info',
    'accepted': 'badge-success',
    'declined': 'badge-danger',
    'active': 'badge-success',
    'on_hold': 'badge-warning',
    'completed': 'badge-success',
    'cancelled': 'badge-danger',
    'reassigned': 'badge-secondary'
  }
  return classes[status] || 'badge-secondary'
}

const getPriorityBadgeClass = (priority) => {
  const classes = {
    'low': 'badge-secondary',
    'medium': 'badge-info',
    'high': 'badge-warning',
    'urgent': 'badge-danger'
  }
  return classes[priority] || 'badge-secondary'
}

const getCareTypeBadgeClass = () => {
  return 'badge-primary'
}

const getAssignmentTypeBadgeClass = (type) => {
  const classes = {
    'single_nurse': 'badge-info',
    'dual_nurse': 'badge-success',
    'team_care': 'badge-warning',
    'rotating_care': 'badge-secondary',
    'emergency_assignment': 'badge-danger'
  }
  return classes[type] || 'badge-secondary'
}

const getMatchScoreClass = (score) => {
  if (score >= 80) return 'text-green-600'
  if (score >= 60) return 'text-yellow-600'
  if (score >= 40) return 'text-orange-600'
  return 'text-red-600'
}

// Permission checks
const canEditAssignment = (assignment) => {
  if (!assignment) return false
  return ['pending', 'on_hold'].includes(assignment.status)
}

const canApproveAssignment = (assignment) => {
  if (!assignment) return false
  const userRole = getCurrentUserRole()
  return assignment.status === 'pending' && ['admin', 'superadmin'].includes(userRole)
}

const canAcceptAssignment = (assignment) => {
  if (!assignment) return false
  const userRole = getCurrentUserRole()
  return ['nurse_review', 'approved'].includes(assignment.status) && userRole === 'nurse'
}

const canActivateAssignment = (assignment) => {
  if (!assignment) return false
  const userRole = getCurrentUserRole()
  
  // Admins can activate from multiple statuses, nurses need acceptance first
  if (['admin', 'superadmin'].includes(userRole)) {
    return ['pending', 'nurse_review', 'accepted'].includes(assignment.status)
  }
  
  // Nurses can only activate if they've already accepted
  return assignment.status === 'accepted'
}

const canCompleteAssignment = (assignment) => {
  if (!assignment) return false
  return assignment.status === 'active'
}

const canReassign = (assignment) => {
  if (!assignment) return false
  const userRole = getCurrentUserRole()
  return ['pending', 'nurse_review', 'declined', 'active'].includes(assignment.status) && 
         ['admin', 'superadmin'].includes(userRole)
}

const canDeleteAssignment = (assignment) => {
  if (!assignment) return false
  const userRole = getCurrentUserRole()
  return ['pending', 'nurse_review', 'declined', 'cancelled'].includes(assignment.status) &&
         ['admin', 'superadmin'].includes(userRole)
}

// Handle clicks outside dropdown
const handleClickOutside = (event) => {
  if (!event.target.closest('.action-dropdown')) {
    activeDropdown.value = null
  }
}

// Lifecycle
onMounted(async () => {
  try {
    await Promise.all([
      loadAssignments(),
      loadStatistics()
    ])
    
    document.addEventListener('click', handleClickOutside)
  } catch (error) {
    console.error('Error during component mount:', error)
  }
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
.care-assignments-page {
  min-height: 100vh;
  background: #f8f9fa;
  padding: 2rem;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: white;
  border-radius: 0.75rem;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.stat-content {
  flex: 1;
}

.stat-value {
  font-size: 2rem;
  font-weight: 700;
  color: #1f2937;
  margin-bottom: 0.25rem;
}

.stat-label {
  color: #6b7280;
  font-size: 0.875rem;
}

.stat-icon {
  width: 3rem;
  height: 3rem;
  border-radius: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Table Styles */
.assignments-table-container {
  background: white;
  border-radius: 0.75rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  border: 1px solid #e5e7eb;
  overflow: visible;
  position: relative;
}

.assignments-table {
  width: 100%;
  border-collapse: collapse;
}

.assignments-table thead {
  background: #f9fafb;
}

.assignments-table th {
  padding: 0.75rem 1.5rem;
  text-align: left;
  font-size: 0.75rem;
  font-weight: 500;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  border-bottom: 1px solid #e5e7eb;
}

.assignments-table th:last-child {
  text-align: right;
}

.assignments-table tbody {
  background: white;
}

.assignments-table tr {
  transition: background-color 0.15s;
}

.assignments-table tbody tr:hover {
  background: #f9fafb;
}

.assignments-table td {
  padding: 1rem 1.5rem;
  white-space: nowrap;
  font-size: 0.875rem;
  border-bottom: 1px solid #e5e7eb;
  vertical-align: top;
}

.assignments-table td:last-child {
  text-align: right;
}

.patient-care-info {
  min-width: 200px;
}

.patient-name {
  font-weight: 500;
  color: #1f2937;
  margin-bottom: 0.25rem;
}

.care-plan-title {
  font-size: 0.875rem;
  color: #6b7280;
  margin-bottom: 0.5rem;
}

.care-badges {
  display: flex;
  gap: 0.25rem;
  flex-wrap: wrap;
}

.nurse-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  min-width: 180px;
}

.nurse-avatar img {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
}

.nurse-name {
  font-weight: 500;
  color: #1f2937;
  margin-bottom: 0.25rem;
}

.nurse-experience {
  font-size: 0.875rem;
  color: #6b7280;
  margin-bottom: 0.25rem;
}

.secondary-nurse {
  font-size: 0.75rem;
  color: #059669;
  font-weight: 500;
}

.assignment-type-info {
  min-width: 120px;
}

.intensity-level {
  font-size: 0.875rem;
  color: #6b7280;
  margin-top: 0.25rem;
}

.status-info {
  min-width: 120px;
}

.response-time {
  font-size: 0.75rem;
  color: #6b7280;
  margin-top: 0.25rem;
}

.match-score-info {
  min-width: 100px;
}

.score-value {
  font-weight: 600;
  font-size: 0.875rem;
}

.score-bar {
  background: #e5e7eb;
  border-radius: 0.25rem;
  overflow: hidden;
  height: 4px;
  margin-top: 0.25rem;
}

.score-bar.large {
  height: 8px;
}

.score-fill {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  height: 100%;
  transition: width 0.3s ease;
}

.date-info {
  min-width: 120px;
}

.start-date {
  font-weight: 500;
  color: #1f2937;
  margin-bottom: 0.25rem;
}

.end-date {
  font-size: 0.875rem;
  color: #6b7280;
}

/* Assignment View Styles */
.assignment-view-grid {
  display: grid;
  grid-template-columns: 1fr 2fr;
  gap: 2rem;
}

.assignment-overview-card {
  background: #f9fafb;
  border-radius: 0.75rem;
  padding: 1.5rem;
}

.card-title {
  font-size: 1.25rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.card-patient {
  color: #6b7280;
  margin-bottom: 1rem;
}

.assignment-badges {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
  margin-bottom: 1.5rem;
}

.match-score-section {
  margin-top: 1.5rem;
}

.match-score-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.5rem;
  font-size: 0.875rem;
  font-weight: 500;
}

.score-breakdown-detailed {
  margin-top: 1rem;
  space-y: 0.5rem;
}

.breakdown-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.875rem;
  color: #6b7280;
  margin-bottom: 0.5rem;
}

.nurse-cards {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.nurse-card {
  background: white;
  border-radius: 0.75rem;
  padding: 1rem;
  border: 2px solid transparent;
}

.nurse-card.primary {
  border-color: #3b82f6;
}

.nurse-card.secondary {
  border-color: #10b981;
}

.nurse-card-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1rem;
}

.nurse-card-header img {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  object-fit: cover;
}

.nurse-card-header h5 {
  margin: 0;
  font-size: 1rem;
  font-weight: 500;
}

.nurse-role {
  font-size: 0.875rem;
  color: #6b7280;
}

.nurse-card-details {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 0.5rem;
}

/* Nurse Matching Styles */
.nurse-matching-form {
  margin-bottom: 2rem;
}

.nurses-results {
  margin-top: 2rem;
}

.nurses-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1rem;
  margin-top: 1rem;
}

.nurse-match-card {
  background: white;
  border-radius: 0.75rem;
  padding: 1.5rem;
  border: 1px solid #e5e7eb;
  transition: all 0.2s ease;
}

.nurse-match-card:hover {
  box-shadow: 0 4px 20px rgba(0,0,0,0.1);
  transform: translateY(-2px);
}

.nurse-match-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1rem;
}

.nurse-match-header img {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  object-fit: cover;
}

.nurse-match-info h4 {
  margin: 0 0 0.25rem 0;
  font-size: 1rem;
  font-weight: 500;
}

.nurse-match-info p {
  margin: 0;
  font-size: 0.875rem;
  color: #6b7280;
}

.overall-match {
  margin-left: auto;
  text-align: center;
}

.match-percentage {
  display: block;
  font-size: 1.25rem;
  font-weight: 600;
}

.match-label {
  font-size: 0.75rem;
  color: #6b7280;
}

.match-breakdown {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.match-item {
  display: flex;
  justify-content: space-between;
  font-size: 0.875rem;
  color: #6b7280;
}

.nurse-match-actions {
  text-align: center;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 3rem;
}

.empty-icon {
  margin: 0 auto 1rem;
  width: 3rem;
  height: 3rem;
  color: #9ca3af;
}

.empty-state h3 {
  margin: 0 0 0.25rem 0;
  font-size: 0.875rem;
  font-weight: 500;
  color: #1f2937;
}

.empty-state p {
  margin: 0;
  font-size: 0.875rem;
  color: #6b7280;
}

/* Loading State */
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  background: white;
  border-radius: 0.75rem;
  border: 1px solid #e5e7eb;
}

.loading-spinner {
  width: 3rem;
  height: 3rem;
  border: 3px solid #f3f4f6;
  border-top: 3px solid #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.loading-text {
  color: #6b7280;
  margin: 0;
}

/* Pagination */
.pagination-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 1.5rem;
  padding: 1rem 0;
}

.pagination-info {
  color: #6b7280;
  font-size: 0.875rem;
}

.pagination-controls {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.pagination-current {
  font-size: 0.875rem;
  color: #374151;
}

/* Modal and Form Styles */
.form-container {
  max-height: 600px;
  overflow-y: auto;
}

.form-section {
  margin-bottom: 2rem;
}

.form-section:last-child {
  margin-bottom: 0;
}

.form-section-title {
  font-size: 1.125rem;
  font-weight: 500;
  color: #1f2937;
  margin: 0 0 1rem 0;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 0.8rem;
}

@media (min-width: 768px) {
  .form-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

.form-grid-full {
  grid-column: 1 / -1;
}

/* Responsive Design */
@media (max-width: 1024px) {
  .assignment-view-grid {
    grid-template-columns: 1fr;
    gap: 1.5rem;
  }
  
  .assignments-table {
    font-size: 0.875rem;
  }
  
  .nurses-grid {
    grid-template-columns: 1fr;
  }
  
  .stats-grid {
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  }
  
  .assignments-table th,
  .assignments-table td {
    padding: 0.75rem 1rem;
  }
}

@media (max-width: 768px) {
  .care-assignments-page {
    padding: 1rem;
  }
  
  .table-wrapper {
    overflow-x: auto;
  }
  
  .assignments-table {
    min-width: 800px;
  }
  
  .form-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 640px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .pagination-container {
    flex-direction: column;
    gap: 1rem;
    align-items: flex-start;
  }
}
</style>