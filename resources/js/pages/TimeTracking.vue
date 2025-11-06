<template>
  <MainLayout>
    <div class="time-tracking-page">
      <!-- Page Header -->
      <div class="page-header">
        <div class="page-header-content">
          <h1>Time Tracking</h1>
          <p>Track and manage working hours and time sessions</p>
        </div>
        <div class="page-header-actions">
          <button @click="exportTimeTracking" class="btn-modern btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Export
          </button>
          <button
            v-if="canStartNewSession"
            @click="openStartSessionModal"
            class="btn-modern btn-primary"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Start Session
          </button>
        </div>
      </div>

      <!-- Current Session Card -->
      <div v-if="currentSession" class="current-session-card">
        <div class="session-header">
          <div class="session-info">
            <h3 class="session-title">Current Session</h3>
            <p class="session-subtitle">{{ formatSessionType(currentSession.session_type) }}</p>
          </div>
          <div class="session-status">
            <span :class="'modern-badge ' + getStatusBadgeColor(currentSession.status)">
              {{ formatStatus(currentSession.status) }}
            </span>
          </div>
        </div>

        <div class="session-content">
          <div class="timer-display">
            <div class="timer-time">{{ currentSessionTime }}</div>
            <div class="timer-label">{{ currentSession.status === 'paused' ? 'Paused' : 'Active' }}</div>
          </div>

          <div class="session-details-layout">
            <div class="detail-item" v-if="currentSession.patient">
              <label>Patient:</label>
              <span>{{ currentSession.patient.first_name }} {{ currentSession.patient.last_name }}</span>
            </div>
            <div class="detail-item" v-if="currentSession.schedule">
              <label>Schedule:</label>
              <span>{{ currentSession.schedule.care_plan?.title || 'No Care Plan' }}</span>
            </div>
            <div class="detail-item" v-if="currentSession.start_time">
              <label>Started:</label>
              <span>{{ formatTime(currentSession.start_time) }}</span>
            </div>
            <div class="detail-item" v-if="currentSession.clock_in_location_name || currentSession.clock_in_location">
              <label>Location:</label>
              <span>{{ currentSession.clock_in_location_name || currentSession.clock_in_location }}</span>
            </div>
          </div>

          <div class="session-actions">
            <button
              v-if="!currentSession.start_time"
              @click="clockIn"
              :disabled="isProcessing"
              class="btn-modern btn-primary"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
              </svg>
              Clock In
            </button>

            <template v-else-if="currentSession.status === 'active'">
              <button
                @click="pauseSession"
                :disabled="isProcessing"
                class="btn-modern btn-secondary"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6" />
                </svg>
                Pause
              </button>
              <button
                @click="openClockOutModal"
                :disabled="isProcessing"
                class="btn-modern btn-primary"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Clock Out
              </button>
            </template>

            <template v-else-if="currentSession.status === 'paused'">
              <button
                @click="resumeSession"
                :disabled="isProcessing"
                class="btn-modern btn-primary"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Resume
              </button>
              <button
                @click="openClockOutModal"
                :disabled="isProcessing"
                class="btn-modern btn-secondary"
              >
                Clock Out
              </button>
            </template>

            <button
              @click="openCancelSessionModal()"
              :disabled="isProcessing"
              class="btn-modern btn-danger"
            >
              Cancel Session
            </button>
          </div>
        </div>
      </div>

      <!-- Stats Grid -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon blue">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Today's Hours</div>
            <div class="stat-value">{{ formatHoursAndMinutes(todaysSummary.total_hours) }}</div>
            <div class="stat-change positive">{{ todaysSummary.total_sessions || 0 }} sessions</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon green">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">This Week</div>
            <div class="stat-value">{{ formatHoursAndMinutes(weeklySummary.total_hours_this_week) }}</div>
            <div class="stat-change positive">Total hours</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon purple">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Completed Sessions</div>
            <div class="stat-value">{{ statistics.completed_sessions || 0 }}</div>
            <div class="stat-change positive">All time</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon yellow">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Active Sessions</div>
            <div class="stat-value">{{ statistics.active_sessions || 0 }}</div>
            <div class="stat-change neutral">Currently active</div>
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
            placeholder="Search by nurse, patient, location..."
            v-model="searchQuery"
            class="search-input"
          />
        </div>
        <div class="filters-group">
          <select
            v-if="isAdminOrSuperAdmin && nurseOptions.length > 0"
            v-model="nurseFilter"
            class="filter-select"
          >
            <option value="all">All Nurses</option>
            <option v-for="nurse in availableNurses" :key="nurse.id" :value="nurse.id">
              {{ nurse.first_name }} {{ nurse.last_name }}
            </option>
          </select>
          
          <select v-model="statusFilter" class="filter-select">
            <option value="all">All Status</option>
            <option value="active">Active</option>
            <option value="paused">Paused</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
          </select>
          
          <select v-model="sessionTypeFilter" class="filter-select">
            <option value="all">All Types</option>
            <option value="scheduled_shift">Scheduled Shift</option>
            <option value="emergency_call">Emergency Call</option>
            <option value="overtime">Overtime</option>
            <option value="break_coverage">Break Coverage</option>
          </select>
          
          <select v-model="viewFilter" class="filter-select">
            <option value="all">All Time</option>
            <option value="today">Today</option>
            <option value="this_week">This Week</option>
          </select>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="loading-state">
        <div class="loading-spinner"></div>
        <p class="loading-text">Loading time tracking sessions...</p>
      </div>

      <!-- Time Tracking Table -->
      <div v-else-if="!loading" class="time-tracking-table-container">
        <div v-if="sessions.data && sessions.data.length > 0" class="table-wrapper">
          <table class="modern-table">
            <thead>
              <tr>
                <th>Nurse</th>
                <th>Patient</th>
                <th>Session Type</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Duration</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="session in sessions.data" :key="session.id">
                <td>
                  <div v-if="session.nurse" class="user-cell">
                    <img :src="session.nurse.avatar_url || generateAvatar(session.nurse)" class="user-avatar-table" />
                    <div class="user-details-table">
                      <div class="user-name-table">{{ session.nurse.first_name }} {{ session.nurse.last_name }}</div>
                      <div class="user-id-table">{{ session.nurse.email }}</div>
                    </div>
                  </div>
                  <div v-else class="text-secondary">No nurse assigned</div>
                </td>
                
                <td>
                  <div v-if="session.patient" class="contact-cell">
                    <div class="contact-primary">{{ session.patient.first_name }} {{ session.patient.last_name }}</div>
                    <div class="contact-secondary">Session #{{ session.id }}</div>
                  </div>
                  <div v-else class="text-secondary">No patient</div>
                </td>
                
                <td>
                  <span class="modern-badge badge-info">{{ formatSessionType(session.session_type) }}</span>
                </td>
                
                <td>
                  <div v-if="session.start_time" class="contact-cell">
                    <div class="contact-primary">{{ formatTime(session.start_time) }}</div>
                    <div class="contact-secondary">{{ formatDate(session.start_time) }}</div>
                  </div>
                  <div v-else class="text-secondary">Not started</div>
                </td>
                
                <td>
                  <div v-if="session.end_time" class="contact-cell">
                    <div class="contact-primary">{{ formatTime(session.end_time) }}</div>
                    <div class="contact-secondary">{{ formatDate(session.end_time) }}</div>
                  </div>
                  <div v-else class="text-secondary">In progress</div>
                </td>
                
                <td>
                  <span class="duration-badge">{{ formatDuration(session.formatted_duration) }}</span>
                </td>
                
                <td>
                  <span :class="'modern-badge ' + getStatusBadgeColor(session.status)">
                    {{ formatStatus(session.status) }}
                  </span>
                </td>
                
                <td>
                  <div class="action-cell">
                    <button @click="toggleDropdown(session.id)" class="action-btn">
                      <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                      </svg>
                    </button>
                    
                    <div v-show="activeDropdown === session.id" class="modern-dropdown">
                      <button @click="viewSession(session)" class="dropdown-item-modern">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        View Details
                      </button>
                      
                      <button
                        v-if="session.status === 'active' && !session.end_time"
                        @click="openTableClockOutModal(session)"
                        class="dropdown-item-modern warning"
                      >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Clock Out
                      </button>
                      
                      <div v-if="session.status !== 'completed' && session.status !== 'cancelled'" class="dropdown-divider"></div>
                      
                      <button
                        v-if="session.status !== 'completed' && session.status !== 'cancelled'"
                        @click="openCancelSessionModal(session)"
                        class="dropdown-item-modern danger"
                      >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Cancel Session
                      </button>
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>

          <!-- Pagination -->
          <div v-if="sessions.last_page > 1" class="pagination-container">
            <div class="pagination-info">
              Showing {{ (sessions.current_page - 1) * sessions.per_page + 1 }} to {{ Math.min(sessions.current_page * sessions.per_page, sessions.total) }} of {{ sessions.total }} sessions
            </div>
            <div class="pagination-controls">
              <button 
                @click="prevPage" 
                :disabled="sessions.current_page === 1"
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
                  :class="['pagination-page', { active: page === sessions.current_page }]"
                >
                  {{ page }}
                </button>
              </div>
              
              <button 
                @click="nextPage" 
                :disabled="sessions.current_page === sessions.last_page"
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

        <!-- Empty State -->
        <div v-else class="empty-state">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <h3>No time tracking sessions found</h3>
          <p>
            {{ (searchQuery || nurseFilter !== 'all' || statusFilter !== 'all' || sessionTypeFilter !== 'all') 
              ? 'Try adjusting your search or filters.' 
              : 'Start your first session to begin tracking work time.' }}
          </p>
          <button @click="openStartSessionModal" class="btn btn-primary">
            Start First Session
          </button>
        </div>
      </div>

      <!-- Start Session Modal -->
      <div v-if="showStartSessionModal" class="modal-overlay" @click.self="closeStartSessionModal">
        <div class="modal modal-lg">
          <div class="modal-header">
            <h2 class="modal-title">Start New Session</h2>
            <button @click="closeStartSessionModal" class="modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <form @submit.prevent="startSession">
            <div class="modal-body">
              <div class="form-grid">
                <div v-if="isAdminOrSuperAdmin" class="form-group form-grid-full">
                  <label>Select Nurse <span class="required">*</span></label>
                  <SearchableSelect
                    v-model="sessionForm.nurse_id"
                    :options="nurseOptions"
                    placeholder="Select a nurse..."
                    @update:modelValue="onNurseChange"
                    required
                  />
                </div>

                <div class="form-group">
                  <label>Session Type <span class="required">*</span></label>
                  <SearchableSelect
                    v-model="sessionForm.session_type"
                    :options="sessionTypeOptions"
                    placeholder="Select session type"
                    @update:modelValue="onSessionTypeChange"
                    required
                  />
                </div>

                <div v-if="sessionForm.session_type === 'scheduled_shift'" class="form-group">
                  <label>Related Schedule <span class="required">*</span></label>
                  <SearchableSelect
                    v-model="sessionForm.schedule_id"
                    :options="scheduleOptions"
                    placeholder="Select a schedule"
                    :disabled="!sessionForm.nurse_id && isAdminOrSuperAdmin"
                    @update:modelValue="onScheduleChange"
                    required
                  />
                  <p class="form-help">Current active schedules for {{ isAdminOrSuperAdmin ? 'selected nurse' : 'you' }}</p>
                </div>

                <div v-if="selectedSchedule" class="form-group form-grid-full">
                  <label>Schedule Information</label>
                  <div class="schedule-info-display">
                    <div class="schedule-details">
                      <div class="schedule-title">{{ selectedSchedule.care_plan?.title || 'No Care Plan' }}</div>
                      <div class="schedule-time">{{ formatTime(selectedSchedule.start_time) }} - {{ formatTime(selectedSchedule.end_time) }}</div>
                      <div v-if="selectedSchedule.care_plan?.patient" class="schedule-patient">
                        Patient: {{ selectedSchedule.care_plan.patient.first_name }} {{ selectedSchedule.care_plan.patient.last_name }}
                      </div>
                    </div>
                  </div>
                </div>

                <div class="form-grid-full">
                  <label class="checkbox-label">
                    <input type="checkbox" v-model="sessionForm.auto_clock_in" />
                    <span class="checkbox-text">Start timer immediately</span>
                  </label>
                </div>
              </div>
            </div>

            <div class="modal-actions">
              <button type="button" @click="closeStartSessionModal" class="btn btn-secondary">
                Cancel
              </button>
              <button type="submit" :disabled="isProcessing || isLoadingData || !isFormValid" class="btn btn-primary">
                <div v-if="isProcessing" class="spinner spinner-sm"></div>
                Start Session
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- View Session Modal -->
      <div v-if="showViewSessionModal && selectedSession" class="modal-overlay" @click.self="closeViewSessionModal">
        <div class="modal modal-xl">
          <div class="modal-header">
            <h2 class="modal-title">Session Details</h2>
            <button @click="closeViewSessionModal" class="modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="modal-body">
            <!-- Active Session Live View -->
            <div v-if="selectedSession.status === 'active' && selectedSession.start_time" class="active-session-view">
              <div class="session-header">
                <div class="session-info">
                  <h3 class="session-title">Active Session</h3>
                  <p class="session-subtitle">{{ formatSessionType(selectedSession.session_type) }}</p>
                </div>
                <div class="session-status">
                  <span :class="'modern-badge ' + getStatusBadgeColor(selectedSession.status)">
                    {{ formatStatus(selectedSession.status) }}
                  </span>
                </div>
              </div>

              <div class="session-content-modal">
                <div class="timer-display-modal">
                  <div class="timer-time-modal">{{ viewModalSessionTime }}</div>
                  <div class="timer-label-modal">Active Session</div>
                </div>

                <div class="session-details-layout-modal">
                  <div class="detail-item-modal" v-if="selectedSession.patient">
                    <label>Patient:</label>
                    <span>{{ selectedSession.patient.first_name }} {{ selectedSession.patient.last_name }}</span>
                  </div>
                  <div class="detail-item-modal" v-if="selectedSession.schedule">
                    <label>Schedule:</label>
                    <span>{{ selectedSession.schedule.care_plan?.title || 'No Care Plan' }}</span>
                  </div>
                  <div class="detail-item-modal" v-if="selectedSession.start_time">
                    <label>Started:</label>
                    <span>{{ formatTime(selectedSession.start_time) }}</span>
                  </div>
                  <div class="detail-item-modal" v-if="selectedSession.clock_in_location">
                    <label>Location:</label>
                    <span>{{ selectedSession.clock_in_location }}</span>
                  </div>
                </div>
              </div>
            </div>

            <div class="user-view-grid">
              <div class="user-profile-section">
                <img
                  :src="selectedSession.nurse?.avatar_url || generateAvatar(selectedSession.nurse)"
                  class="profile-avatar-large"
                />
                <h3 class="profile-name-view">
                  {{ selectedSession.nurse?.first_name }} {{ selectedSession.nurse?.last_name }}
                </h3>
                <span class="modern-badge badge-info">Nurse</span>
                <div class="profile-contact-view">
                  <div class="contact-item-view">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <span>{{ selectedSession.nurse?.email }}</span>
                  </div>
                </div>
              </div>

              <div class="details-section-view">
                <div class="details-group">
                  <h4 class="details-header">Session Information</h4>
                  <div class="details-grid-view">
                    <div class="detail-item-view">
                      <label>Session ID</label>
                      <p>#{{ selectedSession.id }}</p>
                    </div>
                    <div class="detail-item-view">
                      <label>Session Type</label>
                      <p>{{ formatSessionType(selectedSession.session_type) }}</p>
                    </div>
                    <div class="detail-item-view">
                      <label>Status</label>
                      <span :class="'modern-badge ' + getStatusBadgeColor(selectedSession.status)">
                        {{ formatStatus(selectedSession.status) }}
                      </span>
                    </div>
                    <div class="detail-item-view">
                      <label>Duration</label>
                      <p>{{ formatDuration(selectedSession.formatted_duration) }}</p>
                    </div>
                  </div>
                </div>

                <div v-if="selectedSession.patient" class="details-group">
                  <h4 class="details-header">Patient Information</h4>
                  <div class="details-grid-view">
                    <div class="detail-item-view">
                      <label>Patient Name</label>
                      <p>{{ selectedSession.patient.first_name }} {{ selectedSession.patient.last_name }}</p>
                    </div>
                    <div class="detail-item-view" v-if="selectedSession.schedule">
                      <label>Care Plan</label>
                      <p>{{ selectedSession.schedule.care_plan?.title || 'N/A' }}</p>
                    </div>
                  </div>
                </div>

                <div class="details-group">
                  <h4 class="details-header">Time Information</h4>
                  <div class="details-grid-view">
                    <div class="detail-item-view" v-if="selectedSession.start_time">
                      <label>Started</label>
                      <p>{{ formatDateTime(selectedSession.start_time) }}</p>
                    </div>
                    <div class="detail-item-view" v-if="selectedSession.end_time">
                      <label>Ended</label>
                      <p>{{ formatDateTime(selectedSession.end_time) }}</p>
                    </div>
                    <div class="detail-item-view" v-if="selectedSession.clock_in_location">
                      <label>Clock In Location</label>
                      <p>{{ selectedSession.clock_in_location }}</p>
                    </div>
                    <div class="detail-item-view" v-if="selectedSession.clock_out_location">
                      <label>Clock Out Location</label>
                      <p>{{ selectedSession.clock_out_location }}</p>
                    </div>
                  </div>
                </div>

                <div v-if="selectedSession.work_notes" class="details-group">
                  <h4 class="details-header">Work Notes</h4>
                  <p class="notes-text">{{ selectedSession.work_notes }}</p>
                </div>
              </div>
            </div>
          </div>

          <div class="modal-actions">
            <button @click="closeViewSessionModal" class="btn btn-secondary">Close</button>
          </div>
        </div>
      </div>

      <!-- Clock Out Modal -->
      <div v-if="showClockOutModal" class="modal-overlay" @click.self="closeClockOutModal">
        <div class="modal modal-sm">
          <div class="modal-header">
            <h2 class="modal-title">Clock Out</h2>
            <button @click="closeClockOutModal" class="modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <form @submit.prevent="clockOut">
            <div class="modal-body">
              <div class="form-group">
                <label>Work Notes (Optional)</label>
                <textarea
                  v-model="clockOutForm.work_notes"
                  placeholder="Describe what was accomplished during this session..."
                  rows="4"
                ></textarea>
              </div>

              <div class="form-group">
                <label>Current Location (Optional)</label>
                <input
                  type="text"
                  v-model="clockOutForm.location"
                  placeholder="Enter your current location"
                />
              </div>
            </div>

            <div class="modal-actions">
              <button type="button" @click="closeClockOutModal" class="btn btn-secondary">
                Cancel
              </button>
              <button type="submit" :disabled="isProcessing" class="btn btn-primary">
                <div v-if="isProcessing" class="spinner spinner-sm"></div>
                Clock Out
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Cancel Session Modal -->
      <div v-if="showCancelSessionModal" class="modal-overlay" @click.self="closeCancelSessionModal">
        <div class="modal modal-sm">
          <div class="modal-header">
            <h2 class="modal-title">Cancel Session</h2>
            <button @click="closeCancelSessionModal" class="modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <form @submit.prevent="cancelSession">
            <div class="modal-body">
              <p class="mb-4">Are you sure you want to cancel this session? This action cannot be undone.</p>
              <div class="form-group">
                <label>Cancellation Reason <span class="required">*</span></label>
                <textarea
                  v-model="cancelForm.reason"
                  placeholder="Please provide a reason for cancelling this session..."
                  rows="3"
                  required
                ></textarea>
              </div>
            </div>

            <div class="modal-actions">
              <button type="button" @click="closeCancelSessionModal" class="btn btn-secondary">
                Cancel
              </button>
              <button type="submit" :disabled="isProcessing" class="btn btn-danger">
                <div v-if="isProcessing" class="spinner spinner-sm"></div>
                Cancel Session
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <Toast />
  </MainLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, inject, watch } from 'vue'
