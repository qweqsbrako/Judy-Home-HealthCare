<template>
  <MainLayout>
    <div class="schedules-page">
      <!-- Page Header -->
      <div class="page-header">
        <div class="page-header-content">
          <h1>Schedule Management</h1>
          <p>Create and manage nurse shift schedules</p>
        </div>
        <div class="page-header-actions">
          <button @click="exportSchedules" class="btn-modern btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Export
          </button>
          <button @click="toggleCalendarView" class="btn-modern btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            {{ viewMode === 'table' ? 'Calendar View' : 'Table View' }}
          </button>
          <button @click="openCreateModal" class="btn-modern btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Add Schedule
          </button>
        </div>
      </div>

      <!-- Stats Grid -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon blue">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Today's Shifts</div>
            <div class="stat-value">{{ statistics.today_schedules || 0 }}</div>
            <div class="stat-change positive">Active today</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon green">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Completion Rate</div>
            <div class="stat-value">{{ Math.round(statistics.completion_rate || 0) }}%</div>
            <div class="stat-change positive">{{ statistics.completed_schedules || 0 }} completed</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon purple">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Active Nurses</div>
            <div class="stat-value">{{ statistics.active_nurses || 0 }}</div>
            <div class="stat-change positive">Currently scheduled</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon yellow">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Upcoming Shifts</div>
            <div class="stat-value">{{ statistics.upcoming_schedules || 0 }}</div>
            <div class="stat-change neutral">Next 7 days</div>
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
            placeholder="Search by nurse name, location, care plan, patient..."
            v-model="searchQuery"
            class="search-input"
          />
        </div>
        <div class="filters-group">
          <select v-model="nurseFilter" class="filter-select">
            <option value="all">All Nurses</option>
            <option v-for="nurse in nurses" :key="nurse.id" :value="nurse.id">
              {{ nurse.first_name }} {{ nurse.last_name }}
            </option>
          </select>
          
          <select v-model="shiftTypeFilter" class="filter-select">
            <option value="all">All Shift Types</option>
            <option v-for="(label, value) in shiftTypes" :key="value" :value="value">
              {{ label }}
            </option>
          </select>
          
          <select v-model="statusFilter" class="filter-select">
            <option value="all">All Status</option>
            <option v-for="(label, value) in statuses" :key="value" :value="value">
              {{ label }}
            </option>
          </select>

          <select v-model="viewFilter" class="filter-select">
            <option value="all">All Time</option>
            <option value="today">Today</option>
            <option value="this_week">This Week</option>
            <option value="upcoming">Upcoming</option>
          </select>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="loading-state">
        <div class="loading-spinner"></div>
        <p class="loading-text">Loading schedules...</p>
      </div>

      <!-- Calendar View -->
      <div v-else-if="viewMode === 'calendar'" class="calendar-container">
        <div class="calendar-header">
          <div class="calendar-nav">
            <button @click="previousMonth" class="btn-modern btn-secondary">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
              </svg>
            </button>
            <h2 class="calendar-title">{{ formatCalendarTitle(currentDate) }}</h2>
            <button @click="nextMonth" class="btn-modern btn-secondary">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </button>
          </div>
          <button @click="goToToday" class="btn-modern btn-primary">Today</button>
        </div>

        <div class="calendar-grid">
          <div v-for="day in dayHeaders" :key="day" class="calendar-day-header">
            {{ day }}
          </div>

          <div
            v-for="day in calendarDays"
            :key="`${day.date.toISOString()}`"
            class="calendar-day"
            :class="{
              'calendar-day-other-month': !day.isCurrentMonth,
              'calendar-day-today': day.isToday,
              'calendar-day-has-schedules': day.schedules.length > 0
            }"
          >
            <div class="calendar-day-number">{{ day.date.getDate() }}</div>
            
            <div class="calendar-day-schedules">
              <div
                v-for="schedule in day.schedules.slice(0, 3)"
                :key="schedule.id"
                class="calendar-schedule-item"
                :class="getScheduleCalendarClass(schedule.status)"
                @click="viewSchedule(schedule)"
              >
                <div class="schedule-time">{{ formatTime(schedule.start_time) }}</div>
                <div v-if="schedule.nurse" class="schedule-nurse">{{ schedule.nurse.first_name }}</div>
                <div v-else class="schedule-nurse">Unassigned</div>
              </div>
              
              <div v-if="day.schedules.length > 3" class="calendar-more-indicator">
                +{{ day.schedules.length - 3 }} more
              </div>
            </div>
          </div>
        </div>

        <div class="calendar-legend">
          <div class="legend-item">
            <div class="legend-color bg-blue-500"></div>
            <span>Scheduled</span>
          </div>
          <div class="legend-item">
            <div class="legend-color bg-yellow-500"></div>
            <span>In Progress</span>
          </div>
          <div class="legend-item">
            <div class="legend-color bg-green-500"></div>
            <span>Completed</span>
          </div>
          <div class="legend-item">
            <div class="legend-color bg-red-500"></div>
            <span>Cancelled</span>
          </div>
        </div>
      </div>

      <!-- Table View -->
      <div v-else-if="!loading" class="schedules-table-container">
        <div v-if="schedules.data && schedules.data.length > 0" class="table-wrapper">
          <table class="modern-table">
            <thead>
              <tr>
                <th>Nurse</th>
                <th>Care Plan</th>
                <th>Date & Time</th>
                <th>Shift Type</th>
                <th>Status</th>
                <th>Progress</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="schedule in schedules.data" :key="schedule.id">
                <td>
                  <div v-if="schedule.nurse" class="user-cell">
                    <img :src="schedule.nurse.avatar_url || generateAvatar(schedule.nurse)" class="user-avatar-table" />
                    <div class="user-details-table">
                      <div class="user-name-table">{{ schedule.nurse.first_name }} {{ schedule.nurse.last_name }}</div>
                      <div class="user-id-table">{{ schedule.nurse.email }}</div>
                    </div>
                  </div>
                  <div v-else class="text-secondary">Nurse not assigned</div>
                </td>
                
                <td>
                  <div v-if="schedule.care_plan" class="contact-cell">
                    <div class="contact-primary">{{ schedule.care_plan.title }}</div>
                    <div class="contact-secondary">
                      {{ schedule.care_plan.patient ? 
                        `${schedule.care_plan.patient.first_name} ${schedule.care_plan.patient.last_name}` : 
                        'Patient N/A' }}
                    </div>
                  </div>
                  <div v-else class="text-secondary">No care plan</div>
                </td>
                
                <td>
                  <div class="contact-cell">
                    <div class="contact-primary">{{ formatDate(schedule.schedule_date) }}</div>
                    <div class="contact-secondary">{{ schedule.formatted_time_slot }}</div>
                  </div>
                </td>
                
                <td>
                  <span class="modern-badge badge-info">{{ schedule.formatted_shift_type }}</span>
                </td>
                
                <td>
                  <span class="modern-badge" :class="getStatusBadgeColor(schedule.status)">
                    {{ formatStatus(schedule.status) }}
                  </span>
                </td>
                
                <td>
                  <div class="progress-container">
                    <div class="progress-bar">
                      <div 
                        class="progress-fill" 
                        :class="getProgressColor(getProgressPercentage(schedule), schedule.status)"
                        :style="`width: ${getProgressPercentage(schedule)}%`"
                      ></div>
                    </div>
                    <div class="progress-text">{{ getProgressPercentage(schedule) }}%</div>
                  </div>
                  <div v-if="schedule.status === 'in_progress'" class="progress-time">
                    {{ formatElapsedTime(schedule) }}
                  </div>
                </td>
                
                <td>
                  <div class="action-cell">
                    <button @click="toggleDropdown(schedule.id)" class="action-btn">
                      <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                      </svg>
                    </button>
                    
                    <div v-show="activeDropdown === schedule.id" class="modern-dropdown">
                      <button @click="viewSchedule(schedule)" class="dropdown-item-modern">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        View Details
                      </button>
                      
                      <button 
                        v-if="['scheduled', 'confirmed'].includes(schedule.status)" 
                        @click="editSchedule(schedule)" 
                        class="dropdown-item-modern"
                      >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Edit Schedule
                      </button>
                      
                      <button 
                        v-if="schedule.status === 'in_progress'" 
                        @click="openEndShiftModal(schedule)" 
                        class="dropdown-item-modern warning"
                      >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        End Shift
                      </button>
                      
                      <button 
                        v-if="['scheduled', 'confirmed'].includes(schedule.status)" 
                        @click="sendReminder(schedule)" 
                        class="dropdown-item-modern"
                      >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        Send Reminder
                      </button>
                      
                      <div v-if="!['completed', 'cancelled'].includes(schedule.status)" class="dropdown-divider"></div>
                      
                      <button 
                        v-if="!['completed', 'cancelled'].includes(schedule.status)" 
                        @click="openCancelModal(schedule)" 
                        class="dropdown-item-modern danger"
                      >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Cancel Schedule
                      </button>
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>

          <!-- Pagination -->
          <div v-if="schedules.last_page > 1" class="pagination-container">
            <div class="pagination-info">
              Showing {{ (schedules.current_page - 1) * schedules.per_page + 1 }} to {{ Math.min(schedules.current_page * schedules.per_page, schedules.total) }} of {{ schedules.total }} schedules
            </div>
            <div class="pagination-controls">
              <button 
                @click="prevPage" 
                :disabled="schedules.current_page === 1"
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
                  :class="['pagination-page', { active: page === schedules.current_page }]"
                >
                  {{ page }}
                </button>
              </div>
              
              <button 
                @click="nextPage" 
                :disabled="schedules.current_page === schedules.last_page"
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
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          <h3>No schedules found</h3>
          <p>
            {{ (searchQuery || nurseFilter !== 'all' || shiftTypeFilter !== 'all' || statusFilter !== 'all') 
              ? 'Try adjusting your search or filters.' 
              : 'Start by creating a new schedule.' }}
          </p>
          <button @click="openCreateModal" class="btn btn-primary">
            Create First Schedule
          </button>
        </div>
      </div>

      <!-- Create/Edit Schedule Modal -->
      <div v-if="showScheduleModal" class="modal-overlay" @click.self="closeScheduleModal">
        <div class="modal modal-lg">
          <div class="modal-header">
            <h2 class="modal-title">
              {{ isEditing ? 'Edit Schedule' : 'Create New Schedule' }}
            </h2>
            <button @click="closeScheduleModal" class="modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <form @submit.prevent="saveSchedule">
            <div class="modal-body">
              <div class="form-grid">
                <div class="form-section-header">
                  <h3 class="form-section-title">Basic Information</h3>
                </div>
                
                <div class="form-group">
                  <label>Nurse <span class="required">*</span></label>
                  <SearchableSelect
                    v-if="nurseOptions.length > 0"
                    v-model="scheduleForm.nurse_id"
                    :options="nurseOptions"
                    placeholder="Select Nurse"
                    @update:modelValue="onNurseChange"
                  />
                </div>

                <div class="form-group">
                  <label>Care Plan</label>
                  <SearchableSelect
                    v-if="carePlanOptions.length > 0"
                    v-model="scheduleForm.care_plan_id"
                    :options="carePlanOptions"
                    placeholder="Select Care Plan (Optional)"
                    :disabled="!scheduleForm.nurse_id"
                  />
                  <select v-else v-model="scheduleForm.care_plan_id" :disabled="!scheduleForm.nurse_id">
                    <option value="">{{ scheduleForm.nurse_id ? 'Loading...' : 'Select nurse first' }}</option>
                  </select>
                </div>

                <div class="form-group">
                  <label>Schedule Date <span class="required">*</span></label>
                  <input
                    type="date"
                    v-model="scheduleForm.schedule_date"
                    :min="today"
                    required
                  />
                </div>

                <div class="form-group">
                  <label>Start Time <span class="required">*</span></label>
                  <input
                    type="time"
                    v-model="scheduleForm.start_time"
                    required
                  />
                </div>

                <div class="form-group">
                  <label>End Time <span class="required">*</span></label>
                  <input
                    type="time"
                    v-model="scheduleForm.end_time"
                    required
                  />
                </div>

                <div class="form-group">
                  <label>Shift Type <span class="required">*</span></label>
                  <SearchableSelect
                    v-if="shiftTypeOptions.length > 0"
                    v-model="scheduleForm.shift_type"
                    :options="shiftTypeOptions"
                    placeholder="Select shift type"
                  />
                </div>

                <div class="form-group form-grid-full">
                  <label>Location</label>
                  <input
                    type="text"
                    v-model="scheduleForm.location"
                    placeholder="Hospital ward, department, etc."
                  />
                </div>

                <div class="form-group form-grid-full">
                  <label>Shift Notes</label>
                  <textarea
                    v-model="scheduleForm.shift_notes"
                    rows="3"
                    placeholder="Any special instructions or notes..."
                  ></textarea>
                </div>
              </div>
            </div>

            <div class="modal-actions">
              <button type="button" @click="closeScheduleModal" class="btn btn-secondary">
                Cancel
              </button>
              <button type="submit" :disabled="isSaving" class="btn btn-primary">
                <div v-if="isSaving" class="spinner spinner-sm"></div>
                {{ isEditing ? 'Update Schedule' : 'Create Schedule' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- View Schedule Modal -->
      <div v-if="showViewModal && selectedSchedule" class="modal-overlay" @click.self="closeViewModal">
        <div class="modal modal-xl">
          <div class="modal-header">
            <h2 class="modal-title">Schedule Details</h2>
            <button @click="closeViewModal" class="modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="modal-body">
            <div v-if="selectedSchedule.nurse" class="user-view-grid">
              <div class="user-profile-section">
                <img
                  :src="selectedSchedule.nurse.avatar_url || generateAvatar(selectedSchedule.nurse)"
                  class="profile-avatar-large"
                />
                <h3 class="profile-name-view">
                  {{ selectedSchedule.nurse.first_name }} {{ selectedSchedule.nurse.last_name }}
                </h3>
                <span class="modern-badge badge-info">Nurse</span>
                <div class="profile-contact-view">
                  <div class="contact-item-view">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <span>{{ selectedSchedule.nurse.email }}</span>
                  </div>
                </div>
                
                <div class="progress-section">
                  <div class="progress-header">
                    <span>Shift Progress</span>
                    <span>{{ getProgressPercentage(selectedSchedule) }}%</span>
                  </div>
                  <div class="progress-bar large">
                    <div 
                      class="progress-fill" 
                      :class="getProgressColor(getProgressPercentage(selectedSchedule), selectedSchedule.status)"
                      :style="`width: ${getProgressPercentage(selectedSchedule)}%`"
                    ></div>
                  </div>
                </div>
              </div>

              <div class="details-section-view">
                <div v-if="selectedSchedule.care_plan" class="details-group">
                  <h4 class="details-header">Care Plan Information</h4>
                  <div class="details-grid-view">
                    <div class="detail-item-view">
                      <label>Care Plan</label>
                      <p>{{ selectedSchedule.care_plan.title }}</p>
                    </div>
                    <div class="detail-item-view">
                      <label>Patient</label>
                      <p>{{ selectedSchedule.care_plan.patient ? 
                        `${selectedSchedule.care_plan.patient.first_name} ${selectedSchedule.care_plan.patient.last_name}` : 
                        'N/A' }}</p>
                    </div>
                  </div>
                </div>

                <div class="details-group">
                  <h4 class="details-header">Schedule Information</h4>
                  <div class="details-grid-view">
                    <div class="detail-item-view">
                      <label>Date</label>
                      <p>{{ formatDate(selectedSchedule.schedule_date) }}</p>
                    </div>
                    <div class="detail-item-view">
                      <label>Time Slot</label>
                      <p>{{ selectedSchedule.formatted_time_slot }}</p>
                    </div>
                    <div class="detail-item-view">
                      <label>Duration</label>
                      <p>{{ selectedSchedule.duration_minutes }} minutes</p>
                    </div>
                    <div class="detail-item-view">
                      <label>Shift Type</label>
                      <p>{{ selectedSchedule.formatted_shift_type }}</p>
                    </div>
                    <div class="detail-item-view">
                      <label>Location</label>
                      <p>{{ selectedSchedule.location || 'Not specified' }}</p>
                    </div>
                    <div class="detail-item-view">
                      <label>Status</label>
                      <span class="modern-badge" :class="getStatusBadgeColor(selectedSchedule.status)">
                        {{ formatStatus(selectedSchedule.status) }}
                      </span>
                    </div>
                  </div>
                </div>

                <div v-if="selectedSchedule.actual_start_time || selectedSchedule.actual_end_time" class="details-group">
                  <h4 class="details-header">Time Tracking</h4>
                  <div class="details-grid-view">
                    <div class="detail-item-view">
                      <label>Actual Start</label>
                      <p>{{ formatDateTime(selectedSchedule.actual_start_time) }}</p>
                    </div>
                    <div class="detail-item-view">
                      <label>Actual End</label>
                      <p>{{ formatDateTime(selectedSchedule.actual_end_time) }}</p>
                    </div>
                    <div v-if="selectedSchedule.actual_duration_minutes" class="detail-item-view">
                      <label>Actual Duration</label>
                      <p>{{ selectedSchedule.actual_duration_minutes }} minutes</p>
                    </div>
                  </div>
                </div>

                <div v-if="selectedSchedule.shift_notes" class="details-group">
                  <h4 class="details-header">Shift Notes</h4>
                  <p class="notes-text">{{ selectedSchedule.shift_notes }}</p>
                </div>
              </div>
            </div>
            
            <div v-else class="empty-state">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
              <h3>Nurse Not Assigned</h3>
              <p>This schedule doesn't have a nurse assigned yet.</p>
            </div>
          </div>

          <div class="modal-actions">
            <button @click="closeViewModal" class="btn btn-secondary">Close</button>
            <button 
              v-if="['scheduled', 'confirmed'].includes(selectedSchedule.status)"
              @click="editSchedule(selectedSchedule)" 
              class="btn btn-primary"
            >
              Edit Schedule
            </button>
          </div>
        </div>
      </div>

      <!-- End Shift Modal -->
      <div v-if="showEndShiftModal && currentSchedule" class="modal-overlay" @click.self="closeEndShiftModal">
        <div class="modal modal-sm">
          <div class="modal-header">
            <h3 class="modal-title">End Shift</h3>
            <button @click="closeEndShiftModal" class="modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          
          <form @submit.prevent="endShift">
            <div class="modal-body">
              <div class="form-group">
                <label>End Shift Notes (Optional)</label>
                <textarea
                  v-model="endShiftNotes"
                  rows="3"
                  placeholder="Any notes about this shift..."
                ></textarea>
              </div>
            </div>

            <div class="modal-actions">
              <button type="button" @click="closeEndShiftModal" class="btn btn-secondary">Cancel</button>
              <button type="submit" :disabled="isProcessing" class="btn btn-primary">
                <div v-if="isProcessing" class="spinner spinner-sm"></div>
                End Shift
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Cancel Modal -->
      <div v-if="showCancelModal && currentSchedule" class="modal-overlay" @click.self="closeCancelModal">
        <div class="modal modal-sm">
          <div class="modal-header">
            <h3 class="modal-title">Cancel Schedule</h3>
            <button @click="closeCancelModal" class="modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          
          <form @submit.prevent="cancelSchedule">
            <div class="modal-body">
              <p class="mb-4">Cancel schedule for <strong>{{ currentSchedule.nurse ? `${currentSchedule.nurse.first_name} ${currentSchedule.nurse.last_name}` : 'Unassigned Nurse' }}</strong>?</p>
              <div class="form-group">
                <label>Cancellation Reason <span class="required">*</span></label>
                <textarea
                  v-model="cancellationReason"
                  rows="3"
                  placeholder="Reason for cancellation..."
                  required
                ></textarea>
              </div>
            </div>

            <div class="modal-actions">
              <button type="button" @click="closeCancelModal" class="btn btn-secondary">Keep Schedule</button>
              <button type="submit" :disabled="isProcessing" class="btn btn-danger">
                <div v-if="isProcessing" class="spinner spinner-sm"></div>
                Cancel Schedule
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
import * as schedulesService from '../../services/schedulesService'

const toast = inject('toast')

// Reactive data
const schedules = ref({ data: [], total: 0, per_page: 15, current_page: 1, last_page: 1 })
const nurses = ref([])
const carePlans = ref([])
const loading = ref(true)
const searchQuery = ref('')
const nurseFilter = ref('all')
const shiftTypeFilter = ref('all')
const statusFilter = ref('all')
const viewFilter = ref('all')
const viewMode = ref('table')
const statistics = ref({})

// Calendar data
const currentDate = ref(new Date())
const calendarSchedules = ref([])
const dayHeaders = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']

// Modal states
const showScheduleModal = ref(false)
const showViewModal = ref(false)
const showEndShiftModal = ref(false)
const showCancelModal = ref(false)
const isEditing = ref(false)
const currentSchedule = ref(null)
const selectedSchedule = ref(null)
const isSaving = ref(false)
const isProcessing = ref(false)
const cancellationReason = ref('')
const endShiftNotes = ref('')

// Live update
const liveUpdateInterval = ref(null)

// Dropdown state
const activeDropdown = ref(null)

// Form data
const scheduleForm = ref({
  nurse_id: '',
  care_plan_id: '',
  schedule_date: '',
  start_time: '',
  end_time: '',
  shift_type: '',
  location: '',
  shift_notes: ''
})

// Options
const shiftTypes = ref({})
const statuses = ref({})

// Computed
const today = computed(() => new Date().toISOString().split('T')[0])

const nurseOptions = computed(() => {
  if (!Array.isArray(nurses.value)) return []
  return nurses.value.map(nurse => ({
    value: nurse.id,
    label: `${nurse.first_name} ${nurse.last_name} - ${nurse.email}`
  }))
})

const carePlanOptions = computed(() => {
  if (!Array.isArray(carePlans.value)) return []
  return carePlans.value.map(plan => ({
    value: plan.id,
    label: `${plan.title} - ${plan.patient ? `${plan.patient.first_name} ${plan.patient.last_name}` : 'Unknown'}`
  }))
})

const shiftTypeOptions = computed(() => {
  if (!shiftTypes.value) return []
  return Object.entries(shiftTypes.value).map(([value, label]) => ({
    value: value,
    label: label
  }))
})

const calendarDays = computed(() => {
  const year = currentDate.value.getFullYear()
  const month = currentDate.value.getMonth()
  
  const firstDay = new Date(year, month, 1)
  const lastDay = new Date(year, month + 1, 0)
  
  const startDate = new Date(firstDay)
  startDate.setDate(firstDay.getDate() - firstDay.getDay())
  
  const endDate = new Date(lastDay)
  endDate.setDate(lastDay.getDate() + (6 - lastDay.getDay()))
  
  const days = []
  const currentDay = new Date(startDate)
  
  while (currentDay <= endDate) {
    const isCurrentMonth = currentDay.getMonth() === month
    const isToday = isSameDay(currentDay, new Date())
    
    const daySchedules = calendarSchedules.value.filter(schedule => {
      const scheduleDate = new Date(schedule.schedule_date)
      return isSameDay(scheduleDate, currentDay)
    })
    
    days.push({
      date: new Date(currentDay),
      isCurrentMonth,
      isToday,
      schedules: daySchedules
    })
    
    currentDay.setDate(currentDay.getDate() + 1)
  }
  
  return days
})

// Methods
const loadSchedules = async (page = 1) => {
  loading.value = true
  try {
    const params = {
      page: page,
      per_page: 15,
      search: searchQuery.value || undefined,
      nurse_id: nurseFilter.value !== 'all' ? nurseFilter.value : undefined,
      shift_type: shiftTypeFilter.value !== 'all' ? shiftTypeFilter.value : undefined,
      status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
      view: viewFilter.value !== 'all' ? viewFilter.value : undefined
    }
    
    // Remove undefined values
    Object.keys(params).forEach(key => params[key] === undefined && delete params[key])
    
    const response = await schedulesService.getSchedules(params)
    schedules.value = response.data || { data: [], total: 0, current_page: 1, last_page: 1, per_page: 15 }
    shiftTypes.value = response.filters?.shift_types || {}
    statuses.value = response.filters?.statuses || {}
  } catch (error) {
    console.error('Error loading schedules:', error)
    toast.showError('Failed to load schedules')
  }
  loading.value = false
}

const loadCalendarSchedules = async () => {
  loading.value = true
  try {
    const year = currentDate.value.getFullYear()
    const month = currentDate.value.getMonth()
    const startDate = new Date(year, month, 1)
    const endDate = new Date(year, month + 1, 0)
    
    const calendarStart = new Date(startDate)
    calendarStart.setDate(startDate.getDate() - startDate.getDay())
    
    const calendarEnd = new Date(endDate)
    calendarEnd.setDate(endDate.getDate() + (6 - endDate.getDay()))
    
    const params = {
      start_date: calendarStart.toISOString().split('T')[0],
      end_date: calendarEnd.toISOString().split('T')[0],
      nurse_id: nurseFilter.value !== 'all' ? nurseFilter.value : undefined,
      shift_type: shiftTypeFilter.value !== 'all' ? shiftTypeFilter.value : undefined,
      status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
      per_page: 1000
    }
    
    // Remove undefined values
    Object.keys(params).forEach(key => params[key] === undefined && delete params[key])
    
    const response = await schedulesService.getSchedules(params)
    calendarSchedules.value = response.data?.data || []
  } catch (error) {
    console.error('Error loading calendar:', error)
    toast.showError('Failed to load calendar')
    calendarSchedules.value = []
  }
  loading.value = false
}

const loadNurses = async () => {
  try {
    const response = await schedulesService.getNurses()
    nurses.value = response.data || []
  } catch (error) {
    console.error('Error loading nurses:', error)
  }
}

const loadCarePlansForNurse = async (nurseId) => {
  if (!nurseId) {
    carePlans.value = []
    return
  }

  try {
    const response = await schedulesService.getCarePlansForNurse(nurseId)
    carePlans.value = response.data || []
  } catch (error) {
    console.error('Error loading care plans:', error)
    carePlans.value = []
  }
}

const getStatistics = async () => {
  try {
    const response = await schedulesService.getStatistics()
    statistics.value = response.data || {}
  } catch (error) {
    console.error('Error loading statistics:', error)
  }
}

const getProgressPercentage = (schedule) => {
  if (!schedule.actual_start_time) return 0

  const expectedDuration = schedule.duration_minutes
  if (expectedDuration <= 0) return 0

  let actualMinutesWorked = 0

  if (schedule.status === 'completed' && schedule.actual_end_time) {
    actualMinutesWorked = schedule.actual_duration_minutes || 0
  } else if (schedule.status === 'in_progress') {
    const startTime = new Date(schedule.actual_start_time)
    const currentTime = new Date()
    actualMinutesWorked = Math.floor((currentTime - startTime) / (1000 * 60))
  } else {
    return 0
  }

  const percentage = Math.min(Math.round((actualMinutesWorked / expectedDuration) * 100), 100)
  return Math.max(percentage, 0)
}

const getProgressColor = (percentage, status) => {
  if (status === 'cancelled') return 'progress-red'
  if (percentage === 0) return 'progress-gray'
  if (percentage < 50) return 'progress-blue'
  if (percentage < 100) return 'progress-yellow'
  return 'progress-green'
}

const formatElapsedTime = (schedule) => {
  if (!schedule.actual_start_time || schedule.status !== 'in_progress') return ''

  const startTime = new Date(schedule.actual_start_time)
  const currentTime = new Date()
  const elapsedMinutes = Math.floor((currentTime - startTime) / (1000 * 60))
  
  const hours = Math.floor(elapsedMinutes / 60)
  const minutes = elapsedMinutes % 60
  
  return hours > 0 ? `${hours}h ${minutes}m elapsed` : `${minutes}m elapsed`
}

const toggleCalendarView = () => {
  viewMode.value = viewMode.value === 'table' ? 'calendar' : 'table'
  if (viewMode.value === 'calendar') {
    loadCalendarSchedules()
  }
}

const previousMonth = () => {
  currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() - 1, 1)
  loadCalendarSchedules()
}

const nextMonth = () => {
  currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() + 1, 1)
  loadCalendarSchedules()
}

