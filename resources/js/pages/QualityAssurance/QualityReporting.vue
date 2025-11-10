<template>
  <MainLayout>
    <div class="quality-reporting-page">
      <!-- Page Header -->
      <div class="page-header">
        <div class="page-header-content">
          <h1>Quality Assurance Reports</h1>
          <p>Monitor patient satisfaction, nurse performance, incidents, and quality metrics</p>
        </div>
        <div class="page-header-actions">
          <button @click="refreshData" class="btn-modern btn-secondary" :disabled="loading">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Refresh
          </button>
          <button @click="exportCurrentReport" class="btn-modern btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Export
          </button>
          <button @click="openCreateIncidentModal" class="btn-modern btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Add Incident Report
          </button>
        </div>
      </div>

      <!-- Overview Cards -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon satisfaction">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293H15M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Avg Satisfaction</div>
            <div class="stat-value">{{ overview.avg_satisfaction || 0 }}/5</div>
            <div class="stat-change positive">{{ overview.total_feedback || 0 }} responses</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon performance">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Active Nurses</div>
            <div class="stat-value">{{ overview.active_nurses || 0 }}</div>
            <div class="stat-change positive">Currently active</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon incidents">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Total Incidents</div>
            <div class="stat-value">{{ overview.total_incidents || 0 }}</div>
            <div class="stat-change" :class="overview.critical_incidents > 0 ? 'negative' : 'neutral'">
              {{ overview.critical_incidents || 0 }} critical
            </div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon sessions">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Care Sessions</div>
            <div class="stat-value">{{ overview.care_sessions || 0 }}</div>
            <div class="stat-change positive">This period</div>
          </div>
        </div>
      </div>

      <!-- Tabs Navigation -->
      <div class="tabs-container">
        <div class="tabs">
          <button
            @click="activeTab = 'feedback'"
            :class="['tab', { 'tab-active': activeTab === 'feedback' }]"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
            Patient Feedback ({{ patientFeedback.total || 0 }})
          </button>
          <button
            @click="activeTab = 'performance'"
            :class="['tab', { 'tab-active': activeTab === 'performance' }]"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            Nurse Performance ({{ nursePerformance.total || 0 }})
          </button>
          <button
            @click="activeTab = 'incidents'"
            :class="['tab', { 'tab-active': activeTab === 'incidents' }]"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
            Incident Reports ({{ incidentReports.total || 0 }})
          </button>
          <button
            @click="activeTab = 'metrics'"
            :class="['tab', { 'tab-active': activeTab === 'metrics' }]"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            Quality Metrics
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="loading-state">
        <div class="loading-spinner"></div>
        <p class="loading-text">Loading quality assurance data...</p>
      </div>

      <!-- Tab Content -->
      <div v-else>
        <!-- Patient Feedback Tab -->
        <div v-if="activeTab === 'feedback'" class="tab-content">
          <!-- Filters -->
          <div class="filters-section">
            <div class="search-wrapper">
              <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
              <input
                type="text"
                placeholder="Search feedback by patient or nurse name..."
                v-model="feedbackSearch"
                class="search-input"
              />
            </div>
            <div class="filters-group">
              <select v-model="feedbackRatingFilter" class="filter-select">
                <option value="all">All Ratings</option>
                <option value="5">5 Stars</option>
                <option value="4">4 Stars</option>
                <option value="3">3 Stars</option>
                <option value="2">2 Stars</option>
                <option value="1">1 Star</option>
              </select>
              <select v-model="feedbackStatusFilter" class="filter-select">
                <option value="all">All Status</option>
                <option value="pending">Pending Response</option>
                <option value="responded">Responded</option>
              </select>
            </div>
          </div>

          <!-- Feedback Table -->
          <div class="table-container">
            <div v-if="filteredFeedback.length > 0" class="table-wrapper">
              <table class="modern-table">
                <thead>
                  <tr>
                    <th>Patient</th>
                    <th>Nurse</th>
                    <th>Rating</th>
                    <th>Feedback</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="feedback in filteredFeedback" :key="feedback.id">
                    <td>
                      <div class="user-cell">
                        <img :src="getPatientAvatar(feedback.patient_name)" class="user-avatar-table" />
                        <div class="user-details-table">
                          <div class="user-name-table">{{ feedback.patient_name }}</div>
                        </div>
                      </div>
                    </td>
                    <td>
                      <div class="user-name-table">{{ feedback.nurse_name }}</div>
                    </td>
                    <td>
                      <div class="rating-display">
                        <div class="stars">
                          <span v-for="i in 5" :key="i" :class="['star', { 'star-filled': i <= feedback.rating }]">★</span>
                        </div>
                        <span class="rating-number">{{ feedback.rating }}/5</span>
                      </div>
                    </td>
                    <td>
                      <div class="feedback-text">
                        {{ feedback.feedback_text.length > 100 ? feedback.feedback_text.substring(0, 100) + '...' : feedback.feedback_text }}
                      </div>
                    </td>
                    <td>
                      <div class="contact-cell">
                        <div class="contact-primary">{{ formatDate(feedback.feedback_date) }}</div>
                        <div class="contact-secondary">{{ formatTime(feedback.feedback_date) }}</div>
                      </div>
                    </td>
                    <td>
                      <span :class="'modern-badge ' + getFeedbackStatusBadgeColor(feedback.status)">
                        {{ capitalizeFirst(feedback.status) }}
                      </span>
                    </td>
                    <td>
                      <div class="action-cell">
                        <button @click="toggleDropdown('feedback', feedback.id)" class="action-btn">
                          <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                          </svg>
                        </button>
                        
                        <div v-show="activeDropdown === `feedback-${feedback.id}`" class="modern-dropdown">
                          <button @click="viewFeedback(feedback)" class="dropdown-item-modern">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            View Details
                          </button>
                          <button
                            v-if="feedback.status === 'pending'"
                            @click="respondToFeedback(feedback)"
                            class="dropdown-item-modern success"
                          >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                            </svg>
                            Respond
                          </button>
                        </div>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div v-else class="empty-state">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
              </svg>
              <h3>No feedback found</h3>
              <p>Try adjusting your search or filters.</p>
            </div>
          </div>
        </div>

        <!-- Nurse Performance Tab -->
        <div v-if="activeTab === 'performance'" class="tab-content">
          <!-- Performance Filters -->
          <div class="filters-section">
            <div class="search-wrapper">
              <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
              <input
                type="text"
                placeholder="Search nurses by name or license..."
                v-model="performanceSearch"
                class="search-input"
              />
            </div>
            <div class="filters-group">
              <select v-model="performanceTimeframe" @change="loadNursePerformance" class="filter-select">
                <option value="7">Last 7 Days</option>
                <option value="30">Last 30 Days</option>
                <option value="90">Last 90 Days</option>
                <option value="365">Last Year</option>
              </select>
              <select v-model="performanceGradeFilter" class="filter-select">
                <option value="all">All Grades</option>
                <option value="A">Grade A</option>
                <option value="B">Grade B</option>
                <option value="C">Grade C</option>
                <option value="D">Grade D</option>
                <option value="F">Grade F</option>
              </select>
            </div>
          </div>

          <!-- Performance Table -->
          <div class="table-container">
            <div v-if="filteredNursePerformance.length > 0" class="table-wrapper">
              <table class="modern-table">
                <thead>
                  <tr>
                    <th>Nurse</th>
                    <th>Care Sessions</th>
                    <th>Avg Rating</th>
                    <th>Total Hours</th>
                    <th>Incidents</th>
                    <th>Overall Score</th>
                    <th>Grade</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="nurse in filteredNursePerformance" :key="nurse.id">
                    <td>
                      <div class="user-cell">
                        <img :src="nurse.avatar_url" class="user-avatar-table" />
                        <div class="user-details-table">
                          <div class="user-name-table">{{ nurse.name }}</div>
                          <div class="user-id-table">{{ nurse.license_number }}</div>
                        </div>
                      </div>
                    </td>
                    <td>
                      <div class="metric-value">{{ nurse.care_sessions }}</div>
                    </td>
                    <td>
                      <div class="rating-display">
                        <div class="stars">
                          <span v-for="i in 5" :key="i" :class="['star', { 'star-filled': i <= Math.floor(nurse.avg_rating) }]">★</span>
                        </div>
                        <span class="rating-number">{{ nurse.avg_rating }}/5</span>
                      </div>
                    </td>
                    <td>
                      <div class="metric-value">{{ nurse.total_hours }}h</div>
                    </td>
                    <td>
                      <span :class="'modern-badge ' + getIncidentCountBadgeColor(nurse.incident_count)">
                        {{ nurse.incident_count }}
                      </span>
                    </td>
                    <td>
                      <div class="progress-container">
                        <div class="progress-bar">
                          <div class="progress-fill" :class="getScoreColor(nurse.overall_score)" :style="{ width: nurse.overall_score + '%' }"></div>
                        </div>
                        <span class="progress-text">{{ nurse.overall_score }}%</span>
                      </div>
                    </td>
                    <td>
                      <span :class="'modern-badge ' + getGradeBadgeColor(nurse.performance_grade)">
                        {{ nurse.performance_grade }}
                      </span>
                    </td>
                    <td>
                      <button @click="viewNurseDetails(nurse)" class="btn-modern btn-secondary btn-sm">
                        View Details
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
<!-- ADD PAGINATION HERE -->
      <div v-if="nursePerformance.last_page > 1" class="pagination-container">
        <div class="pagination-info">
          Showing {{ (nursePerformance.current_page - 1) * nursePerformance.per_page + 1 }} to {{ Math.min(nursePerformance.current_page * nursePerformance.per_page, nursePerformance.total) }} of {{ nursePerformance.total }} nurses
        </div>
        <div class="pagination-controls">
          <button 
            @click="prevPerformancePage" 
            :disabled="nursePerformance.current_page === 1"
            class="pagination-btn"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Previous
          </button>
          
          <div class="pagination-pages">
            <button
              v-for="page in getPerformancePaginationPages()"
              :key="page"
              @click="goToPerformancePage(page)"
              :class="['pagination-page', { active: page === nursePerformance.current_page }]"
            >
              {{ page }}
            </button>
          </div>
          
          <button 
            @click="nextPerformancePage" 
            :disabled="nursePerformance.current_page === nursePerformance.last_page"
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
              <h3>No performance data</h3>
              <p>Try adjusting your search or filters.</p>
            </div>
          </div>
        </div>

        <!-- Incident Reports Tab -->
        <div v-if="activeTab === 'incidents'" class="tab-content">
          <!-- Incident Filters -->
          <div class="filters-section">
            <div class="search-wrapper">
              <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
              <input
                type="text"
                placeholder="Search incidents by title, patient, or description..."
                v-model="incidentSearch"
                class="search-input"
              />
            </div>
            <div class="filters-group">
              <select v-model="incidentSeverityFilter" class="filter-select">
                <option value="all">All Severity</option>
                <option value="critical">Critical</option>
                <option value="high">High</option>
                <option value="medium">Medium</option>
                <option value="low">Low</option>
              </select>
              <select v-model="incidentStatusFilter" class="filter-select">
                <option value="all">All Status</option>
                <option value="pending">Pending</option>
                <option value="investigated">Investigating</option>
                <option value="resolved">Resolved</option>
                <option value="closed">Closed</option>
              </select>
              <select v-model="incidentCategoryFilter" class="filter-select">
                <option value="all">All Categories</option>
                <option value="medication">Medication</option>
                <option value="fall">Fall</option>
                <option value="equipment">Equipment</option>
                <option value="procedure">Procedure</option>
                <option value="emergency">Emergency</option>
                <option value="other">Other</option>
              </select>
            </div>
          </div>

          <!-- Incidents Table -->
          <div class="table-container">
            <div v-if="filteredIncidents.length > 0" class="table-wrapper">
              <table class="modern-table">
                <thead>
                  <tr>
                    <th>Incident</th>
                    <th>Patient/Nurse</th>
                    <th>Category</th>
                    <th>Severity</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="incident in filteredIncidents" :key="incident.id">
                    <td>
                      <div class="incident-info">
                        <div class="incident-title">{{ incident.title }}</div>
                        <div class="incident-description">
                          {{ incident.description.length > 80 ? incident.description.substring(0, 80) + '...' : incident.description }}
                        </div>
                      </div>
                    </td>
                    <td>
                      <div class="contact-cell">
                        <div class="contact-primary">{{ incident.patient_name }}</div>
                        <div class="contact-secondary">{{ incident.nurse_name }}</div>
                      </div>
                    </td>
                    <td>
                      <span :class="'modern-badge ' + getCategoryBadgeColor(incident.category)">
                        {{ capitalizeFirst(incident.incident_type) }}
                      </span>
                    </td>
                    <td>
                      <span :class="'modern-badge ' + getSeverityBadgeColor(incident.severity)">
                        {{ capitalizeFirst(incident.severity) }}
                      </span>
                    </td>
                    <td>
                      <div class="contact-cell">
                        <div class="contact-primary">{{ formatDate(incident.incident_date) }}</div>
                        <div class="contact-secondary">{{ incident.incident_time }}</div>
                      </div>
                    </td>
                    <td>
                      <span :class="'modern-badge ' + getIncidentStatusBadgeColor(incident.status)">
                        {{ capitalizeFirst(incident.status) }}
                      </span>
                    </td>
                    <td>
                      <div class="action-cell">
                        <button @click="toggleDropdown('incident', incident.id)" class="action-btn">
                          <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                          </svg>
                        </button>
                        
                        <div v-show="activeDropdown === `incident-${incident.id}`" class="modern-dropdown">
                          <button @click="viewIncident(incident)" class="dropdown-item-modern">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            View Details
                          </button>
                          
                          <button 
                            v-if="incident.status !== 'closed'"
                            @click="editIncident(incident)" 
                            class="dropdown-item-modern"
                          >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Edit Incident
                          </button>
                          
                          <button 
                            v-if="incident.status !== 'closed'"
                            @click="updateIncidentStatus(incident)" 
                            class="dropdown-item-modern warning"
                          >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5" />
                            </svg>
                            Update Status
                          </button>
                          
                          <div v-if="incident.status !== 'closed'" class="dropdown-divider"></div>
                          
                          <button 
                            v-if="incident.status !== 'closed'"
                            @click="openDeleteIncidentModal(incident)" 
                            class="dropdown-item-modern danger"
                          >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Delete Incident
                          </button>
                        </div>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>

              <!-- Pagination -->
              <div v-if="incidentReports.last_page > 1" class="pagination-container">
                <div class="pagination-info">
                  Showing {{ (incidentReports.current_page - 1) * incidentReports.per_page + 1 }} to {{ Math.min(incidentReports.current_page * incidentReports.per_page, incidentReports.total) }} of {{ incidentReports.total }} incidents
                </div>
                <div class="pagination-controls">
                  <button 
                    @click="prevIncidentPage" 
                    :disabled="incidentReports.current_page === 1"
                    class="pagination-btn"
                  >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Previous
                  </button>
                  
                  <div class="pagination-pages">
                    <button
                      v-for="page in getIncidentPaginationPages()"
                      :key="page"
                      @click="goToIncidentPage(page)"
                      :class="['pagination-page', { active: page === incidentReports.current_page }]"
                    >
                      {{ page }}
                    </button>
                  </div>
                  
                  <button 
                    @click="nextIncidentPage" 
                    :disabled="incidentReports.current_page === incidentReports.last_page"
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
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z" />
              </svg>
              <h3>No incidents found</h3>
              <p>Try adjusting your search or filters.</p>
            </div>
          </div>
        </div>

        <!-- Quality Metrics Tab -->
        <div v-if="activeTab === 'metrics'" class="tab-content">
          <div class="metrics-grid">
            <!-- Patient Satisfaction Metrics -->
            <div class="metric-card-large">
              <div class="metric-header">
                <h3>Patient Satisfaction</h3>
                <svg class="metric-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293H15M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div class="metric-content">
                <div class="primary-metric">
                  <span class="metric-value-large">{{ qualityMetrics.patient_satisfaction?.average_rating || 0 }}/5</span>
                  <span class="metric-label">Average Rating</span>
                </div>
                <div class="secondary-metrics">
                  <div class="secondary-metric">
                    <span class="value">{{ qualityMetrics.patient_satisfaction?.total_responses || 0 }}</span>
                    <span class="label">Total Responses</span>
                  </div>
                  <div class="secondary-metric">
                    <span class="value">{{ qualityMetrics.patient_satisfaction?.feedback_response_rate || 0 }}%</span>
                    <span class="label">Response Rate</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Care Quality Metrics -->
            <div class="metric-card-large">
              <div class="metric-header">
                <h3>Care Quality</h3>
                <svg class="metric-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div class="metric-content">
                <div class="care-metrics-grid">
                  <div class="care-metric">
                    <div class="care-metric-value">{{ qualityMetrics.care_quality?.care_plan_adherence || 0 }}%</div>
                    <div class="care-metric-label">Care Plan Adherence</div>
                  </div>
                  <div class="care-metric">
                    <div class="care-metric-value">{{ qualityMetrics.care_quality?.medication_compliance || 0 }}%</div>
                    <div class="care-metric-label">Medication Compliance</div>
                  </div>
                  <div class="care-metric">
                    <div class="care-metric-value">{{ qualityMetrics.care_quality?.vitals_monitoring_consistency || 0 }}%</div>
                    <div class="care-metric-label">Vitals Monitoring</div>
                  </div>
                  <div class="care-metric">
                    <div class="care-metric-value">{{ qualityMetrics.care_quality?.documentation_completeness || 0 }}%</div>
                    <div class="care-metric-label">Documentation</div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Safety Metrics -->
            <div class="metric-card-large">
              <div class="metric-header">
                <h3>Safety Metrics</h3>
                <svg class="metric-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
              </div>
              <div class="metric-content">
                <div class="safety-overview">
                  <div class="safety-metric">
                    <span class="value">{{ qualityMetrics.safety_metrics?.total_incidents || 0 }}</span>
                    <span class="label">Total Incidents</span>
                  </div>
                  <div class="safety-metric">
                    <span class="value critical">{{ qualityMetrics.safety_metrics?.incidents_by_severity?.critical || 0 }}</span>
                    <span class="label">Critical</span>
                  </div>
                  <div class="safety-metric">
                    <span class="value">{{ qualityMetrics.safety_metrics?.resolution_time_avg || 0 }}h</span>
                    <span class="label">Avg Resolution</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>


      <!-- View Feedback Details Modal -->