import MainLayout from '../layout/MainLayout.vue'
import Toast from '../common/components/Toast.vue'
import SearchableSelect from '../common/components/SearchableSelect.vue'
import * as timeTrackingService from '../services/timeTrackingService'

const toast = inject('toast')

// Utility functions
const formatHoursAndMinutes = (decimalHours) => {
  if (!decimalHours || decimalHours === 0) return '0h 0m'
  
  const hours = Math.floor(decimalHours)
  const minutes = Math.round((decimalHours - hours) * 60)
  
  if (hours === 0) return `${minutes}m`
  if (minutes === 0) return `${hours}h`
  
  return `${hours}h ${minutes}m`
}

const formatDuration = (duration) => {
  if (!duration) return '0h 0m'
  
  if (typeof duration === 'string' && duration.includes('h')) {
    return duration
  }
  
  if (typeof duration === 'number') {
    return formatHoursAndMinutes(duration)
  }
  
  return duration
}

const getCurrentUserRole = () => {
  const user = JSON.parse(localStorage.getItem('user') || '{}')
  return user.role || 'admin'
}

const getCurrentUserId = () => {
  const user = JSON.parse(localStorage.getItem('user') || '{}')
  return user.id
}

// Reactive data
const currentSession = ref(null)
const sessions = ref({ data: [], current_page: 1, last_page: 1, per_page: 15, total: 0 })
const todaysSummary = ref({})
const weeklySummary = ref({})
const statistics = ref({})
const availableSchedules = ref([])
const availableNurses = ref([])
const selectedSession = ref(null)