const goToToday = () => {
  currentDate.value = new Date()
  loadCalendarSchedules()
}

const formatCalendarTitle = (date) => {
  return date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' })
}

const formatTime = (timeString) => {
  if (!timeString) return ''
  return timeString.slice(0, 5)
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatDateTime = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleString()
}

const formatStatus = (status) => {
  return status.charAt(0).toUpperCase() + status.slice(1).replace(/_/g, ' ')
}

const isSameDay = (date1, date2) => {
  return date1.getDate() === date2.getDate() &&
         date1.getMonth() === date2.getMonth() &&
         date1.getFullYear() === date2.getFullYear()
}

const generateAvatar = (user) => {
  if (!user) {
    return 'https://ui-avatars.com/api/?name=N+A&color=667eea&background=f8f9fa&size=200&font-size=0.6'
  }
  const name = `${user.first_name || ''} ${user.last_name || ''}`
  return `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&color=667eea&background=f8f9fa&size=200&font-size=0.6`
}

const getStatusBadgeColor = (status) => {
  const colorMap = {
    'scheduled': 'badge-secondary',
    'confirmed': 'badge-info',
    'in_progress': 'badge-warning',
    'completed': 'badge-success',
    'cancelled': 'badge-danger'
  }
  return colorMap[status] || 'badge-secondary'
}