<div v-if="showViewFeedbackModal && currentFeedback" class="modal-overlay" @click.self="closeViewFeedbackModal">
  <div class="modal modal-md">
    <div class="modal-header">
      <h3 class="modal-title">
        <svg class="modal-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
        </svg>
        Feedback Details
      </h3>
      <button @click="closeViewFeedbackModal" class="modal-close">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <div class="modal-body">
      <div class="feedback-details">
        <!-- Rating Section -->
        <div class="feedback-detail-section">
          <h5>Rating</h5>
          <div class="rating-display-large">
            <div class="stars-large">
              <span v-for="i in 5" :key="i" :class="['star-large', { 'star-filled': i <= currentFeedback.rating }]">★</span>
            </div>
            <span class="rating-number-large">{{ currentFeedback.rating }}/5</span>
          </div>
        </div>

        <!-- People Section -->
        <div class="feedback-detail-section">
          <h5>Details</h5>
          <div class="detail-grid">
            <div class="detail-item">
              <label>Patient:</label>
              <span>{{ currentFeedback.patient_name }}</span>
            </div>
            <div class="detail-item">
              <label>Nurse:</label>
              <span>{{ currentFeedback.nurse_name }}</span>
            </div>
            <div class="detail-item">
              <label>Care Date:</label>
              <span>{{ formatDate(currentFeedback.care_date) }}</span>
            </div>
            <div class="detail-item">
              <label>Feedback Date:</label>
              <span>{{ formatDateTime(currentFeedback.feedback_date) }}</span>
            </div>
            <div class="detail-item">
              <label>Would Recommend:</label>
              <span :class="currentFeedback.would_recommend ? 'text-success' : 'text-muted'">
                {{ currentFeedback.would_recommend ? 'Yes' : 'No' }}
              </span>
            </div>
            <div class="detail-item">
              <label>Status:</label>
              <span :class="'modern-badge ' + getFeedbackStatusBadgeColor(currentFeedback.status)">
                {{ capitalizeFirst(currentFeedback.status) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Feedback Text Section -->
        <div class="feedback-detail-section">
          <h5>Feedback</h5>
          <div class="feedback-text-full">
            {{ currentFeedback.feedback_text }}
          </div>
        </div>

        <!-- Response Section (if responded) -->
        <div v-if="currentFeedback.status === 'responded' && currentFeedback.response_text" class="feedback-detail-section">
          <h5>Admin Response</h5>
          <div class="response-container">
            <div class="response-header">
              <div class="response-meta">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Responded on {{ formatDateTime(currentFeedback.responded_at) }}
              </div>
              <span class="modern-badge badge-success">Resolved</span>
            </div>
            <div class="response-content">
              <p class="response-text">{{ currentFeedback.response_text }}</p>
              <div v-if="currentFeedback.responded_by_name" class="response-footer">
                Responded by: <strong>{{ currentFeedback.responded_by_name }}</strong>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="modal-actions">
      <button @click="closeViewFeedbackModal" class="btn-modern btn-secondary">
        Close
      </button>
      <button
        v-if="currentFeedback.status === 'pending'"
        @click="respondToFeedbackFromView"
        class="btn-modern btn-primary"
      >
        Respond to Feedback
      </button>
    </div>
  </div>
</div>

      <!-- Nurse Performance Details Modal -->
      <div v-if="showNurseDetailsModal && currentNurse" class="modal-overlay" @click.self="closeNurseDetailsModal">
        <div class="modal modal-xl">
          <div class="modal-header">
            <h2 class="modal-title">
              <svg class="modal-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
              Nurse Performance Details
            </h2>
            <button @click="closeNurseDetailsModal" class="modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="modal-body">
            <div class="nurse-performance-details">
              <!-- Nurse Profile Section -->
              <div class="nurse-profile-section">
                <div class="nurse-profile-card">
                  <img
                    class="profile-avatar-large"
                    :src="currentNurse.avatar_url"
                    :alt="currentNurse.name"
                  />
                  <h3 class="profile-name">{{ currentNurse.name }}</h3>
                  <div class="profile-badges">
                    <span class="modern-badge badge-success">Nurse</span>
                    <span :class="'modern-badge ' + getGradeBadgeColor(currentNurse.performance_grade)">
                      Grade {{ currentNurse.performance_grade }}
                    </span>
                  </div>
                  <div class="profile-contact">
                    <div class="contact-item">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                      </svg>
                      <span>{{ currentNurse.license_number }}</span>
                    </div>
                    <div class="contact-item" v-if="currentNurse.specialization">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                      </svg>
                      <span>{{ currentNurse.specialization }}</span>
                    </div>
                    <div class="contact-item">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      <span>Last active: {{ currentNurse.last_activity }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Performance Details Section -->
              <div class="performance-details-section">
                <!-- Overall Performance Score -->
                <div class="performance-overview">
                  <div class="score-card">
                    <div class="score-header">
                      <h4>Overall Performance Score</h4>
                      <span :class="'modern-badge ' + getGradeBadgeColor(currentNurse.performance_grade)">
                        Grade {{ currentNurse.performance_grade }}
                      </span>
                    </div>
                    <div class="score-display-large">
                      <div class="score-circle">
                        <svg class="score-circle-svg" viewBox="0 0 36 36">
                          <path class="score-circle-bg"
                            d="M18 2.0845
                              a 15.9155 15.9155 0 0 1 0 31.831
                              a 15.9155 15.9155 0 0 1 0 -31.831"
                          />
                          <path class="score-circle-progress"
                            :stroke-dasharray="`${currentNurse.overall_score}, 100`"
                            d="M18 2.0845
                              a 15.9155 15.9155 0 0 1 0 31.831
                              a 15.9155 15.9155 0 0 1 0 -31.831"
                          />
                        </svg>
                        <div class="score-text">{{ currentNurse.overall_score }}%</div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Detailed Metrics -->
                <div class="metrics-section">
                  <h4 class="section-header">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    Performance Metrics
                  </h4>
                  <div class="metrics-grid-detail">
                    <div class="metric-detail-card">
                      <div class="metric-icon patient-care">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                      </div>
                      <div class="metric-info">
                        <div class="metric-value">{{ currentNurse.care_sessions }}</div>
                        <div class="metric-label">Care Sessions</div>
                      </div>
                    </div>

                    <div class="metric-detail-card">
                      <div class="metric-icon hours">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                      </div>
                      <div class="metric-info">
                        <div class="metric-value">{{ currentNurse.total_hours }}h</div>
                        <div class="metric-label">Total Hours</div>
                      </div>
                    </div>

                    <div class="metric-detail-card">
                      <div class="metric-icon rating">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                        </svg>
                      </div>
                      <div class="metric-info">
                        <div class="metric-value">{{ currentNurse.avg_rating }}/5</div>
                        <div class="metric-label">Avg Rating</div>
                      </div>
                    </div>

                    <div class="metric-detail-card">
                      <div class="metric-icon punctuality">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                      </div>
                      <div class="metric-info">
                        <div class="metric-value">{{ currentNurse.punctuality_score }}%</div>
                        <div class="metric-label">Punctuality</div>
                      </div>
                    </div>

                    <div class="metric-detail-card">
                      <div class="metric-icon care-plans">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                      </div>
                      <div class="metric-info">
                        <div class="metric-value">{{ currentNurse.active_care_plans }}</div>
                        <div class="metric-label">Active Care Plans</div>
                      </div>
                    </div>

                    <div class="metric-detail-card">
                      <div class="metric-icon incidents">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                      </div>
                      <div class="metric-info">
                        <div class="metric-value">{{ currentNurse.incident_count }}</div>
                        <div class="metric-label">Incident Reports</div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Patient Feedback Summary -->
                <div class="feedback-section">
                  <h4 class="section-header">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                    Patient Feedback Summary
                  </h4>
                  <div class="feedback-summary-grid">
                    <div class="feedback-stat">
                      <div class="feedback-stat-value">{{ currentNurse.feedback_count }}</div>
                      <div class="feedback-stat-label">Total Feedback</div>
                    </div>
                    <div class="feedback-stat">
                      <div class="rating-display">
                        <div class="stars">
                          <span v-for="i in 5" :key="i" :class="['star', { 'star-filled': i <= Math.floor(currentNurse.avg_rating) }]">★</span>
                        </div>
                        <span class="rating-number">{{ currentNurse.avg_rating }}/5</span>
                      </div>
                      <div class="feedback-stat-label">Average Rating</div>
                    </div>
                  </div>
                </div>

                <!-- Performance Breakdown -->
                <div class="performance-breakdown">
                  <h4 class="section-header">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    Performance Breakdown
                  </h4>
                  <div class="breakdown-items">
                    <div class="breakdown-item">
                      <div class="breakdown-label">Patient Ratings</div>
                      <div class="breakdown-bar">
                        <div class="breakdown-fill" :style="{ width: (currentNurse.avg_rating / 5) * 100 + '%' }"></div>
                      </div>
                      <div class="breakdown-score">{{ Math.round((currentNurse.avg_rating / 5) * 100) }}%</div>
                    </div>
                    <div class="breakdown-item">
                      <div class="breakdown-label">Punctuality</div>
                      <div class="breakdown-bar">
                        <div class="breakdown-fill" :style="{ width: currentNurse.punctuality_score + '%' }"></div>
                      </div>
                      <div class="breakdown-score">{{ currentNurse.punctuality_score }}%</div>
                    </div>
                    <div class="breakdown-item">
                      <div class="breakdown-label">Safety Record</div>
                      <div class="breakdown-bar">
                        <div class="breakdown-fill" :style="{ width: Math.max(0, 100 - (currentNurse.incident_count * 20)) + '%' }"></div>
                      </div>
                      <div class="breakdown-score">{{ Math.max(0, 100 - (currentNurse.incident_count * 20)) }}%</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="modal-actions">
            <button @click="closeNurseDetailsModal" class="btn-modern btn-secondary">
              Close
            </button>
            <button @click="viewNurseFeedback" class="btn-modern btn-primary">
              View All Feedback
            </button>
          </div>
        </div>
      </div>

      <!-- View Incident Modal -->
      <div v-if="showIncidentModal && currentIncident" class="modal-overlay" @click.self="closeIncidentModal">
        <div class="modal modal-lg">
          <div class="modal-header">
            <h3 class="modal-title">
              <svg class="modal-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z" />
              </svg>
              Incident Report Details
            </h3>
            <button @click="closeIncidentModal" class="modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="modal-body">
            <div class="incident-details">
              <div class="incident-header">
                <h4>{{ currentIncident.title }}</h4>
                <div class="incident-badges">
                  <span :class="'modern-badge ' + getSeverityBadgeColor(currentIncident.severity)">
                    {{ capitalizeFirst(currentIncident.severity) }}
                  </span>
                  <span :class="'modern-badge ' + getIncidentStatusBadgeColor(currentIncident.status)">
                    {{ capitalizeFirst(currentIncident.status) }}
                  </span>
                </div>
              </div>

              <div class="incident-sections">
                <div class="incident-section">
                  <h5>Description</h5>
                  <p>{{ currentIncident.description }}</p>
                </div>

                <div class="incident-section">
                  <h5>Involved Parties</h5>
                  <div class="parties-grid">
                    <div class="party-detail">
                      <label>Patient:</label>
                      <span>{{ currentIncident.patient_name }}</span>
                    </div>
                    <div class="party-detail">
                      <label>Assigned Nurse:</label>
                      <span>{{ currentIncident.nurse_name }}</span>
                    </div>
                    <div class="party-detail">
                      <label>Reported By:</label>
                      <span>{{ currentIncident.reporter_name }}</span>
                    </div>
                  </div>
                </div>

                <div v-if="currentIncident.actions_taken" class="incident-section">
                  <h5>Actions Taken</h5>
                  <p>{{ currentIncident.actions_taken }}</p>
                </div>

                <div class="incident-section">
                  <h5>Timeline</h5>
                  <div class="timeline-grid">
                    <div class="timeline-item">
                      <label>Incident Date:</label>
                      <span>{{ formatDateTime(currentIncident.incident_date) }}</span>
                    </div>
                    <div class="timeline-item">
                      <label>Reported:</label>
                      <span>{{ formatDateTime(currentIncident.reported_at) }}</span>
                    </div>
                    <div v-if="currentIncident.follow_up_date" class="timeline-item">
                      <label>Follow-up Due:</label>
                      <span>{{ formatDate(currentIncident.follow_up_date) }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="modal-actions">
            <button @click="closeIncidentModal" class="btn-modern btn-secondary">
              Close
            </button>
            <button 
              v-if="currentIncident.status !== 'closed'"
              @click="editIncidentFromView" 
              class="btn-modern btn-primary"
            >
              Edit Incident
            </button>
          </div>
        </div>
      </div>

      <!-- Update Incident Status Modal -->
      <div v-if="showUpdateIncidentModal && currentIncident" class="modal-overlay" @click.self="closeUpdateIncidentModal">
        <div class="modal modal-sm">
          <div class="modal-header">
            <h3 class="modal-title">Update Incident Status</h3>
            <button @click="closeUpdateIncidentModal" class="modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          
          <form @submit.prevent="submitIncidentUpdate">
            <div class="modal-body">
              <div class="form-group">
                <label>Status</label>
                <select v-model="incidentUpdateForm.status" required class="filter-select" style="width: 100%;">
                  <option value="pending">Pending</option>
                  <option value="investigated">Investigating</option>
                  <option value="resolved">Resolved</option>
                  <option value="closed">Closed</option>
                </select>
              </div>

              <div class="form-group">
                <label>Actions Taken</label>
                <textarea
                  v-model="incidentUpdateForm.actions_taken"
                  rows="3"
                  placeholder="Describe actions taken to address this incident..."
                  class="search-input"
                  style="padding: 10px 14px; min-height: 80px;"
                ></textarea>
              </div>

              <div v-if="incidentUpdateForm.follow_up_required" class="form-group">
                <label>Follow-up Date</label>
                <input
                  type="date"
                  v-model="incidentUpdateForm.follow_up_date"
                  :min="new Date().toISOString().split('T')[0]"
                  class="search-input"
                />
              </div>
            </div>

            <div class="modal-actions">
              <button type="button" @click="closeUpdateIncidentModal" class="btn-modern btn-secondary">
                Cancel
              </button>
              <button
                type="submit"
                :disabled="isSaving"
                class="btn-modern btn-primary"
              >
                <div v-if="isSaving" class="spinner"></div>
                Update Status
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Delete Incident Confirmation Modal -->
      <div v-if="showDeleteIncidentModal && currentIncident" class="modal-overlay" @click.self="closeDeleteIncidentModal">
        <div class="modal modal-sm">
          <div class="modal-header modal-header-danger">
            <h3 class="modal-title">
              <svg class="modal-icon modal-icon-danger" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z" />
              </svg>
              Delete Incident Report
            </h3>
            <button @click="closeDeleteIncidentModal" class="modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          
          <div class="modal-body">
            <p>
              Are you sure you want to delete this incident report? This action cannot be undone.
            </p>
            <div class="incident-summary-box">
              <div class="summary-item">
                <strong>Incident:</strong> {{ currentIncident.title }}
              </div>
              <div class="summary-item">
                <strong>Patient:</strong> {{ currentIncident.patient_name }}
              </div>
              <div class="summary-item">
                <strong>Date:</strong> {{ formatDateTime(currentIncident.incident_date) }}
              </div>
            </div>
          </div>

          <div class="modal-actions">
            <button @click="closeDeleteIncidentModal" class="btn-modern btn-secondary">
              Cancel
            </button>
            <button
              @click="deleteIncident"
              :disabled="isSaving"
              class="btn-modern btn-danger"
            >
              <div v-if="isSaving" class="spinner"></div>
              Delete Incident
            </button>
          </div>
        </div>
      </div>

      <!-- Respond to Feedback Modal -->
      <div v-if="showResponseModal && currentFeedback" class="modal-overlay" @click.self="closeResponseModal">
        <div class="modal modal-sm">
          <div class="modal-header">
            <h3 class="modal-title">
              <svg class="modal-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
              </svg>
              Respond to Feedback
            </h3>
            <button @click="closeResponseModal" class="modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          
          <form @submit.prevent="submitResponse">
            <div class="modal-body">
              <div class="feedback-summary-box">
                <p><strong>Patient:</strong> {{ currentFeedback.patient_name }}</p>
                <p><strong>Nurse:</strong> {{ currentFeedback.nurse_name }}</p>
                <p><strong>Rating:</strong> {{ currentFeedback.rating }}/5</p>
                <p><strong>Feedback:</strong> {{ currentFeedback.feedback_text }}</p>
              </div>
              
              <div class="form-group">
                <label>Your Response</label>
                <textarea
                  v-model="responseText"
                  rows="4"
                  placeholder="Enter your response to this feedback..."
                  required
                  class="search-input"
                  style="padding: 10px 14px; min-height: 100px; width: 100%;"
                ></textarea>
              </div>
            </div>

            <div class="modal-actions">
              <button type="button" @click="closeResponseModal" class="btn-modern btn-secondary">
                Cancel
              </button>
              <button
                type="submit"
                :disabled="isSaving"
                class="btn-modern btn-primary"
              >
                <div v-if="isSaving" class="spinner"></div>
                Send Response
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
import * as qualityReportsService from '../../services/qualityReportsService'

const toast = inject('toast')

// Reactive data
const loading = ref(true)
const activeTab = ref('feedback')
const overview = ref({})
const qualityMetrics = ref({})
const showViewFeedbackModal = ref(false)

// Patient Feedback data
const patientFeedback = ref({ data: [], total: 0, per_page: 15, current_page: 1, last_page: 1 })
const feedbackSearch = ref('')
const feedbackRatingFilter = ref('all')
const feedbackStatusFilter = ref('all')

// Nurse Performance data
const nursePerformance = ref([])
const performanceSearch = ref('')
const performanceTimeframe = ref('30')
const performanceGradeFilter = ref('all')

// Incident Reports data
const incidentReports = ref({ data: [], total: 0, per_page: 15, current_page: 1, last_page: 1 })
const incidentSearch = ref('')
const incidentSeverityFilter = ref('all')
const incidentStatusFilter = ref('all')
const incidentCategoryFilter = ref('all')

// Users data for dropdown selections
const patients = ref([])
const nurses = ref([])
const admins = ref([])

// Modal states
const showResponseModal = ref(false)
const showIncidentModal = ref(false)
const showUpdateIncidentModal = ref(false)
const showCreateIncidentModal = ref(false)
const showDeleteIncidentModal = ref(false)
const showNurseDetailsModal = ref(false)
const currentFeedback = ref(null)
const currentIncident = ref(null)
const currentNurse = ref(null)
const responseText = ref('')
const isSaving = ref(false)
const isEditingIncident = ref(false)

// Dropdown state
const activeDropdown = ref(null)

// Computed properties
const filteredFeedback = computed(() => {
  if (!patientFeedback.value.data) return []
  
  return patientFeedback.value.data.filter(feedback => {
    const matchesSearch = !feedbackSearch.value || 
      feedback.patient_name.toLowerCase().includes(feedbackSearch.value.toLowerCase()) ||
      feedback.nurse_name.toLowerCase().includes(feedbackSearch.value.toLowerCase()) ||
      feedback.feedback_text.toLowerCase().includes(feedbackSearch.value.toLowerCase())
    
    const matchesRating = feedbackRatingFilter.value === 'all' || 
      feedback.rating.toString() === feedbackRatingFilter.value
    
    const matchesStatus = feedbackStatusFilter.value === 'all' || 
      feedback.status === feedbackStatusFilter.value
    
    return matchesSearch && matchesRating && matchesStatus
  })
})

const filteredNursePerformance = computed(() => {
  console.log('nursePerformance.value:', nursePerformance.value)
  console.log('nursePerformance.value.data:', nursePerformance.value.data)
  const result = nursePerformance.value.data || []
  console.log('filteredNursePerformance result:', result)
  console.log('filteredNursePerformance length:', result.length)
  return result
})

const filteredIncidents = computed(() => {
  if (!incidentReports.value.data) return []
  
  return incidentReports.value.data.filter(incident => {
    const matchesSearch = !incidentSearch.value || 
      incident.title.toLowerCase().includes(incidentSearch.value.toLowerCase()) ||
      incident.patient_name.toLowerCase().includes(incidentSearch.value.toLowerCase()) ||
      incident.description.toLowerCase().includes(incidentSearch.value.toLowerCase())
    
    const matchesSeverity = incidentSeverityFilter.value === 'all' || 
      incident.severity === incidentSeverityFilter.value
    
    const matchesStatus = incidentStatusFilter.value === 'all' || 
      incident.status === incidentStatusFilter.value
    
    const matchesCategory = incidentCategoryFilter.value === 'all' || 
      incident.category === incidentCategoryFilter.value
    
    return matchesSearch && matchesSeverity && matchesStatus && matchesCategory
  })
})

// Methods
const loadOverviewData = async () => {
  try {
    const response = await qualityReportsService.getQualityOverview()
    overview.value = response.data.overview || {}
  } catch (error) {
    console.error('Error loading overview data:', error)
  }
}

const loadPatientFeedback = async (page = 1) => {
  try {
    const filters = {
      page: page,
      per_page: 15,
      rating: feedbackRatingFilter.value !== 'all' ? feedbackRatingFilter.value : undefined,
      status: feedbackStatusFilter.value !== 'all' ? feedbackStatusFilter.value : undefined,
      search: feedbackSearch.value || undefined
    }
    
    Object.keys(filters).forEach(key => filters[key] === undefined && delete filters[key])
    
    const response = await qualityReportsService.getPatientFeedback(filters)
    patientFeedback.value = response.data || { data: [], total: 0, current_page: 1, last_page: 1, per_page: 15 }
  } catch (error) {
    console.error('Error loading patient feedback:', error)
  }
}

const loadNursePerformance = async (page = 1) => {
  try {
    const filters = {
      page: page,
      per_page: 15,
      timeframe: performanceTimeframe.value,
      grade: performanceGradeFilter.value !== 'all' ? performanceGradeFilter.value : undefined,
      search: performanceSearch.value || undefined
    }
    
    Object.keys(filters).forEach(key => filters[key] === undefined && delete filters[key])
    
    console.log('Loading nurse performance with filters:', filters)
    
    const response = await qualityReportsService.getNursePerformance(filters)
    
    console.log('Full API Response:', response)
    console.log('Response data:', response.data)
    console.log('Response pagination:', response.pagination)
    
    if (response.success && response.pagination) {
      nursePerformance.value = {
        data: response.data || [],
        total: response.pagination.total || 0,
        current_page: response.pagination.current_page || 1,
        last_page: response.pagination.last_page || 1,
        per_page: response.pagination.per_page || 15
      }
      
      console.log('Set nursePerformance.value to:', nursePerformance.value)
    }
  } catch (error) {
    console.error('Error loading nurse performance data:', error)
    toast.showError('Failed to load nurse performance data')
  }
}

// Pagination methods for nurse performance
const goToPerformancePage = (page) => {
  if (page >= 1 && page <= nursePerformance.value.last_page) {
    loadNursePerformance(page)
  }
}

const nextPerformancePage = () => {
  if (nursePerformance.value.current_page < nursePerformance.value.last_page) {
    goToPerformancePage(nursePerformance.value.current_page + 1)
  }
}

const prevPerformancePage = () => {
  if (nursePerformance.value.current_page > 1) {
    goToPerformancePage(nursePerformance.value.current_page - 1)
  }
}



const loadIncidentReports = async (page = 1) => {
  try {
    const filters = {
      page: page,
      per_page: 15,
      severity: incidentSeverityFilter.value !== 'all' ? incidentSeverityFilter.value : undefined,
      status: incidentStatusFilter.value !== 'all' ? incidentStatusFilter.value : undefined,
      incident_type: incidentCategoryFilter.value !== 'all' ? incidentCategoryFilter.value : undefined,
      search: incidentSearch.value || undefined
    }
    
    Object.keys(filters).forEach(key => filters[key] === undefined && delete filters[key])
    
    const response = await qualityReportsService.getIncidentReports(filters)
    incidentReports.value = response.data || { data: [], total: 0, current_page: 1, last_page: 1, per_page: 15 }
  } catch (error) {
    console.error('Error loading incident reports:', error)
  }
}

const loadQualityMetrics = async () => {
  try {
    const response = await qualityReportsService.getQualityMetrics()
    qualityMetrics.value = response.data || {}
  } catch (error) {
    console.error('Error loading quality metrics:', error)
  }
}

const refreshData = async () => {
  loading.value = true
  await Promise.all([
    loadOverviewData(),
    loadPatientFeedback(),
    loadNursePerformance(),
    loadIncidentReports(),
    loadQualityMetrics()
  ])
  loading.value = false
  toast.showSuccess('Data refreshed successfully!')
}

const exportCurrentReport = async () => {
  try {
    let reportType = ''
    switch (activeTab.value) {
      case 'feedback':
        reportType = 'patient-feedback'
        break
      case 'performance':
        reportType = 'nurse-performance'
        break
      case 'incidents':
        reportType = 'incident-reports'
        break
      case 'metrics':
        reportType = 'quality-metrics'
        break
    }

    toast.showInfo('Preparing export...')
    
    const { blob, filename } = await qualityReportsService.exportReport(reportType)
    
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = filename
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
    
    toast.showSuccess('Report exported successfully!')
  } catch (error) {
    console.error('Error exporting report:', error)
    toast.showError('Failed to export report')
  }
}

const toggleDropdown = (type, id) => {
  const dropdownId = `${type}-${id}`
  activeDropdown.value = activeDropdown.value === dropdownId ? null : dropdownId
}

const openCreateIncidentModal = () => {
  showCreateIncidentModal.value = true
}

const viewFeedback = (feedback) => {
  currentFeedback.value = feedback
  showViewFeedbackModal.value = true
  activeDropdown.value = null
}

const respondToFeedback = (feedback) => {
  currentFeedback.value = feedback
  responseText.value = ''
  showResponseModal.value = true
  activeDropdown.value = null
}

const closeViewFeedbackModal = () => {
  showViewFeedbackModal.value = false
  if (!showResponseModal.value) {
    currentFeedback.value = null
  }
}

const respondToFeedbackFromView = () => {
  showViewFeedbackModal.value = false
  responseText.value = ''
  showResponseModal.value = true
}

const viewNurseDetails = (nurse) => {
  currentNurse.value = nurse
  showNurseDetailsModal.value = true
  activeDropdown.value = null
}

const closeNurseDetailsModal = () => {
  showNurseDetailsModal.value = false
  currentNurse.value = null
}

const viewNurseFeedback = () => {
  // Switch to feedback tab and filter by current nurse
  activeTab.value = 'feedback'
  feedbackSearch.value = currentNurse.value.name
  closeNurseDetailsModal()
}

const viewIncident = (incident) => {
  currentIncident.value = incident
  showIncidentModal.value = true
  activeDropdown.value = null
}

const closeIncidentModal = () => {
  showIncidentModal.value = false
  currentIncident.value = null
}

const editIncident = async (incident) => {
  try {
    const response = await qualityReportsService.getIncidentReport(incident.id)
    
    if (response.success) {
      const incidentData = response.data
      
      // Open edit modal with incident data
      isEditingIncident.value = true
      currentIncident.value = incident
      
      // You would populate the incident form here
      // For now, just show a success message
      toast.showInfo('Edit functionality - implement as needed')
      
      showIncidentModal.value = false
      activeDropdown.value = null
    }
  } catch (error) {
    console.error('Error loading incident for edit:', error)
    toast.showError('Failed to load incident data for editing')
  }
}

const editIncidentFromView = () => {
  closeIncidentModal()
  editIncident(currentIncident.value)
}

const updateIncidentStatus = (incident) => {
  currentIncident.value = incident
  incidentUpdateForm.value = {
    status: incident.status,
    actions_taken: incident.actions_taken || '',
    follow_up_required: incident.follow_up_required || false,
    follow_up_date: incident.follow_up_date || ''
  }
  showUpdateIncidentModal.value = true
  activeDropdown.value = null
}

const closeUpdateIncidentModal = () => {
  showUpdateIncidentModal.value = false
  currentIncident.value = null
}

const incidentUpdateForm = ref({
  status: '',
  actions_taken: '',
  follow_up_required: false,
  follow_up_date: ''
})

const submitIncidentUpdate = async () => {
  isSaving.value = true
  
  try {
    await qualityReportsService.updateIncidentStatus(currentIncident.value.id, incidentUpdateForm.value)
    
    await loadIncidentReports()
    closeUpdateIncidentModal()
    toast.showSuccess('Incident status updated successfully!')
  } catch (error) {
    console.error('Error updating incident:', error)
    toast.showError('Failed to update incident status')
  }
  
  isSaving.value = false
}

const openDeleteIncidentModal = (incident) => {
  currentIncident.value = incident
  showDeleteIncidentModal.value = true
  activeDropdown.value = null
}

const closeDeleteIncidentModal = () => {
  showDeleteIncidentModal.value = false
  currentIncident.value = null
}

const deleteIncident = async () => {
  isSaving.value = true
  
  try {
    await qualityReportsService.deleteIncidentReport(currentIncident.value.id)
    
    await loadIncidentReports()
    await loadOverviewData() // Refresh overview stats
    closeDeleteIncidentModal()
    toast.showSuccess('Incident report deleted successfully!')
  } catch (error) {
    console.error('Error deleting incident:', error)
    toast.showError('Failed to delete incident report')
  }
  
  isSaving.value = false
}

const closeResponseModal = () => {
  showResponseModal.value = false
  responseText.value = ''
  currentFeedback.value = null
}

const submitResponse = async () => {
  isSaving.value = true
  
  try {
    await qualityReportsService.respondToFeedback(currentFeedback.value.id, {
      response_text: responseText.value
    })
    
    await loadPatientFeedback(patientFeedback.value.current_page) 
    closeResponseModal()
    toast.showSuccess('Response sent successfully!')
  } catch (error) {
    console.error('Error sending response:', error)
    toast.showError('Failed to send response')
  }
  
  isSaving.value = false
}

const formatDateTime = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleString()
}

// Utility methods
const getPatientAvatar = (patientName) => {
  return `https://ui-avatars.com/api/?name=${encodeURIComponent(patientName)}&color=667eea&background=f8f9fa&size=200&font-size=0.6`
}

const getFeedbackStatusBadgeColor = (status) => {
  const colorMap = {
    'pending': 'badge-warning',
    'responded': 'badge-success'
  }
  return colorMap[status] || 'badge-secondary'
}

const getIncidentCountBadgeColor = (count) => {
  if (count === 0) return 'badge-success'
  if (count <= 2) return 'badge-warning'
  return 'badge-danger'
}

const getGradeBadgeColor = (grade) => {
  const colorMap = {
    'A': 'badge-success',
    'B': 'badge-info',
    'C': 'badge-warning',
    'D': 'badge-warning',
    'F': 'badge-danger'
  }
  return colorMap[grade] || 'badge-secondary'
}

const getSeverityBadgeColor = (severity) => {
  const colorMap = {
    'critical': 'badge-danger',
    'high': 'badge-warning',
    'medium': 'badge-info',
    'low': 'badge-success'
  }
  return colorMap[severity] || 'badge-secondary'
}

const getIncidentStatusBadgeColor = (status) => {
  const colorMap = {
    'pending': 'badge-warning',
    'investigated': 'badge-info',
    'resolved': 'badge-success',
    'closed': 'badge-secondary'
  }
  return colorMap[status] || 'badge-secondary'
}

const getCategoryBadgeColor = (category) => {
  const colorMap = {
    'medication': 'badge-danger',
    'fall': 'badge-warning',
    'equipment': 'badge-info',
    'procedure': 'badge-info',
    'emergency': 'badge-danger',
    'other': 'badge-secondary'
  }
  return colorMap[category] || 'badge-secondary'
}

const getScoreColor = (score) => {
  if (score >= 90) return 'progress-green'
  if (score >= 70) return 'progress-blue'
  if (score >= 50) return 'progress-yellow'
  return 'progress-red'
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

const formatTime = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' })
}

// Pagination methods
const goToIncidentPage = (page) => {
  if (page >= 1 && page <= incidentReports.value.last_page) {
    loadIncidentReports(page)
  }
}

const nextIncidentPage = () => {
  if (incidentReports.value.current_page < incidentReports.value.last_page) {
    goToIncidentPage(incidentReports.value.current_page + 1)
  }
}

const prevIncidentPage = () => {
  if (incidentReports.value.current_page > 1) {
    goToIncidentPage(incidentReports.value.current_page - 1)
  }
}

const getIncidentPaginationPages = () => {
  const pages = []
  const maxVisible = 5
  let start = Math.max(1, incidentReports.value.current_page - Math.floor(maxVisible / 2))
  let end = Math.min(incidentReports.value.last_page, start + maxVisible - 1)
  
  if (end - start < maxVisible - 1) {
    start = Math.max(1, end - maxVisible + 1)
  }
  
  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  
  return pages
}


const getPerformancePaginationPages = () => {
  const pages = []
  const maxVisible = 5
  let start = Math.max(1, nursePerformance.value.current_page - Math.floor(maxVisible / 2))
  let end = Math.min(nursePerformance.value.last_page, start + maxVisible - 1)
  
  if (end - start < maxVisible - 1) {
    start = Math.max(1, end - maxVisible + 1)
  }
  
  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  
  return pages
}

// Close dropdown when clicking outside
const handleClickOutside = (event) => {
  if (!event.target.closest('.action-cell')) {
    activeDropdown.value = null
  }
}

// Watch for search and filter changes
let searchDebounceTimer = null

watch(feedbackSearch, () => {
  clearTimeout(searchDebounceTimer)
  searchDebounceTimer = setTimeout(() => {
    loadPatientFeedback(1)
  }, 500)
})

watch([feedbackRatingFilter, feedbackStatusFilter], () => {
  loadPatientFeedback(1)
})

watch(incidentSearch, () => {
  clearTimeout(searchDebounceTimer)
  searchDebounceTimer = setTimeout(() => {
    loadIncidentReports(1)
  }, 500)
})

watch([incidentSeverityFilter, incidentStatusFilter, incidentCategoryFilter], () => {
  loadIncidentReports(1)
})

watch(performanceSearch, () => {
  // Local filtering, no need to reload
})

watch(performanceGradeFilter, () => {
  // Local filtering, no need to reload
})

// Lifecycle
onMounted(async () => {
  try {
    await Promise.all([
      loadOverviewData(),
      loadPatientFeedback(),
      loadNursePerformance(),
      loadIncidentReports(),
      loadQualityMetrics()
    ])
    
    loading.value = false
    document.addEventListener('click', handleClickOutside)
  } catch (error) {
    console.error('Mount error:', error)
    loading.value = false
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

.quality-reporting-page {
  padding: 32px;
  background: #f8fafc;
  min-height: 100vh;
}

/* Page Header - Same as Schedules */
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

.btn-modern.btn-sm {
  padding: 8px 14px;
  font-size: 13px;
}

.btn-modern:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none !important;
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

.stat-icon.satisfaction {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.stat-icon.performance {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
}

.stat-icon.incidents {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.stat-icon.sessions {
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

.stat-change.negative {
  color: #ef4444;
}

/* Tabs */
.tabs-container {
  background: white;
  border-radius: 16px;
  margin-bottom: 24px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  border: 1px solid #f1f5f9;
  padding: 8px;
}

.tabs {
  display: flex;
  gap: 8px;
  overflow-x: auto;
}

.tab {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 20px;
  background: transparent;
  border: none;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 600;
  color: #64748b;
  cursor: pointer;
  transition: all 0.2s;
  white-space: nowrap;
}

.tab svg {
  width: 18px;
  height: 18px;
}

.tab:hover {
  background: #f8fafc;
  color: #334155;
}

.tab-active {
  background: #667eea !important;
  color: white !important;
}

/* Tab Content */
.tab-content {
  animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
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
.table-container {
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

/* Rating Display */
.rating-display {
  display: flex;
  align-items: center;
  gap: 8px;
}

.stars {
  display: flex;
  gap: 2px;
}

.star {
  font-size: 16px;
  color: #d1d5db;
}

.star.star-filled {
  color: #f59e0b;
}

.rating-number {
  font-size: 13px;
  color: #64748b;
  font-weight: 600;
}

/* Feedback Text */
.feedback-text {
  max-width: 300px;
  line-height: 1.4;
  color: #334155;
}

/* Incident Info */
.incident-info {
  max-width: 250px;
}

.incident-title {
  font-weight: 600;
  color: #0f172a;
  margin-bottom: 4px;
}

.incident-description {
  color: #64748b;
  font-size: 13px;
  line-height: 1.3;
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

.metric-value {
  font-weight: 600;
  color: #0f172a;
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
  margin: 0;
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

/* Metrics Grid */
.metrics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 24px;
}

.metric-card-large {
  background: white;
  border-radius: 16px;
  border: 1px solid #f1f5f9;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  overflow: hidden;
}

.metric-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  border-bottom: 1px solid #e2e8f0;
  background: #f8fafc;
}

.metric-header h3 {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
  letter-spacing: -0.3px;
}

.metric-icon {
  width: 20px;
  height: 20px;
  color: #64748b;
}

.metric-content {
  padding: 24px;
}

.primary-metric {
  text-align: center;
  margin-bottom: 24px;
}

.metric-value-large {
  display: block;
  font-size: 40px;
  font-weight: 800;
  color: #0f172a;
  line-height: 1;
  margin-bottom: 8px;
  letter-spacing: -1.5px;
}

.metric-label {
  color: #64748b;
  font-size: 14px;
  font-weight: 600;
}

.secondary-metrics {
  display: flex;
  justify-content: space-around;
  gap: 16px;
}

.secondary-metric {
  text-align: center;
}

.secondary-metric .value {
  display: block;
  font-size: 24px;
  font-weight: 700;
  color: #0f172a;
  margin-bottom: 4px;
  letter-spacing: -0.5px;
}

.secondary-metric .label {
  color: #64748b;
  font-size: 13px;
  font-weight: 600;
}

.care-metrics-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}

.care-metric {
  text-align: center;
  padding: 20px;
  background: #f8fafc;
  border-radius: 12px;
}

.care-metric-value {
  font-size: 28px;
  font-weight: 800;
  color: #0f172a;
  margin-bottom: 6px;
  letter-spacing: -0.8px;
}

.care-metric-label {
  color: #64748b;
  font-size: 12px;
  font-weight: 600;
}

.safety-overview {
  display: flex;
  justify-content: space-around;
  gap: 16px;
}

.safety-metric {
  text-align: center;
}

.safety-metric .value {
  display: block;
  font-size: 28px;
  font-weight: 800;
  color: #0f172a;
  margin-bottom: 6px;
  letter-spacing: -0.8px;
}

.safety-metric .value.critical {
  color: #dc2626;
}

.safety-metric .label {
  color: #64748b;
  font-size: 13px;
  font-weight: 600;
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
  border-bottom-color: #fecaca;
}

.modal-title {
  font-size: 20px;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
  letter-spacing: -0.4px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.modal-icon {
  width: 24px;
  height: 24px;
}

.modal-icon-danger {
  color: #dc2626;
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

/* Button with spinner */
.btn-modern .spinner,
.spinner {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
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

/* Form Groups */
.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: #334155;
  margin-bottom: 8px;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

/* Nurse Performance Modal Styles */
.nurse-performance-details {
  display: grid;
  grid-template-columns: 300px 1fr;
  gap: 32px;
}

.nurse-profile-section {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.nurse-profile-card {
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  border-radius: 16px;
  padding: 24px;
  text-align: center;
  border: 1px solid #e2e8f0;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
  width: 100%;
}

.profile-avatar-large {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  border: 4px solid white;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
  margin: 0 auto 16px;
  object-fit: cover;
}

.profile-name {
  font-size: 20px;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 12px 0;
}

.profile-badges {
  display: flex;
  justify-content: center;
  gap: 8px;
  margin-bottom: 20px;
  flex-wrap: wrap;
}

.profile-contact {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-top: 20px;
}

.contact-item {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-size: 13px;
  color: #334155;
  padding: 10px;
  background: rgba(255, 255, 255, 0.7);
  border-radius: 8px;
}

.contact-item svg {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
  color: #64748b;
}

.performance-details-section {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.score-card {
  background: white;
  border-radius: 16px;
  padding: 24px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.score-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.score-header h4 {
  font-size: 16px;
  font-weight: 600;
  color: #0f172a;
  margin: 0;
}

.score-display-large {
  display: flex;
  justify-content: center;
  align-items: center;
}

.score-circle {
  position: relative;
  width: 120px;
  height: 120px;
}

.score-circle-svg {
  width: 120px;
  height: 120px;
  transform: rotate(-90deg);
}

.score-circle-bg {
  fill: none;
  stroke: #e5e7eb;
  stroke-width: 2;
}

.score-circle-progress {
  fill: none;
  stroke: #3b82f6;
  stroke-width: 2;
  stroke-linecap: round;
  transition: stroke-dasharray 0.6s ease-in-out;
}

.score-text {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-size: 24px;
  font-weight: 700;
  color: #0f172a;
}

.section-header {
  display: flex;
  align-items: center;
  margin-bottom: 16px;
  font-size: 16px;
  font-weight: 600;
  color: #0f172a;
  padding-bottom: 12px;
  border-bottom: 1px solid #e2e8f0;
}

.section-header svg {
  width: 20px;
  height: 20px;
  margin-right: 10px;
  color: #64748b;
}

.metrics-section {
  background: #f8fafc;
  padding: 20px;
  border-radius: 12px;
}

.metrics-grid-detail {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
}

.metric-detail-card {
  background: white;
  border-radius: 12px;
  padding: 20px;
  border: 1px solid #e2e8f0;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  transition: all 0.2s ease;
}

.metric-detail-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.metric-detail-card .metric-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 12px;
}

.metric-detail-card .metric-icon svg {
  width: 24px;
  height: 24px;
  color: white;
}

.metric-icon.patient-care {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.metric-icon.hours {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
}

.metric-icon.rating {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.metric-icon.punctuality {
  background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
}

.metric-icon.care-plans {
  background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
}

.metric-icon.incidents {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.metric-info .metric-value {
  font-size: 28px;
  font-weight: 700;
  color: #0f172a;
  line-height: 1;
  margin-bottom: 6px;
}

.metric-info .metric-label {
  color: #64748b;
  font-size: 13px;
  font-weight: 600;
}

.feedback-section {
  background: white;
  border-radius: 16px;
  padding: 20px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.feedback-summary-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 24px;
  margin-top: 16px;
}

.feedback-stat {
  text-align: center;
  padding: 20px;
  background: #f8fafc;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
}

.feedback-stat-value {
  font-size: 32px;
  font-weight: 700;
  color: #0f172a;
  margin-bottom: 8px;
  line-height: 1;
}

.feedback-stat-label {
  color: #64748b;
  font-size: 13px;
  font-weight: 600;
  margin-top: 8px;
}

.performance-breakdown {
  background: white;
  border-radius: 16px;
  padding: 20px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.breakdown-items {
  display: flex;
  flex-direction: column;
  gap: 20px;
  margin-top: 16px;
}

.breakdown-item {
  display: flex;
  align-items: center;
  gap: 16px;
}

.breakdown-label {
  min-width: 140px;
  font-weight: 500;
  color: #334155;
  font-size: 14px;
}

.breakdown-bar {
  flex: 1;
  height: 8px;
  background: #e5e7eb;
  border-radius: 4px;
  overflow: hidden;
  position: relative;
}

.breakdown-fill {
  height: 100%;
  background: linear-gradient(90deg, #ef4444 0%, #f59e0b 30%, #10b981 70%, #059669 100%);
  border-radius: 4px;
  transition: width 0.6s ease-in-out;
}

.breakdown-score {
  min-width: 60px;
  font-weight: 600;
  color: #0f172a;
  font-size: 14px;
  text-align: right;
}

/* Incident Details Styles */
.incident-details {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.incident-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 20px;
}

.incident-header h4 {
  font-size: 20px;
  font-weight: 600;
  color: #0f172a;
  margin: 0;
}

.incident-badges {
  display: flex;
  gap: 8px;
}

.incident-sections {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.incident-section h5 {
  font-size: 14px;
  font-weight: 600;
  color: #0f172a;
  margin: 0 0 12px 0;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.incident-section p {
  margin: 0;
  color: #334155;
  line-height: 1.6;
}

.parties-grid,
.timeline-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 12px;
}

.party-detail,
.timeline-item {
  background: #f8fafc;
  padding: 12px;
  border-radius: 8px;
}

.party-detail label,
.timeline-item label {
  display: block;
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
  margin-bottom: 4px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.party-detail span,
.timeline-item span {
  color: #0f172a;
  font-size: 14px;
  font-weight: 500;
}

/* Summary Boxes */
.incident-summary-box,
.feedback-summary-box {
  background: #f8fafc;
  padding: 16px;
  border-radius: 12px;
  margin-bottom: 20px;
}

.incident-summary-box .summary-item,
.feedback-summary-box p {
  margin: 8px 0;
  font-size: 14px;
  color: #334155;
}

.incident-summary-box .summary-item:first-child,
.feedback-summary-box p:first-child {
  margin-top: 0;
}

.incident-summary-box .summary-item:last-child,
.feedback-summary-box p:last-child {
  margin-bottom: 0;
}


.feedback-details {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.feedback-detail-section h5 {
  font-size: 14px;
  font-weight: 600;
  color: #0f172a;
  margin: 0 0 12px 0;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  padding-bottom: 8px;
  border-bottom: 1px solid #e2e8f0;
}

.rating-display-large {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px;
  background: #f8fafc;
  border-radius: 12px;
}

.stars-large {
  display: flex;
  gap: 4px;
}

.star-large {
  font-size: 28px;
  color: #d1d5db;
}

.star-large.star-filled {
  color: #f59e0b;
}

.rating-number-large {
  font-size: 20px;
  color: #64748b;
  font-weight: 700;
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}

.detail-item {
  background: #f8fafc;
  padding: 12px;
  border-radius: 8px;
}

.detail-item label {
  display: block;
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
  margin-bottom: 4px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.detail-item span {
  color: #0f172a;
  font-size: 14px;
  font-weight: 500;
}

.text-success {
  color: #10b981 !important;
  font-weight: 600;
}

.text-muted {
  color: #94a3b8 !important;
}

.feedback-text-full {
  background: #f8fafc;
  padding: 16px;
  border-radius: 12px;
  color: #334155;
  line-height: 1.6;
  white-space: pre-wrap;
}

.response-box {
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  border-radius: 12px;
  padding: 16px;
}

.response-meta {
  font-size: 12px;
  color: #3b82f6;
  font-weight: 600;
  margin-bottom: 8px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.response-text {
  color: #1e40af;
  line-height: 1.6;
}

.response-container {
  background: white;
  border: 1px solid #e0e7ff;
  border-radius: 12px;
  overflow: hidden;
}

.response-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  background: #f0f9ff;
  border-bottom: 1px solid #e0e7ff;
}

.response-meta {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
  color: #3b82f6;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.response-meta svg {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
}

.response-content {
  padding: 16px;
}

.response-text {
  color: #334155;
  line-height: 1.6;
  margin: 0 0 12px 0;
  font-size: 14px;
}

.response-footer {
  padding-top: 12px;
  border-top: 1px solid #f1f5f9;
  font-size: 13px;
  color: #64748b;
}

.response-footer strong {
  color: #0f172a;
  font-weight: 600;
}

@media (max-width: 640px) {
  .detail-grid {
    grid-template-columns: 1fr;
  }
}

/* Responsive Design for Modals */
@media (max-width: 1024px) {
  .nurse-performance-details {
    grid-template-columns: 1fr;
  }
  
  .metrics-grid-detail {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .feedback-summary-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .modal {
    max-width: 95%;
    margin: 10px;
  }
  
  .modal-body {
    padding: 20px;
  }
  
  .metrics-grid-detail {
    grid-template-columns: 1fr;
  }
  
  .breakdown-item {
    flex-direction: column;
    align-items: stretch;
    gap: 8px;
  }
  
  .breakdown-label {
    min-width: auto;
    text-align: center;
  }
  
  .breakdown-score {
    text-align: center;
    min-width: auto;
  }
}

/* Responsive Design */
@media (max-width: 1024px) {
  .metrics-grid {
    grid-template-columns: 1fr;
  }
  
  .care-metrics-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .quality-reporting-page {
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
  
  .tabs {
    flex-wrap: wrap;
  }
  
  .secondary-metrics {
    flex-direction: column;
  }
}

@media (max-width: 640px) {
  .page-header-content h1 {
    font-size: 24px;
  }
  
  .stat-value {
    font-size: 24px;
  }
  
  .modern-table {
    font-size: 12px;
  }
  
  .modern-table th,
  .modern-table td {
    padding: 12px 16px;
  }
}
</style>