const loading = ref(true)
const isProcessing = ref(false)
const isLoadingData = ref(false)
const currentSessionTime = ref('00:00:00')
const viewModalSessionTime = ref('00:00:00')
const timerInterval = ref(null)
const viewModalTimerInterval = ref(null)

const userRole = ref(getCurrentUserRole())
const currentUserId = ref(getCurrentUserId())

// Filters
const searchQuery = ref('')
const statusFilter = ref('all')
const sessionTypeFilter = ref('all')
const viewFilter = ref('all')
const nurseFilter = ref('all')
const activeDropdown = ref(null)

// Modal states
const showStartSessionModal = ref(false)
const showClockOutModal = ref(false)
const showCancelSessionModal = ref(false)
const showViewSessionModal = ref(false)
const sessionToCancel = ref(null)

// Form data
const sessionForm = ref({
  nurse_id: '',
  session_type: 'scheduled_shift',
  schedule_id: '',
  auto_clock_in: true
})

const clockOutForm = ref({
  work_notes: '',
  location: ''
})

const cancelForm = ref({
  reason: ''
})

// Computed properties
const isAdminOrSuperAdmin = computed(() => {
  return ['admin', 'superadmin'].includes(userRole.value)
})

const canStartNewSession = computed(() => {
  return !currentSession.value || ['completed', 'cancelled'].includes(currentSession.value.status)
})