const getScheduleCalendarClass = (status) => {
  const classMap = {
    'scheduled': 'cal-scheduled',
    'confirmed': 'cal-confirmed',
    'in_progress': 'cal-progress',
    'completed': 'cal-completed',
    'cancelled': 'cal-cancelled'
  }
  return classMap[status] || 'cal-scheduled'
}

const toggleDropdown = (scheduleId) => {
  activeDropdown.value = activeDropdown.value === scheduleId ? null : scheduleId
}

const onNurseChange = (nurseId) => {
  scheduleForm.value.care_plan_id = ''
  loadCarePlansForNurse(nurseId)
}

const openCreateModal = () => {
  isEditing.value = false
  currentSchedule.value = null
  scheduleForm.value = {
    nurse_id: '',
    care_plan_id: '',
    schedule_date: '',
    start_time: '',
    end_time: '',
    shift_type: '',
    location: '',
    shift_notes: ''
  }
  carePlans.value = []
  showScheduleModal.value = true
  loadNurses()
}

const editSchedule = (schedule) => {
  isEditing.value = true
  currentSchedule.value = schedule
  
  let formattedDate = schedule.schedule_date
  if (formattedDate && !formattedDate.includes('T')) {
    const dateObj = new Date(formattedDate)
    if (!isNaN(dateObj.getTime())) {
      formattedDate = dateObj.toISOString().split('T')[0]
    }
  } else if (formattedDate && formattedDate.includes('T')) {
    formattedDate = formattedDate.split('T')[0]
  }
  
  scheduleForm.value = {
    nurse_id: schedule.nurse_id,
    care_plan_id: schedule.care_plan_id || '',
    schedule_date: formattedDate,
    start_time: schedule.start_time.slice(0, 5),
    end_time: schedule.end_time.slice(0, 5),
    shift_type: schedule.shift_type,
    location: schedule.location || '',
    shift_notes: schedule.shift_notes || ''
  }
  
  showScheduleModal.value = true
  showViewModal.value = false
  loadNurses()
  loadCarePlansForNurse(schedule.nurse_id)
  activeDropdown.value = null
}

const viewSchedule = (schedule) => {
  if (!schedule) return
  selectedSchedule.value = schedule
  showViewModal.value = true
  activeDropdown.value = null
}

const openEndShiftModal = (schedule) => {
  currentSchedule.value = schedule
  showEndShiftModal.value = true
  activeDropdown.value = null
}

const openCancelModal = (schedule) => {
  currentSchedule.value = schedule
  showCancelModal.value = true
  activeDropdown.value = null
}

const closeScheduleModal = () => {
  showScheduleModal.value = false
}

const closeViewModal = () => {
  showViewModal.value = false
  selectedSchedule.value = null
}

const closeEndShiftModal = () => {
  showEndShiftModal.value = false
  currentSchedule.value = null
  endShiftNotes.value = ''
}

const closeCancelModal = () => {
  showCancelModal.value = false
  currentSchedule.value = null
  cancellationReason.value = ''
}

const saveSchedule = async () => {
  isSaving.value = true
  
  try {
    if (isEditing.value) {
      await schedulesService.updateSchedule(currentSchedule.value.id, scheduleForm.value)
    } else {
      await schedulesService.createSchedule(scheduleForm.value)
    }
    
    if (viewMode.value === 'table') {
      await loadSchedules()
    } else {
      await loadCalendarSchedules()
    }
    
    await getStatistics()
    closeScheduleModal()
    toast.showSuccess(isEditing.value ? 'Schedule updated!' : 'Schedule created!')
  } catch (error) {
    console.error('Error saving schedule:', error)
    toast.showError(error.message || 'Failed to save schedule')
  }
  
  isSaving.value = false
}