const selectedSchedule = computed(() => {
  return availableSchedules.value.find(schedule => schedule.id == sessionForm.value.schedule_id)
})

const isFormValid = computed(() => {
  if (!sessionForm.value.session_type) return false
  if (sessionForm.value.session_type === 'scheduled_shift' && !sessionForm.value.schedule_id) return false
  if (isAdminOrSuperAdmin.value && !sessionForm.value.nurse_id) return false
  return true
})

const nurseOptions = computed(() => {
  if (!Array.isArray(availableNurses.value)) return []
  return availableNurses.value.map(nurse => ({
    value: nurse.id,
    label: `${nurse.first_name} ${nurse.last_name} - ${nurse.email}`
  }))
})

const scheduleOptions = computed(() => {
  if (!Array.isArray(availableSchedules.value)) return []
  return availableSchedules.value.map(schedule => ({
    value: schedule.id,
    label: `${schedule.care_plan?.title || 'No Care Plan'} - ${formatTime(schedule.start_time)} to ${formatTime(schedule.end_time)}`
  }))
})

const sessionTypeOptions = computed(() => [
  { value: 'scheduled_shift', label: 'Scheduled Shift' },
  { value: 'emergency_call', label: 'Emergency Call' },
  { value: 'overtime', label: 'Overtime' },
  { value: 'break_coverage', label: 'Break Coverage' }
])