const endShift = async () => {
  isProcessing.value = true
  
  try {
    await schedulesService.endSchedule(currentSchedule.value.id, {
      shift_notes: endShiftNotes.value
    })
    
    if (viewMode.value === 'table') {
      await loadSchedules()
    } else {
      await loadCalendarSchedules()
    }
    
    await getStatistics()
    closeEndShiftModal()
    toast.showSuccess('Shift ended!')
  } catch (error) {
    console.error('Error:', error)
    toast.showError('Failed to end shift')
  }
  
  isProcessing.value = false
}

const cancelSchedule = async () => {
  isProcessing.value = true
  
  try {
    await schedulesService.cancelSchedule(currentSchedule.value.id, {
      cancellation_reason: cancellationReason.value
    })
    
    if (viewMode.value === 'table') {
      await loadSchedules()
    } else {
      await loadCalendarSchedules()
    }
    
    await getStatistics()
    closeCancelModal()
    toast.showSuccess('Schedule cancelled!')
  } catch (error) {
    console.error('Error:', error)
    toast.showError('Failed to cancel schedule')
  }
  
  isProcessing.value = false
}

const sendReminder = async (schedule) => {
  try {
    await schedulesService.sendReminder(schedule.id)
    toast.showSuccess('Reminder sent!')
  } catch (error) {
    console.error('Error:', error)
    toast.showError('Failed to send reminder')
  }
  activeDropdown.value = null
}