// Data loading methods
const loadCurrentSession = async () => {
  try {
    const data = await timeTrackingService.getCurrentSession()
    currentSession.value = data.data
    if (currentSession.value) {
      startTimer()
    }
  } catch (error) {
    console.error('Error loading current session:', error)
  }
}

const loadSessions = async (page = 1) => {
  loading.value = true
  try {
    const params = {
      page: page,
      per_page: 15,
      search: searchQuery.value || undefined,
      status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
      session_type: sessionTypeFilter.value !== 'all' ? sessionTypeFilter.value : undefined,
      view: viewFilter.value !== 'all' ? viewFilter.value : undefined
    }

    if (isAdminOrSuperAdmin.value && nurseFilter.value !== 'all') {
      params.nurse_id = nurseFilter.value
    }

    Object.keys(params).forEach(key => params[key] === undefined && delete params[key])

    const data = await timeTrackingService.getTimeTrackingSessions(params)
    if (data && data.success) {
      sessions.value = data.data || { data: [], current_page: 1, last_page: 1, per_page: 15, total: 0 }
    }
  } catch (error) {
    console.error('Error loading sessions:', error)
    toast.showError('Failed to load sessions')
  } finally {
    loading.value = false
  }
}

const loadSummaries = async () => {
  try {
    const [todaysData, weeklyData, statsData] = await Promise.all([
      timeTrackingService.getTodaysSummary(),
      timeTrackingService.getWeeklySummary(),
      timeTrackingService.getStatistics()
    ])
    
    if (todaysData && todaysData.success) {
      todaysSummary.value = todaysData.data.summary
    }
    
    if (weeklyData && weeklyData.success) {
      weeklySummary.value = weeklyData.data
    }
    
    if (statsData && statsData.success) {
      statistics.value = statsData.data
    }
  } catch (error) {
    console.error('Error loading summaries:', error)
  }
}

const loadAvailableNurses = async () => {
  if (!isAdminOrSuperAdmin.value) return
  
  try {
    const data = await timeTrackingService.getAvailableNurses()
    if (data && data.success) {
      availableNurses.value = data.data || []
    }
  } catch (error) {
    console.error('Error loading nurses:', error)
    availableNurses.value = []
  }
}

const loadSchedulesForNurse = async (nurseId) => {
  if (!nurseId) {
    availableSchedules.value = []
    return
  }

  try {
    const data = await timeTrackingService.getSchedulesForNurse(nurseId)
    
    if (data && data.success) {
      const today = new Date()
      today.setHours(0, 0, 0, 0)
      
      availableSchedules.value = (data.data.data || []).filter(schedule => {
        const scheduleDateStr = schedule.schedule_date || schedule.date
        const scheduleDate = new Date(scheduleDateStr)
        scheduleDate.setHours(0, 0, 0, 0)
        
        const isToday = scheduleDate.getTime() === today.getTime()
        const isValidStatus = !['cancelled', 'completed'].includes(schedule.status)
        
        return isToday && isValidStatus
      })
      
      availableSchedules.value.sort((a, b) => {
        return (a.start_time || '').localeCompare(b.start_time || '')
      })
    } else {
      availableSchedules.value = []
    }
  } catch (error) {
    console.error('Error loading schedules:', error)
    availableSchedules.value = []
  }
}