const exportSchedules = async () => {
  try {
    toast.showInfo('Preparing export...')
    
    const filters = {
      nurse_id: nurseFilter.value !== 'all' ? nurseFilter.value : undefined,
      shift_type: shiftTypeFilter.value !== 'all' ? shiftTypeFilter.value : undefined,
      status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
      view: viewFilter.value !== 'all' ? viewFilter.value : undefined,
      search: searchQuery.value || undefined
    }
    
    // Remove undefined values
    Object.keys(filters).forEach(key => filters[key] === undefined && delete filters[key])
    
    const { blob, filename } = await schedulesService.exportSchedules(filters)
    
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = filename
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
    
    toast.showSuccess('Schedules exported!')
  } catch (error) {
    console.error('Error:', error)
    toast.showError('Failed to export')
  }
}

const goToPage = (page) => {
  if (page >= 1 && page <= schedules.value.last_page) {
    loadSchedules(page)
  }
}

const nextPage = () => {
  if (schedules.value.current_page < schedules.value.last_page) {
    goToPage(schedules.value.current_page + 1)
  }
}

const prevPage = () => {
  if (schedules.value.current_page > 1) {
    goToPage(schedules.value.current_page - 1)
  }
}

const getPaginationPages = () => {
  const pages = []
  const maxVisible = 5
  let start = Math.max(1, schedules.value.current_page - Math.floor(maxVisible / 2))
  let end = Math.min(schedules.value.last_page, start + maxVisible - 1)
  
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
    if (viewMode.value === 'table') {
      loadSchedules(1)
    } else {
      loadCalendarSchedules()
    }
  }, 500)
})

watch([nurseFilter, shiftTypeFilter, statusFilter, viewFilter], () => {
  if (viewMode.value === 'table') {
    loadSchedules(1)
  } else {
    loadCalendarSchedules()
  }
})

onMounted(async () => {
  try {
    await Promise.all([
      loadSchedules(),
      loadNurses(),
      getStatistics()
    ])
    
    document.addEventListener('click', handleClickOutside)

    liveUpdateInterval.value = setInterval(() => {
      if (schedules.value.data?.some(s => s.status === 'in_progress')) {
        // Trigger reactive updates
      }
    }, 60000)
  } catch (error) {
    console.error('Mount error:', error)
  }
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
  if (liveUpdateInterval.value) {
    clearInterval(liveUpdateInterval.value)
  }
  clearTimeout(searchDebounceTimer)
})
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

* {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

.schedules-page {
  padding: 32px;
  background: #f8fafc;
  min-height: 100vh;
}

/* Page Header - Same as Patients */
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

/* Calendar Styles */
.calendar-container {
  background: white;
  border-radius: 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  border: 1px solid #f1f5f9;
  padding: 24px;
}

.calendar-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.calendar-nav {
  display: flex;
  align-items: center;
  gap: 16px;
}

.calendar-title {
  font-size: 20px;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
}

.calendar-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 1px;
  background: #e2e8f0;
  border-radius: 12px;
  overflow: hidden;
}