// Timer functions
const startTimer = () => {
  if (timerInterval.value) {
    clearInterval(timerInterval.value)
  }

  timerInterval.value = setInterval(() => {
    if (currentSession.value && currentSession.value.start_time && currentSession.value.status === 'active') {
      const startTime = new Date(currentSession.value.start_time)
      const now = new Date()
      const diff = now - startTime
      
      const pauseDuration = (currentSession.value.total_pause_duration_minutes || 0) * 60 * 1000
      const activeDiff = Math.max(0, diff - pauseDuration)
      
      const hours = Math.floor(activeDiff / (1000 * 60 * 60))
      const minutes = Math.floor((activeDiff % (1000 * 60 * 60)) / (1000 * 60))
      const seconds = Math.floor((activeDiff % (1000 * 60)) / 1000)
      
      currentSessionTime.value = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`
    }
  }, 1000)
}

const stopTimer = () => {
  if (timerInterval.value) {
    clearInterval(timerInterval.value)
    timerInterval.value = null
  }
}

const startViewModalTimer = () => {
  if (viewModalTimerInterval.value) {
    clearInterval(viewModalTimerInterval.value)
  }

  viewModalTimerInterval.value = setInterval(() => {
    if (selectedSession.value && selectedSession.value.start_time && selectedSession.value.status === 'active') {
      const startTime = new Date(selectedSession.value.start_time)
      const now = new Date()
      const diff = now - startTime
      
      const pauseDuration = (selectedSession.value.total_pause_duration_minutes || 0) * 60 * 1000
      const activeDiff = Math.max(0, diff - pauseDuration)
      
      const hours = Math.floor(activeDiff / (1000 * 60 * 60))
      const minutes = Math.floor((activeDiff % (1000 * 60 * 60)) / (1000 * 60))
      const seconds = Math.floor((activeDiff % (1000 * 60)) / 1000)
      
      viewModalSessionTime.value = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`
    }
  }, 1000)
}

const stopViewModalTimer = () => {
  if (viewModalTimerInterval.value) {
    clearInterval(viewModalTimerInterval.value)
    viewModalTimerInterval.value = null
  }
}

// Form event handlers
const onNurseChange = (nurseId) => {
  sessionForm.value.schedule_id = ''
  
  if (nurseId) {
    loadSchedulesForNurse(nurseId)
  } else {
    availableSchedules.value = []
  }
}

const onSessionTypeChange = () => {
  sessionForm.value.schedule_id = ''
  if (sessionForm.value.session_type !== 'scheduled_shift') {
    availableSchedules.value = []
  } else {
    const nurseId = isAdminOrSuperAdmin.value ? sessionForm.value.nurse_id : currentUserId.value
    if (nurseId) {
      loadSchedulesForNurse(nurseId)
    }
  }
}

const onScheduleChange = () => {
  if (selectedSchedule.value?.care_plan?.patient) {
    sessionForm.value.patient_id = selectedSchedule.value.care_plan.patient.id
  }
}

// Modal methods
const openStartSessionModal = () => {
  sessionForm.value = {
    nurse_id: isAdminOrSuperAdmin.value ? '' : currentUserId.value,
    session_type: 'scheduled_shift',
    schedule_id: '',
    auto_clock_in: true
  }
  availableSchedules.value = []
  showStartSessionModal.value = true
  
  if (isAdminOrSuperAdmin.value) {
    loadAvailableNurses()
  } else {
    loadSchedulesForNurse(currentUserId.value)
  }
}

const closeStartSessionModal = () => {
  showStartSessionModal.value = false
}

const openClockOutModal = () => {
  clockOutForm.value = { work_notes: '', location: '' }
  showClockOutModal.value = true
}

const closeClockOutModal = () => {
  showClockOutModal.value = false
}

const openTableClockOutModal = (session) => {
  sessionToCancel.value = session
  clockOutForm.value = { work_notes: '', location: '' }
  showClockOutModal.value = true
  activeDropdown.value = null
}

const openCancelSessionModal = (session = null) => {
  sessionToCancel.value = session || currentSession.value
  cancelForm.value = { reason: '' }
  showCancelSessionModal.value = true
  activeDropdown.value = null
}

const closeCancelSessionModal = () => {
  showCancelSessionModal.value = false
  sessionToCancel.value = null
}

const viewSession = (session) => {
  selectedSession.value = session
  showViewSessionModal.value = true
  activeDropdown.value = null
  
  // Start timer if session is active
  if (session.status === 'active' && session.start_time) {
    startViewModalTimer()
  }
}

const closeViewSessionModal = () => {
  showViewSessionModal.value = false
  stopViewModalTimer()
  selectedSession.value = null
}

// Action methods
const startSession = async () => {
  isProcessing.value = true
  
  try {
    const response = await timeTrackingService.createTimeTrackingSession(sessionForm.value)
    
    if (response && response.success) {
      currentSession.value = response.data
      closeStartSessionModal()
      toast.showSuccess('Session started successfully!')
      if (sessionForm.value.auto_clock_in) {
        startTimer()
      }
      await loadSessions()
      await loadSummaries()
    } else {
      toast.showError(response?.message || 'Failed to start session')
    }
  } catch (error) {
    console.error('Error starting session:', error)
    toast.showError('An error occurred while starting the session')
  }
  
  isProcessing.value = false
}

const clockIn = async () => {
  isProcessing.value = true
  
  try {
    const response = await timeTrackingService.clockIn(currentSession.value.id, { location: '' })
    
    if (response && response.success) {
      currentSession.value = response.data
      toast.showSuccess('Clocked in successfully!')
      startTimer()
      await loadSessions()
    } else {
      toast.showError(response?.message || 'Failed to clock in')
    }
  } catch (error) {
    console.error('Error clocking in:', error)
    toast.showError('An error occurred while clocking in')
  }
  
  isProcessing.value = false
}