.calendar-day-header {
  background: #f8fafc;
  padding: 12px;
  text-align: center;
  font-weight: 600;
  font-size: 13px;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.calendar-day {
  background: white;
  min-height: 100px;
  padding: 8px;
  cursor: pointer;
  transition: background-color 0.15s;
}

.calendar-day:hover {
  background: #f8fafc;
}

.calendar-day-other-month {
  background: #f8f9fa;
  opacity: 0.5;
}

.calendar-day-today {
  background: #dbeafe;
}

.calendar-day-has-schedules {
  background: #fef3c7;
}

.calendar-day-number {
  font-weight: 600;
  margin-bottom: 8px;
  color: #0f172a;
}

.calendar-day-schedules {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.calendar-schedule-item {
  padding: 4px 6px;
  border-radius: 6px;
  font-size: 11px;
  line-height: 1.3;
  cursor: pointer;
  transition: transform 0.1s;
}

.calendar-schedule-item:hover {
  transform: scale(1.05);
}

.cal-scheduled {
  background: #e0e7ff;
  color: #3730a3;
}

.cal-confirmed {
  background: #3b82f6;
  color: white;
}

.cal-progress {
  background: #f59e0b;
  color: white;
}

.cal-completed {
  background: #10b981;
  color: white;
}

.cal-cancelled {
  background: #ef4444;
  color: white;
}

.schedule-time {
  font-weight: 600;
}

.schedule-nurse {
  opacity: 0.9;
}

.calendar-more-indicator {
  font-size: 11px;
  color: #6b7280;
  text-align: center;
  padding: 4px;
  font-weight: 500;
}

.calendar-legend {
  display: flex;
  justify-content: center;
  gap: 20px;
  margin-top: 24px;
  flex-wrap: wrap;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  font-weight: 500;
}

.legend-color {
  width: 16px;
  height: 16px;
  border-radius: 4px;
}

.bg-blue-500 { background: #3b82f6; }
.bg-yellow-500 { background: #f59e0b; }
.bg-green-500 { background: #10b981; }
.bg-red-500 { background: #ef4444; }

/* Table Styles */
.schedules-table-container {
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

/* Progress Container */
.progress-container {
  display: flex;
  align-items: center;
  gap: 12px;
  min-width: 140px;
}

.progress-bar {
  flex: 1;
  height: 8px;
  background: #e2e8f0;
  border-radius: 4px;
  overflow: hidden;
}

.progress-bar.large {
  height: 12px;
}

.progress-fill {
  height: 100%;
  transition: width 0.3s ease;
  border-radius: 4px;
}

.progress-gray {
  background: #9ca3af;
}

.progress-blue {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
}

.progress-yellow {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.progress-green {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.progress-red {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.progress-text {
  font-size: 13px;
  font-weight: 600;
  color: #334155;
  min-width: 40px;
}

.progress-time {
  font-size: 12px;
  color: #64748b;
  margin-top: 4px;
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

.progress-section {
  margin-top: 24px;
  width: 100%;
}

.progress-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 8px;
  font-size: 13px;
  font-weight: 600;
  color: #334155;
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
}

@media (max-width: 768px) {
  .schedules-page {
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

  .form-grid {
    grid-template-columns: 1fr;
  }

  .calendar-day {
    min-height: 80px;
  }
}
</style>