const clockOut = async () => {
  isProcessing.value = true
  
  try {
    const targetSession = sessionToCancel.value || currentSession.value
    const response = await timeTrackingService.clockOut(targetSession.id, clockOutForm.value)
    
    if (response && response.success) {
      if (targetSession.id === currentSession.value?.id) {
        currentSession.value = response.data
        stopTimer()
      }
      closeClockOutModal()
      sessionToCancel.value = null
      toast.showSuccess('Clocked out successfully!')
      await loadSessions()
      await loadSummaries()
    } else {
      toast.showError(response?.message || 'Failed to clock out')
    }
  } catch (error) {
    console.error('Error clocking out:', error)
    toast.showError('An error occurred while clocking out')
  }
  
  isProcessing.value = false
}

const pauseSession = async () => {
  isProcessing.value = true
  
  try {
    const response = await timeTrackingService.pauseSession(currentSession.value.id, { reason: 'Manual pause' })
    
    if (response && response.success) {
      currentSession.value = response.data
      toast.showSuccess('Session paused successfully!')
      await loadSessions()
    } else {
      toast.showError(response?.message || 'Failed to pause session')
    }
  } catch (error) {
    console.error('Error pausing session:', error)
    toast.showError('An error occurred while pausing the session')
  }
  
  isProcessing.value = false
}

const resumeSession = async () => {
  isProcessing.value = true
  
  try {
    const response = await timeTrackingService.resumeSession(currentSession.value.id)
    
    if (response && response.success) {
      currentSession.value = response.data
      toast.showSuccess('Session resumed successfully!')
      await loadSessions()
    } else {
      toast.showError(response?.message || 'Failed to resume session')
    }
  } catch (error) {
    console.error('Error resuming session:', error)
    toast.showError('An error occurred while resuming the session')
  }
  
  isProcessing.value = false
}

const cancelSession = async () => {
  isProcessing.value = true
  
  try {
    const response = await timeTrackingService.cancelSession(sessionToCancel.value.id, cancelForm.value)
    
    if (response && response.success) {
      if (sessionToCancel.value.id === currentSession.value?.id) {
        currentSession.value = response.data
        stopTimer()
      }
      closeCancelSessionModal()
      toast.showSuccess('Session cancelled successfully!')
      await loadSessions()
      await loadSummaries()
    } else {
      toast.showError(response?.message || 'Failed to cancel session')
    }
  } catch (error) {
    console.error('Error cancelling session:', error)
    toast.showError('An error occurred while cancelling the session')
  }
  
  isProcessing.value = false
}

const exportTimeTracking = async () => {
  try {
    toast.showInfo('Preparing export...')
    
    const filters = {
      nurse_id: nurseFilter.value !== 'all' ? nurseFilter.value : undefined,
      status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
      session_type: sessionTypeFilter.value !== 'all' ? sessionTypeFilter.value : undefined,
      view: viewFilter.value !== 'all' ? viewFilter.value : undefined,
      search: searchQuery.value || undefined
    }
    
    Object.keys(filters).forEach(key => filters[key] === undefined && delete filters[key])
    
    const { blob, filename } = await timeTrackingService.exportTimeTracking(filters)
    
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = filename
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
    
    toast.showSuccess('Time tracking data exported!')
  } catch (error) {
    console.error('Error:', error)
    toast.showError('Failed to export')
  }
}

// Utility methods
const toggleDropdown = (sessionId) => {
  activeDropdown.value = activeDropdown.value === sessionId ? null : sessionId
}

const goToPage = (page) => {
  if (page >= 1 && page <= sessions.value.last_page) {
    loadSessions(page)
  }
}

const nextPage = () => {
  if (sessions.value.current_page < sessions.value.last_page) {
    goToPage(sessions.value.current_page + 1)
  }
}

const prevPage = () => {
  if (sessions.value.current_page > 1) {
    goToPage(sessions.value.current_page - 1)
  }
}

const getPaginationPages = () => {
  const pages = []
  const maxVisible = 5
  let start = Math.max(1, sessions.value.current_page - Math.floor(maxVisible / 2))
  let end = Math.min(sessions.value.last_page, start + maxVisible - 1)
  
  if (end - start < maxVisible - 1) {
    start = Math.max(1, end - maxVisible + 1)
  }
  
  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  
  return pages
}

const formatSessionType = (type) => {
  const types = {
    'scheduled_shift': 'Scheduled Shift',
    'emergency_call': 'Emergency Call',
    'overtime': 'Overtime',
    'break_coverage': 'Break Coverage'
  }
  return types[type] || type
}

const formatStatus = (status) => {
  return status.charAt(0).toUpperCase() + status.slice(1)
}

const getStatusBadgeColor = (status) => {
  const colors = {
    'active': 'badge-success',
    'paused': 'badge-warning',
    'completed': 'badge-info',
    'cancelled': 'badge-danger'
  }
  return colors[status] || 'badge-secondary'
}

const formatTime = (datetime) => {
  if (!datetime) return ''
  
  const date = new Date(datetime)
  
  if (isNaN(date.getTime())) {
    if (typeof datetime === 'string' && /^\d{1,2}:\d{2}(:\d{2})?$/.test(datetime)) {
      return datetime.substring(0, 5)
    }
    return 'Invalid Time'
  }
  
  return date.toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatDate = (datetime) => {
  if (!datetime) return ''
  return new Date(datetime).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric'
  })
}

const formatDateTime = (datetime) => {
  if (!datetime) return 'N/A'
  return new Date(datetime).toLocaleString()
}

const generateAvatar = (user) => {
  if (!user) {
    return 'https://ui-avatars.com/api/?name=N+A&color=667eea&background=f8f9fa&size=200&font-size=0.6'
  }
  const name = `${user.first_name || ''} ${user.last_name || ''}`
  return `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&color=667eea&background=f8f9fa&size=200&font-size=0.6`
}

const handleClickOutside = (event) => {
  if (!event.target.closest('.action-cell')) {
    activeDropdown.value = null
  }
}

// Lifecycle
onMounted(async () => {
  await loadCurrentSession()
  await loadSessions()
  await loadSummaries()
  
  if (isAdminOrSuperAdmin.value) {
    await loadAvailableNurses()
  }
  
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  stopTimer()
  stopViewModalTimer()
  document.removeEventListener('click', handleClickOutside)
})

// Watchers
let searchDebounceTimer = null

watch(searchQuery, () => {
  clearTimeout(searchDebounceTimer)
  searchDebounceTimer = setTimeout(() => {
    loadSessions(1)
  }, 500)
})

watch([statusFilter, sessionTypeFilter, viewFilter, nurseFilter], () => {
  loadSessions(1)
})
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

* {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

.time-tracking-page {
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

.btn-modern.btn-danger {
  background: #ef4444;
  color: white;
}

.btn-modern.btn-danger:hover {
  background: #dc2626;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
}

/* Current Session Card */
.current-session-card {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 16px;
  padding: 32px;
  color: white;
  margin-bottom: 32px;
  box-shadow: 0 20px 40px rgba(102, 126, 234, 0.3);
}

.session-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 32px;
}

.session-title {
  font-size: 24px;
  font-weight: 700;
  margin: 0 0 8px 0;
}

.session-subtitle {
  opacity: 0.9;
  margin: 0;
  font-size: 16px;
}

.session-content {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 32px;
  align-items: center;
}

.timer-display {
  text-align: center;
  padding: 24px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  backdrop-filter: blur(10px);
}

.timer-time {
  font-size: 48px;
  font-weight: 700;
  font-family: 'Courier New', monospace;
  margin-bottom: 8px;
  letter-spacing: 2px;
}

.timer-label {
  font-size: 16px;
  opacity: 0.9;
  font-weight: 500;
}

.session-details-layout {
  display: grid;
  gap: 16px;
}

.detail-item {
  display: flex;
  justify-content: space-between;
  padding: 12px 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}

.detail-item:last-child {
  border-bottom: none;
}

.detail-item label {
  font-weight: 600;
  opacity: 0.9;
}

.detail-item span {
  font-weight: 500;
}

.session-actions {
  grid-column: 1 / -1;
  display: flex;
  gap: 12px;
  justify-content: center;
  margin-top: 24px;
  flex-wrap: wrap;
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
  flex-wrap: wrap;
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
.time-tracking-table-container {
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

.duration-badge {
  background: #f3f4f6;
  color: #4b5563;
  padding: 6px 12px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 700;
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
  color: #64748b;
  margin-top: 4px;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
  width: 18px;
  height: 18px;
  cursor: pointer;
}

.checkbox-text {
  font-size: 14px;
  color: #334155;
  font-weight: 500;
}

.schedule-info-display {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 16px;
}

.schedule-title {
  font-weight: 600;
  color: #0f172a;
  margin-bottom: 4px;
}

.schedule-time {
  color: #64748b;
  font-size: 14px;
  margin-bottom: 4px;
}

.schedule-patient {
  color: #059669;
  font-size: 14px;
  font-weight: 500;
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

/* Active Session View in Modal */
.active-session-view {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 16px;
  padding: 32px;
  color: white;
  margin-bottom: 32px;
  box-shadow: 0 20px 40px rgba(102, 126, 234, 0.3);
}

.session-content-modal {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 32px;
  align-items: center;
  margin-top: 24px;
}

.timer-display-modal {
  text-align: center;
  padding: 24px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  backdrop-filter: blur(10px);
}

.timer-time-modal {
  font-size: 48px;
  font-weight: 700;
  font-family: 'Courier New', monospace;
  margin-bottom: 8px;
  letter-spacing: 2px;
}

.timer-label-modal {
  font-size: 16px;
  opacity: 0.9;
  font-weight: 500;
}

.session-details-layout-modal {
  display: grid;
  gap: 16px;
}

.detail-item-modal {
  display: flex;
  justify-content: space-between;
  padding: 12px 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}

.detail-item-modal:last-child {
  border-bottom: none;
}

.detail-item-modal label {
  font-weight: 600;
  opacity: 0.9;
}

.detail-item-modal span {
  font-weight: 500;
}

/* View Grid */
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

.notes-text {
  background: white;
  padding: 16px;
  border-radius: 8px;
  line-height: 1.6;
  color: #334155;
}

.mb-4 {
  margin-bottom: 16px;
}

/* Responsive */
@media (max-width: 1024px) {
  .user-view-grid {
    grid-template-columns: 1fr;
  }
  
  .details-grid-view {
    grid-template-columns: 1fr;
  }
  
  .session-content-modal {
    grid-template-columns: 1fr;
    gap: 24px;
  }
}

@media (max-width: 768px) {
  .time-tracking-page {
    padding: 16px;
  }
  
  .page-header {
    flex-direction: column;
    gap: 16px;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .session-content {
    grid-template-columns: 1fr;
    gap: 24px;
  }
  
  .session-content-modal {
    grid-template-columns: 1fr;
    gap: 20px;
  }
  
  .timer-time {
    font-size: 36px;
  }
  
  .timer-time-modal {
    font-size: 36px;
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

  .session-actions {
    flex-direction: column;
  }
}
</style>