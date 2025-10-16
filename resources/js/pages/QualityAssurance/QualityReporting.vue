<template>
  <MainLayout>
    <div class="quality-reporting-page">
      <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Page Header -->
        <div class="page-header">
          <div class="page-header-content">
            <h1>Quality Assurance Reports</h1>
            <p>Monitor patient satisfaction, nurse performance, incidents, and quality metrics</p>
          </div>
          <div class="page-header-actions">
            <button @click="refreshData" class="btn btn-secondary" :disabled="loading">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              Refresh
            </button>
            <!-- <button @click="exportCurrentReport" class="btn btn-secondary">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              Export
            </button> -->
            <button @click="openCreateIncidentModal" class="btn btn-primary">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
              </svg>
              Add New Incident Report
            </button>
          </div>
        </div>

        <!-- Overview Cards -->
        <div v-if="!loading" class="overview-cards">
          <div class="overview-card">
            <div class="overview-icon satisfaction">
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293H15M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div class="overview-content">
              <div class="overview-value">{{ overview.avg_satisfaction }}/5</div>
              <div class="overview-label">Avg Satisfaction</div>
            </div>
          </div>
          
          <div class="overview-card">
            <div class="overview-icon performance">
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
            </div>
            <div class="overview-content">
              <div class="overview-value">{{ overview.active_nurses }}</div>
              <div class="overview-label">Active Nurses</div>
            </div>
          </div>
          
          <div class="overview-card">
            <div class="overview-icon incidents">
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z" />
              </svg>
            </div>
            <div class="overview-content">
              <div class="overview-value">{{ overview.total_incidents }}</div>
              <div class="overview-label">Total Incidents</div>
            </div>
          </div>
          
          <div class="overview-card">
            <div class="overview-icon sessions">
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div class="overview-content">
              <div class="overview-value">{{ overview.care_sessions }}</div>
              <div class="overview-label">Care Sessions</div>
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
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
              </svg>
              Patient Feedback ({{ patientFeedback.length }})
            </button>
            <button
              @click="activeTab = 'performance'"
              :class="['tab', { 'tab-active': activeTab === 'performance' }]"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
              Nurse Performance ({{ nursePerformance.length }})
            </button>
            <button
              @click="activeTab = 'incidents'"
              :class="['tab', { 'tab-active': activeTab === 'incidents' }]"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z" />
              </svg>
              Incident Reports ({{ incidentReports.length }})
            </button>
            <button
              @click="activeTab = 'metrics'"
              :class="['tab', { 'tab-active': activeTab === 'metrics' }]"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
              <div class="filters-content">
                <div class="search-wrapper">
                  <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
            </div>

            <!-- Feedback Table -->
            <div class="data-table-container">
              <table class="data-table">
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
                      <div class="user-info">
                        <div class="user-avatar">
                          <img :src="getPatientAvatar(feedback.patient_name)" :alt="feedback.patient_name" />
                        </div>
                        <div class="user-details">
                          <div class="user-name">{{ feedback.patient_name }}</div>
                        </div>
                      </div>
                    </td>
                    <td>
                      <div class="nurse-info">
                        <div class="nurse-name">{{ feedback.nurse_name }}</div>
                      </div>
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
                      <div class="date-info">
                        <div class="date">{{ formatDate(feedback.feedback_date) }}</div>
                        <div class="time">{{ formatTime(feedback.feedback_date) }}</div>
                      </div>
                    </td>
                    <td>
                      <span :class="'badge ' + getFeedbackStatusBadgeColor(feedback.status)">
                        {{ capitalizeFirst(feedback.status) }}
                      </span>
                    </td>
                    <td>
                      <div class="action-dropdown">
                        <button
                          @click="toggleDropdown('feedback', feedback.id)"
                          class="btn btn-secondary btn-sm"
                          style="min-width: auto; padding: 0.5rem;"
                        >
                          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                          </svg>
                        </button>
                        <div v-show="activeDropdown === `feedback-${feedback.id}`" class="dropdown-menu">
                          <button @click="viewFeedback(feedback)" class="dropdown-item">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            View Details
                          </button>
                          <button
                            v-if="feedback.status === 'pending'"
                            @click="respondToFeedback(feedback)"
                            class="dropdown-item dropdown-item-success"
                          >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
          </div>

          <!-- Nurse Performance Tab -->
          <div v-if="activeTab === 'performance'" class="tab-content">
            <!-- Performance Filters -->
            <div class="filters-section">
              <div class="filters-content">
                <div class="search-wrapper">
                  <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
            </div>

            <!-- Performance Table -->
            <div class="data-table-container">
              <table class="data-table">
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
                      <div class="user-info">
                        <div class="user-avatar">
                          <img :src="nurse.avatar_url" :alt="nurse.name" />
                        </div>
                        <div class="user-details">
                          <div class="user-name">{{ nurse.name }}</div>
                          <div class="user-id">{{ nurse.license_number }}</div>
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
                      <span :class="'badge ' + getIncidentCountBadgeColor(nurse.incident_count)">
                        {{ nurse.incident_count }}
                      </span>
                    </td>
                    <td>
                      <div class="score-display">
                        <div class="score-bar">
                          <div class="score-fill" :style="{ width: nurse.overall_score + '%' }"></div>
                        </div>
                        <span class="score-number">{{ nurse.overall_score }}%</span>
                      </div>
                    </td>
                    <td>
                      <span :class="'badge ' + getGradeBadgeColor(nurse.performance_grade)">
                        {{ nurse.performance_grade }}
                      </span>
                    </td>
                    <td>
                      <button @click="viewNurseDetails(nurse)" class="btn btn-secondary btn-sm">
                        View Details
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Incident Reports Tab -->
          <div v-if="activeTab === 'incidents'" class="tab-content">
            <!-- Incident Filters -->
            <div class="filters-section">
              <div class="filters-content">
                <div class="search-wrapper">
                  <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
            </div>

            <!-- Incidents Table -->
            <div class="data-table-container">
              <table class="data-table">
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
                      <div class="incident-parties">
                        <div class="party-info">
                          <span class="party-label">Patient:</span>
                          <span class="party-name">{{ incident.patient_name }}</span>
                        </div>
                        <div class="party-info">
                          <span class="party-label">Nurse:</span>
                          <span class="party-name">{{ incident.nurse_name }}</span>
                        </div>
                      </div>
                    </td>
                    <td>
                      <span :class="'badge ' + getCategoryBadgeColor(incident.category)">
                        {{ capitalizeFirst(incident.incident_type) }}
                      </span>
                    </td>
                    <td>
                      <span :class="'badge ' + getSeverityBadgeColor(incident.severity)">
                        {{ capitalizeFirst(incident.severity) }}
                      </span>
                    </td>
                      <td>
                        <div class="text-sm">
                          {{ formatDate(incident.incident_date) }}
                        </div>
                        <div class="text-xs text-gray-500">
                          {{ (incident.incident_time) }}
                        </div>
                      </td>
                    <td>
                      <span :class="'badge ' + getIncidentStatusBadgeColor(incident.status)">
                        {{ capitalizeFirst(incident.status) }}
                      </span>
                    </td>
                      <td>
                        <div class="action-dropdown">
                          <button
                            @click="toggleDropdown('incident', incident.id)"
                            class="btn btn-secondary btn-sm"
                            style="min-width: auto; padding: 0.5rem;"
                          >
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                              <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                            </svg>
                          </button>
                          <div v-show="activeDropdown === `incident-${incident.id}`" class="dropdown-menu">
                            <!-- View Details - Always available -->
                            <button @click="viewIncident(incident)" class="dropdown-item">
                              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                              </svg>
                              View Details
                            </button>
                            
                            <!-- Edit Incident - Only available if not closed -->
                            <button 
                              v-if="incident.status !== 'closed'"
                              @click="editIncident(incident)" 
                              class="dropdown-item"
                            >
                              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                              </svg>
                              Edit Incident
                            </button>
                            
                            <!-- Update Status - Only available if not closed -->
                            <button 
                              v-if="incident.status !== 'closed'"
                              @click="updateIncidentStatus(incident)" 
                              class="dropdown-item dropdown-item-warning"
                            >
                              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5" />
                              </svg>
                              Update Status
                            </button>
                            
                            <!-- Divider - Only show if there are edit/delete actions available -->
                            <div v-if="incident.status !== 'closed'" class="dropdown-divider"></div>
                            
                            <!-- Delete Incident - Only available if not closed -->
                            <button 
                              v-if="incident.status !== 'closed'"
                              @click="openDeleteIncidentModal(incident)" 
                              class="dropdown-item dropdown-item-danger"
                            >
                              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
            </div>
          </div>

          <!-- Quality Metrics Tab -->
          <div v-if="activeTab === 'metrics'" class="tab-content">
            <div class="metrics-grid">
              <!-- Patient Satisfaction Metrics -->
              <div class="metric-card">
                <div class="metric-header">
                  <h3>Patient Satisfaction</h3>
                  <svg class="metric-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293H15M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
                <div class="metric-content">
                  <div class="primary-metric">
                    <span class="metric-value">{{ qualityMetrics.patient_satisfaction?.average_rating || 0 }}/5</span>
                    <span class="metric-label">Average Rating</span>
                  </div>
                  <div class="secondary-metrics">
                    <div class="secondary-metric">
                      <span class="value">{{ qualityMetrics.patient_satisfaction?.total_responses || 0 }}</span>
                      <span class="label">Total Responses</span>
                    </div>
                    <div class="secondary-metric">
                      <span class="value">{{ qualityMetrics.patient_satisfaction?.response_rate || 0 }}%</span>
                      <span class="label">Response Rate</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Care Quality Metrics -->
              <div class="metric-card">
                <div class="metric-header">
                  <h3>Care Quality</h3>
                  <svg class="metric-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
              <div class="metric-card">
                <div class="metric-header">
                  <h3>Safety Metrics</h3>
                  <svg class="metric-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

        <!-- Nurse Performance Details Modal -->
        <div v-if="showNurseDetailsModal && currentNurse" class="modal-overlay">
          <div class="modal modal-xl">
            <div class="modal-header">
              <h2 class="modal-title">
                <svg class="modal-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                Nurse Performance Details
              </h2>
              <button @click="closeNurseDetailsModal" class="modal-close">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                      <span class="badge badge-success">Nurse</span>
                      <span :class="'badge ' + getGradeBadgeColor(currentNurse.performance_grade)">
                        Grade {{ currentNurse.performance_grade }}
                      </span>
                    </div>
                    <div class="profile-contact">
                      <div class="contact-item">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <span>{{ currentNurse.license_number }}</span>
                      </div>
                      <div class="contact-item" v-if="currentNurse.specialization">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <span>{{ currentNurse.specialization }}</span>
                      </div>
                      <div class="contact-item">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                        <span :class="'badge ' + getGradeBadgeColor(currentNurse.performance_grade)">
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
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                      </svg>
                      Performance Metrics
                    </h4>
                    <div class="metrics-grid-detail">
                      <div class="metric-detail-card">
                        <div class="metric-icon patient-care">
                          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
              <button @click="closeNurseDetailsModal" class="btn btn-secondary">
                Close
              </button>
              <button @click="viewNurseFeedback" class="btn btn-primary">
                View All Feedback
              </button>
            </div>
          </div>
        </div>

        <!-- Create/Edit New Incident Report Modal -->
        <div v-if="showCreateIncidentModal" class="modal-overlay">
          <div class="modal modal-xl">
            <div class="modal-header">
              <h3 class="modal-title">
                <svg class="modal-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
                {{ isEditingIncident ? 'Edit' : 'Create' }} Incident Report - Judy's Home Healthcare Agency
              </h3>
              <button @click="closeCreateIncidentModal" class="modal-close">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
            
            <form @submit.prevent="submitNewIncident">
              <div class="modal-body">
                <div class="form-grid">
                  <!-- Section 1: General Information -->
                  <div class="form-section-header">
                    <h4 class="form-section-title">Section 1: General Information</h4>
                  </div>
                  
                  <div class="form-group">
                    <label>Date of Report</label>
                    <input
                      type="date"
                      v-model="newIncidentForm.report_date"
                      required
                    />
                  </div>

                  <div class="form-group">
                    <label>Date of Incident</label>
                    <input
                      type="date"
                      v-model="newIncidentForm.incident_date"
                      required
                    />
                  </div>

                  <div class="form-group">
                    <label>Time of Incident</label>
                    <input
                      type="time"
                      v-model="newIncidentForm.incident_time"
                      required
                    />
                  </div>

                  <div class="form-group">
                    <label>Location of Incident</label>
                    <SearchableSelect
                      v-model="newIncidentForm.incident_location"
                      :options="incidentLocationOptions"
                      placeholder="Select location..."
                      required
                    />
                  </div>

                  <div v-if="newIncidentForm.incident_location === 'other'" class="form-group">
                    <label>Other Location Details</label>
                    <input
                      type="text"
                      v-model="newIncidentForm.location_other"
                      placeholder="Specify location"
                    />
                  </div>

                  <div class="form-group form-grid-full">
                    <label>Type of Incident</label>
                    <SearchableSelect
                      v-model="newIncidentForm.incident_type"
                      :options="incidentTypeOptions"
                      placeholder="Select incident type..."
                      required
                    />
                    <div v-if="newIncidentForm.incident_type === 'other'" class="mt-2">
                      <input
                        type="text"
                        v-model="newIncidentForm.incident_type_other"
                        placeholder="Specify other incident type"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                      />
                    </div>
                  </div>

                  <!-- Section 2: Person(s) Involved -->
                  <div class="form-section-header">
                    <h4 class="form-section-title">Section 2: Person(s) Involved</h4>
                  </div>

                  <div class="form-group">
                    <label>Patient Name (if applicable)</label>
                    <SearchableSelect
                      v-model="newIncidentForm.patient_id"
                      :options="patientOptions"
                      placeholder="Search and select patient..."
                      required
                    />
                  </div>

                  <div class="form-group">
                    <label>Age</label>
                    <input
                      type="number"
                      v-model="newIncidentForm.patient_age"
                      min="0"
                      max="120"
                      :readonly="!!selectedPatient"
                    />
                    <span v-if="selectedPatient" class="form-help">Auto-filled from patient data</span>
                  </div>

                  <div class="form-group">
                    <label>Sex</label>
                    <SearchableSelect
                      v-model="newIncidentForm.patient_sex"
                      :options="sexOptions"
                      placeholder="Select..."
                      :disabled="!!selectedPatient"
                    />
                    <span v-if="selectedPatient" class="form-help">Auto-filled from patient data</span>
                  </div>

                  <div class="form-group">
                    <label>Client ID/Case No.</label>
                    <input
                      type="text"
                      v-model="newIncidentForm.client_id_case_no"
                      placeholder="Client/Case number"
                    />
                  </div>

                  <div class="form-group">
                    <label>Staff/Family Involved (if any)</label>
                    <input
                      type="text"
                      v-model="newIncidentForm.staff_family_involved"
                      placeholder="Name of staff or family member involved"
                    />
                  </div>

                  <div class="form-group">
                    <label>Role</label>
                    <SearchableSelect
                      v-model="newIncidentForm.staff_family_role"
                      :options="staffFamilyRoleOptions"
                      placeholder="Select role..."
                    />
                    <div v-if="newIncidentForm.staff_family_role === 'other'" class="mt-2">
                      <input
                        type="text"
                        v-model="newIncidentForm.staff_family_role_other"
                        placeholder="Specify other role"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                      />
                    </div>
                  </div>

                  <!-- Section 3: Description of Incident -->
                  <div class="form-section-header">
                    <h4 class="form-section-title">Section 3: Description of Incident</h4>
                  </div>

                  <div class="form-group form-grid-full">
                    <label>Describe what happened (facts only, no opinions)</label>
                    <textarea
                      v-model="newIncidentForm.incident_description"
                      rows="4"
                      placeholder="Provide factual description of the incident..."
                      required
                    ></textarea>
                  </div>

                  <!-- Section 4: Immediate Actions Taken -->
                  <div class="form-section-header">
                    <h4 class="form-section-title">Section 4: Immediate Actions Taken</h4>
                  </div>

                  <div class="form-group form-grid-full">
                    <label>Did you provide First Aid/Medical Care?</label>
                    <div class="checkbox-group-inline">
                      <label class="checkbox-label">
                        <input
                          type="radio"
                          v-model="newIncidentForm.first_aid_provided"
                          :value="true"
                        />
                        <span class="radio-mark"></span>
                        <span class="checkbox-text">Yes</span>
                      </label>
                      <label class="checkbox-label">
                        <input
                          type="radio"
                          v-model="newIncidentForm.first_aid_provided"
                          :value="false"
                        />
                        <span class="radio-mark"></span>
                        <span class="checkbox-text">No</span>
                      </label>
                    </div>
                  </div>

                  <div v-if="newIncidentForm.first_aid_provided" class="form-group form-grid-full">
                    <label>If yes, describe</label>
                    <textarea
                      v-model="newIncidentForm.first_aid_description"
                      rows="3"
                      placeholder="Describe the first aid or medical care provided..."
                    ></textarea>
                  </div>

                  <div class="form-group">
                    <label>Who Provided Care</label>
                    <input
                      type="text"
                      v-model="newIncidentForm.care_provider_name"
                      placeholder="Name of person who provided care"
                    />
                  </div>

                  <div class="form-group form-grid-full">
                    <label>Was the client transferred to hospital?</label>
                    <div class="checkbox-group-inline">
                      <label class="checkbox-label">
                        <input
                          type="radio"
                          v-model="newIncidentForm.transferred_to_hospital"
                          :value="true"
                        />
                        <span class="radio-mark"></span>
                        <span class="checkbox-text">Yes</span>
                      </label>
                      <label class="checkbox-label">
                        <input
                          type="radio"
                          v-model="newIncidentForm.transferred_to_hospital"
                          :value="false"
                        />
                        <span class="radio-mark"></span>
                        <span class="checkbox-text">No</span>
                      </label>
                    </div>
                  </div>

                  <div v-if="newIncidentForm.transferred_to_hospital" class="form-group form-grid-full">
                    <label>If yes, where and mode of transportation?</label>
                    <textarea
                      v-model="newIncidentForm.hospital_transfer_details"
                      rows="3"
                      placeholder="Specify hospital name, address, and transportation method used..."
                    ></textarea>
                  </div>

                  <!-- Section 5: Witness Information -->
                  <div class="form-section-header">
                    <h4 class="form-section-title">Section 5: Witness Information</h4>
                  </div>

                  <div class="form-group">
                    <label>Name(s) of Witness(es)</label>
                    <input
                      type="text"
                      v-model="newIncidentForm.witness_names"
                      placeholder="Names of any witnesses present"
                    />
                  </div>

                  <div class="form-group">
                    <label>Contact(s)</label>
                    <input
                      type="text"
                      v-model="newIncidentForm.witness_contacts"
                      placeholder="Phone numbers or contact details of witnesses"
                    />
                  </div>

                  <!-- Section 6: Follow-Up Actions -->
                  <div class="form-section-header">
                    <h4 class="form-section-title">Section 6: Follow-Up Actions</h4>
                  </div>

                  <div class="form-group">
                    <label>Reported To (Supervisor/Manager)</label>
                    <SearchableSelect
                      v-model="newIncidentForm.reported_to_supervisor"
                      :options="supervisorOptions"
                      placeholder="Search and select supervisor..."
                    />
                  </div>

                  <div class="form-group form-grid-full">
                    <label>Corrective/Preventive Actions Planned</label>
                    <textarea
                      v-model="newIncidentForm.corrective_preventive_actions"
                      rows="3"
                      placeholder="Describe planned corrective or preventive actions..."
                    ></textarea>
                  </div>

                  <!-- Additional Tracking Information -->
                  <div class="form-section-header">
                    <h4 class="form-section-title">Additional Information</h4>
                  </div>

                  <div class="form-group">
                    <label>Severity Level</label>
                    <SearchableSelect
                      v-model="newIncidentForm.severity"
                      :options="severityOptions"
                      placeholder="Select severity level..."
                      required
                    />
                  </div>

                  <div class="form-group">
                    <label>Assign to</label>
                    <SearchableSelect
                      v-model="newIncidentForm.assigned_to"
                      :options="assigneeOptions"
                      placeholder="Auto-assign based on incident type"
                    />
                  </div>

                  <div class="form-group form-grid-full">
                    <label class="checkbox-label">
                      <input
                        type="checkbox"
                        v-model="newIncidentForm.follow_up_required"
                      />
                      <span class="checkmark"></span>
                      <span class="checkbox-text">This incident requires follow-up</span>
                    </label>
                  </div>

                  <div v-if="newIncidentForm.follow_up_required" class="form-group">
                    <label>Follow-up Date</label>
                    <input
                      type="date"
                      v-model="newIncidentForm.follow_up_date"
                      :min="new Date().toISOString().split('T')[0]"
                    />
                  </div>
                </div>

                <!-- Template Note -->
                <div class="template-note">
                  <p><strong>Note:</strong> This form is designed to capture detailed, factual information about any unexpected event, accident, error, or near-miss that occurs during the provision of home healthcare services. All incidents must be reported promptly and documented completely.</p>
                  <p class="confidential-notice">📌 <strong>Confidential -- For Internal Use Only</strong></p>
                </div>
              </div>

              <div class="modal-actions">
                <button type="button" @click="closeCreateIncidentModal" class="btn btn-secondary">
                  Cancel
                </button>
                <button
                  type="submit"
                  :disabled="isSaving"
                  class="btn btn-primary"
                >
                  <div v-if="isSaving" class="spinner spinner-sm"></div>
                  {{ isEditingIncident ? 'Update' : 'Submit' }} Incident Report
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Delete Incident Confirmation Modal -->
        <div v-if="showDeleteIncidentModal && currentIncident" class="modal-overlay">
          <div class="modal modal-sm">
            <div class="modal-header modal-header-danger">
              <h3 class="modal-title">
                <svg class="modal-icon modal-icon-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
                Delete Incident Report
              </h3>
              <button @click="closeDeleteIncidentModal" class="modal-close">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
            
            <div class="modal-body">
              <p>
                Are you sure you want to delete this incident report? This action cannot be undone.
              </p>
              <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                <div class="text-sm">
                  <strong>Incident:</strong> {{ currentIncident.title }}
                </div>
                <div class="text-sm">
                  <strong>Patient:</strong> {{ currentIncident.patient_name }}
                </div>
                <div class="text-sm">
                  <strong>Date:</strong> {{ formatDateTime(currentIncident.incident_date) }}
                </div>
              </div>
            </div>

            <div class="modal-actions">
              <button @click="closeDeleteIncidentModal" class="btn btn-secondary">
                Cancel
              </button>
              <button
                @click="deleteIncident"
                :disabled="isSaving"
                class="btn btn-danger"
              >
                <div v-if="isSaving" class="spinner spinner-sm"></div>
                Delete Incident
              </button>
            </div>
          </div>
        </div>

        <!-- Respond to Feedback Modal -->
        <div v-if="showResponseModal && currentFeedback" class="modal-overlay">
          <div class="modal modal-sm">
            <div class="modal-header">
              <h3 class="modal-title">
                <svg class="modal-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                </svg>
                Respond to Feedback
              </h3>
              <button @click="closeResponseModal" class="modal-close">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
            
            <form @submit.prevent="submitResponse">
              <div class="modal-body">
                <div class="feedback-summary">
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
                    class="w-full"
                  ></textarea>
                </div>
              </div>

              <div class="modal-actions">
                <button type="button" @click="closeResponseModal" class="btn btn-secondary">
                  Cancel
                </button>
                <button
                  type="submit"
                  :disabled="isSaving"
                  class="btn btn-primary"
                >
                  <div v-if="isSaving" class="spinner spinner-sm"></div>
                  Send Response
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- View Incident Modal -->
        <div v-if="showIncidentModal && currentIncident" class="modal-overlay">
          <div class="modal modal-lg">
            <div class="modal-header">
              <h3 class="modal-title">
                <svg class="modal-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
                Incident Report Details
              </h3>
              <button @click="closeIncidentModal" class="modal-close">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <div class="modal-body">
              <div class="incident-details">
                <div class="incident-header">
                  <h4>{{ currentIncident.title }}</h4>
                  <div class="incident-badges">
                    <span :class="'badge ' + getSeverityBadgeColor(currentIncident.severity)">
                      {{ capitalizeFirst(currentIncident.severity) }}
                    </span>
                    <span :class="'badge ' + getIncidentStatusBadgeColor(currentIncident.status)">
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
              <button @click="closeIncidentModal" class="btn btn-secondary">
                Close
              </button>
            </div>
          </div>
        </div>

        <!-- Update Incident Status Modal -->
        <div v-if="showUpdateIncidentModal && currentIncident" class="modal-overlay">
          <div class="modal modal-sm">
            <div class="modal-header">
              <h3 class="modal-title">Update Incident Status</h3>
              <button @click="closeUpdateIncidentModal" class="modal-close">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
            
            <form @submit.prevent="submitIncidentUpdate">
              <div class="modal-body">
                <div class="form-group">
                  <label>Status</label>
                  <select v-model="incidentUpdateForm.status" required>
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
                  ></textarea>
                </div>

                <!-- <div>
                  <label class="checkbox-label">
                    <input
                      type="checkbox"
                      v-model="incidentUpdateForm.follow_up_required"
                    />
                    <span class="checkmark"></span>
                    <span class="checkbox-text">Follow-up required</span>
                  </label>
                </div> -->

                <div v-if="incidentUpdateForm.follow_up_required" class="form-group">
                  <label>Follow-up Date</label>
                  <input
                    type="date"
                    v-model="incidentUpdateForm.follow_up_date"
                    :min="new Date().toISOString().split('T')[0]"
                  />
                </div>
              </div>

              <div class="modal-actions">
                <button type="button" @click="closeUpdateIncidentModal" class="btn btn-secondary">
                  Cancel
                </button>
                <button
                  type="submit"
                  :disabled="isSaving"
                  class="btn btn-primary"
                >
                  <div v-if="isSaving" class="spinner spinner-sm"></div>
                  Update Status
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
import MainLayout from '../../layout/MainLayout.vue'
import Toast from '../../common/components/Toast.vue'
import SearchableSelect from '../../common/components/SearchableSelect.vue'

const toast = inject('toast')

// Reactive data
const loading = ref(true)
const activeTab = ref('feedback')
const overview = ref({})
const qualityMetrics = ref({})

// Patient Feedback data
const patientFeedback = ref([])
const feedbackSearch = ref('')
const feedbackRatingFilter = ref('all')
const feedbackStatusFilter = ref('all')

// Nurse Performance data
const nursePerformance = ref([])
const performanceSearch = ref('')
const performanceTimeframe = ref('30')
const performanceGradeFilter = ref('all')

// Incident Reports data
const incidentReports = ref([])
const incidentSearch = ref('')
const incidentSeverityFilter = ref('all')
const incidentStatusFilter = ref('all')
const incidentCategoryFilter = ref('all')

// Users data for dropdown selections
const patients = ref([])
const nurses = ref([])
const admins = ref([])

// Options for SearchableSelect components
const incidentLocationOptions = [
  { value: 'home', label: 'Home' },
  { value: 'room', label: 'Room' },
  { value: 'washroom', label: 'Washroom' },
  { value: 'kitchen', label: 'Kitchen' },
  { value: 'other', label: 'Other' }
]

const incidentTypeOptions = [
  { value: 'fall', label: 'Fall' },
  { value: 'medication_error', label: 'Medication Error' },
  { value: 'equipment_failure', label: 'Equipment Failure' },
  { value: 'injury', label: 'Injury' },
  { value: 'other', label: 'Other' }
]

const severityOptions = [
  { value: 'low', label: 'Low' },
  { value: 'medium', label: 'Medium' },
  { value: 'high', label: 'High' },
  { value: 'critical', label: 'Critical' }
]

const sexOptions = [
  { value: 'M', label: 'Male' },
  { value: 'F', label: 'Female' }
]

const staffFamilyRoleOptions = [
  { value: 'nurse', label: 'Nurse' },
  { value: 'family', label: 'Family Member' },
  { value: 'other', label: 'Other' }
]

// Computed options for dynamic dropdowns
const patientOptions = computed(() => 
  patients.value.map(patient => ({
    value: patient.id,
    label: `${patient.first_name} ${patient.last_name}`,
    patient: patient // Include full patient object for auto-fill
  }))
)

const supervisorOptions = computed(() => 
  admins.value.map(admin => ({
    value: `${admin.first_name} ${admin.last_name}`,
    label: `${admin.first_name} ${admin.last_name} (${admin.role})`
  }))
)

const assigneeOptions = computed(() => 
  admins.value.map(admin => ({
    value: admin.id,
    label: `${admin.first_name} ${admin.last_name} (${admin.role})`
  }))
)

// Computed property for selected patient
const selectedPatient = computed(() => {
  if (!newIncidentForm.value.patient_id) return null
  return patients.value.find(p => p.id === newIncidentForm.value.patient_id)
})

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

// New incident form (Based on Official Template)
const newIncidentForm = ref({
  // Section 1: General Information
  report_date: new Date().toISOString().split('T')[0],
  incident_date: '',
  incident_time: '',
  incident_location: '',
  location_other: '',
  incident_type: '',
  incident_type_other: '',
  
  // Section 2: Person(s) Involved
  patient_id: '',
  patient_age: '',
  patient_sex: '',
  client_id_case_no: '',
  staff_family_involved: '',
  staff_family_role: '',
  staff_family_role_other: '',
  
  // Section 3: Description of Incident
  incident_description: '',
  
  // Section 4: Immediate Actions Taken
  first_aid_provided: false,
  first_aid_description: '',
  care_provider_name: '',
  transferred_to_hospital: false,
  hospital_transfer_details: '',
  
  // Section 5: Witness Information
  witness_names: '',
  witness_contacts: '',
  
  // Section 6: Follow-Up Actions
  reported_to_supervisor: '',
  corrective_preventive_actions: '',
  
  // Additional tracking
  severity: '',
  follow_up_required: false,
  follow_up_date: '',
  assigned_to: ''
})

// Update incident form
const incidentUpdateForm = ref({
  status: '',
  actions_taken: '',
  follow_up_required: false,
  follow_up_date: ''
})

// Watch for patient selection to auto-populate age and gender
watch(() => newIncidentForm.value.patient_id, (newPatientId) => {
  if (newPatientId && patients.value.length > 0) {
    const patient = patients.value.find(p => p.id === newPatientId)
    if (patient) {
      // Calculate age from date_of_birth
      if (patient.date_of_birth) {
        const today = new Date()
        const birthDate = new Date(patient.date_of_birth)
        let age = today.getFullYear() - birthDate.getFullYear()
        const monthDiff = today.getMonth() - birthDate.getMonth()
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
          age--
        }
        newIncidentForm.value.patient_age = age
      }
      
      // Set gender (convert to M/F format)
      if (patient.gender) {
        newIncidentForm.value.patient_sex = patient.gender.toUpperCase().substring(0, 1)
      }
    }
  } else {
    // Clear auto-filled fields when no patient selected
    newIncidentForm.value.patient_age = ''
    newIncidentForm.value.patient_sex = ''
  }
})

// Computed properties
const filteredFeedback = computed(() => {
  return patientFeedback.value.filter(feedback => {
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
  return nursePerformance.value.filter(nurse => {
    const matchesSearch = !performanceSearch.value || 
      nurse.name.toLowerCase().includes(performanceSearch.value.toLowerCase()) ||
      nurse.license_number.toLowerCase().includes(performanceSearch.value.toLowerCase())
    
    const matchesGrade = performanceGradeFilter.value === 'all' || 
      nurse.performance_grade === performanceGradeFilter.value
    
    return matchesSearch && matchesGrade
  })
})

const filteredIncidents = computed(() => {
  return incidentReports.value.filter(incident => {
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
const loadUsersForDropdowns = async () => {
  try {
    const [patientsResponse, nursesResponse, adminsResponse] = await Promise.all([
      fetch('/api/users?role=patient&verified=true', {
        headers: { 'Authorization': `Bearer ${localStorage.getItem('auth_token')}` }
      }),
      fetch('/api/users?role=nurse&verified=true', {
        headers: { 'Authorization': `Bearer ${localStorage.getItem('auth_token')}` }
      }),
      fetch('/api/users?role=admin,superadmin&verified=true', {
        headers: { 'Authorization': `Bearer ${localStorage.getItem('auth_token')}` }
      })
    ])
    
    if (patientsResponse.ok) {
      const patientsData = await patientsResponse.json()
      patients.value = patientsData.data || []
    }
    
    if (nursesResponse.ok) {
      const nursesData = await nursesResponse.json()
      nurses.value = nursesData.data || []
    }
    
    if (adminsResponse.ok) {
      const adminsData = await adminsResponse.json()
      admins.value = adminsData.data || []
    }
  } catch (error) {
    console.error('Error loading users for dropdowns:', error)
  }
}

const loadOverviewData = async () => {
  try {
    const response = await fetch('/api/quality-reports', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Content-Type': 'application/json'
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      overview.value = data.data.overview
    }
  } catch (error) {
    console.error('Error loading overview data:', error)
  }
}

const loadPatientFeedback = async () => {
  try {
    const params = new URLSearchParams()
    if (feedbackRatingFilter.value !== 'all') params.append('rating', feedbackRatingFilter.value)
    if (feedbackStatusFilter.value !== 'all') params.append('status', feedbackStatusFilter.value)
    if (feedbackSearch.value) params.append('search', feedbackSearch.value)

    const response = await fetch(`/api/quality-reports/patient-feedback?${params}`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Content-Type': 'application/json'
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      patientFeedback.value = data.data.data || []
    }
  } catch (error) {
    console.error('Error loading patient feedback:', error)
  }
}

const loadNursePerformance = async () => {
  try {
    const response = await fetch(`/api/quality-reports/nurse-performance?timeframe=${performanceTimeframe.value}`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Content-Type': 'application/json'
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      nursePerformance.value = data.data || []
    }
  } catch (error) {
    console.error('Error loading nurse performance:', error)
  }
}

const loadIncidentReports = async () => {
  try {
    const params = new URLSearchParams()
    if (incidentSeverityFilter.value !== 'all') params.append('severity', incidentSeverityFilter.value)
    if (incidentStatusFilter.value !== 'all') params.append('status', incidentStatusFilter.value)
    if (incidentCategoryFilter.value !== 'all') params.append('category', incidentCategoryFilter.value)
    if (incidentSearch.value) params.append('search', incidentSearch.value)

    const response = await fetch(`/api/quality-reports/incident-reports?${params}`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Content-Type': 'application/json'
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      incidentReports.value = data.data.data || []
    }
  } catch (error) {
    console.error('Error loading incident reports:', error)
  }
}

const loadQualityMetrics = async () => {
  try {
    const response = await fetch('/api/quality-reports/quality-metrics', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Content-Type': 'application/json'
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      qualityMetrics.value = data.data
    }
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

    const response = await fetch(`/api/quality-reports/export/${reportType}`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
      }
    })
    
    if (response.ok) {
      const blob = await response.blob()
      const url = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = `${reportType}_${new Date().toISOString().split('T')[0]}.csv`
      document.body.appendChild(link)
      link.click()
      link.remove()
      window.URL.revokeObjectURL(url)
      
      toast.showSuccess('Report exported successfully!')
    }
  } catch (error) {
    console.error('Error exporting report:', error)
    toast.showError('Failed to export report')
  }
}

const toggleDropdown = (type, id) => {
  const dropdownId = `${type}-${id}`
  activeDropdown.value = activeDropdown.value === dropdownId ? null : dropdownId
}

// New Incident Report Methods
const openCreateIncidentModal = async () => {
  // Load users for dropdowns if not already loaded
  if (patients.value.length === 0 || nurses.value.length === 0) {
    await loadUsersForDropdowns()
  }
  
  isEditingIncident.value = false
  currentIncident.value = null
  
  // Reset form to match official template structure
  newIncidentForm.value = {
    // Section 1: General Information
    report_date: new Date().toISOString().split('T')[0],
    incident_date: '',
    incident_time: '',
    incident_location: '',
    location_other: '',
    incident_type: '',
    incident_type_other: '',
    
    // Section 2: Person(s) Involved
    patient_id: '',
    patient_age: '',
    patient_sex: '',
    client_id_case_no: '',
    staff_family_involved: '',
    staff_family_role: '',
    staff_family_role_other: '',
    
    // Section 3: Description of Incident
    incident_description: '',
    
    // Section 4: Immediate Actions Taken
    first_aid_provided: false,
    first_aid_description: '',
    care_provider_name: '',
    transferred_to_hospital: false,
    hospital_transfer_details: '',
    
    // Section 5: Witness Information
    witness_names: '',
    witness_contacts: '',
    
    // Section 6: Follow-Up Actions
    reported_to_supervisor: '',
    corrective_preventive_actions: '',
    
    // Additional tracking
    severity: '',
    follow_up_required: false,
    follow_up_date: '',
    assigned_to: ''
  }
  
  showCreateIncidentModal.value = true
}


const editIncident = async (incident) => {
  // Load users for dropdowns if not already loaded
  if (patients.value.length === 0 || nurses.value.length === 0) {
    await loadUsersForDropdowns()
  }
  
  try {
    const response = await fetch(`/api/quality-reports/incident-reports/${incident.id}`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Content-Type': 'application/json'
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      console.log('Incident data received:', data) // Debug log
      
      const incidentData = data.data
      
      // Validate that we have the required data
      if (!incidentData) {
        throw new Error('No incident data received from server')
      }
      
      isEditingIncident.value = true
      currentIncident.value = incident
      
      // Helper function to safely format date
      const safeDate = (dateValue) => {
        if (!dateValue) return ''
        try {
          // Handle both date strings and Date objects
          const date = new Date(dateValue)
          if (isNaN(date.getTime())) return ''
          return date.toISOString().split('T')[0] // Format as YYYY-MM-DD
        } catch (e) {
          console.warn('Invalid date value:', dateValue)
          return ''
        }
      }
      
      // IMPROVED time handling function
      const safeTime = (timeValue) => {
        if (!timeValue) return ''
        
        console.log('Processing time value:', timeValue, 'Type:', typeof timeValue) // Debug log
        
        try {
          // If it's already in HH:MM format, return as is
          if (typeof timeValue === 'string' && /^\d{1,2}:\d{2}$/.test(timeValue)) {
            // Ensure it's in HH:MM format (pad single digit hours)
            const parts = timeValue.split(':')
            const hours = parts[0].padStart(2, '0')
            const minutes = parts[1]
            return `${hours}:${minutes}`
          }
          
          // If it's in HH:MM:SS format, remove seconds
          if (typeof timeValue === 'string' && /^\d{1,2}:\d{2}:\d{2}$/.test(timeValue)) {
            const timePart = timeValue.substring(0, 5)
            const parts = timePart.split(':')
            const hours = parts[0].padStart(2, '0')
            const minutes = parts[1]
            return `${hours}:${minutes}`
          }
          
          // Try to parse as a full datetime and extract time
          if (typeof timeValue === 'string' && timeValue.includes('T')) {
            const date = new Date(timeValue)
            if (!isNaN(date.getTime())) {
              return date.toTimeString().substring(0, 5) // HH:MM format
            }
          }
          
          // Try to parse as time with date prefix
          if (typeof timeValue === 'string') {
            // Handle cases like "1970-01-01 14:30:00" or "2024-01-01T14:30:00"
            const timeMatch = timeValue.match(/(\d{1,2}):(\d{2})(?::\d{2})?/)
            if (timeMatch) {
              const hours = timeMatch[1].padStart(2, '0')
              const minutes = timeMatch[2]
              return `${hours}:${minutes}`
            }
          }
          
          console.warn('Could not parse time value:', timeValue)
          return ''
          
        } catch (e) {
          console.warn('Error parsing time value:', timeValue, e)
          return ''
        }
      }
      
      // Helper function to safely get boolean value
      const safeBool = (value) => {
        if (value === null || value === undefined) return false
        if (typeof value === 'boolean') return value
        if (typeof value === 'string') {
          return value.toLowerCase() === 'true' || value === '1'
        }
        if (typeof value === 'number') return value === 1
        return false
      }
      
      // Helper function to safely get string value
      const safeString = (value) => {
        if (value === null || value === undefined) return ''
        return String(value)
      }
      
      // Populate form with existing data using safe helpers
      newIncidentForm.value = {
        // Section 1: General Information
        report_date: safeDate(incidentData.report_date),
        incident_date: safeDate(incidentData.incident_date),
        incident_time: safeTime(incidentData.incident_time), // This should now work properly
        incident_location: safeString(incidentData.incident_location),
        location_other: '',
        incident_type: safeString(incidentData.incident_type),
        incident_type_other: safeString(incidentData.incident_type_other),
        
        // Section 2: Person(s) Involved
        patient_id: incidentData.patient_id || '',
        patient_age: incidentData.patient_age || '',
        patient_sex: safeString(incidentData.patient_sex),
        client_id_case_no: safeString(incidentData.client_id_case_no),
        staff_family_involved: safeString(incidentData.staff_family_involved),
        staff_family_role: safeString(incidentData.staff_family_role),
        staff_family_role_other: safeString(incidentData.staff_family_role_other),
        
        // Section 3: Description
        incident_description: safeString(incidentData.incident_description),
        
        // Section 4: Immediate Actions
        first_aid_provided: safeBool(incidentData.first_aid_provided),
        first_aid_description: safeString(incidentData.first_aid_description),
        care_provider_name: safeString(incidentData.care_provider_name),
        transferred_to_hospital: safeBool(incidentData.transferred_to_hospital),
        hospital_transfer_details: safeString(incidentData.hospital_transfer_details),
        
        // Section 5: Witness Information
        witness_names: safeString(incidentData.witness_names),
        witness_contacts: safeString(incidentData.witness_contacts),
        
        // Section 6: Follow-Up Actions
        reported_to_supervisor: safeString(incidentData.reported_to_supervisor),
        corrective_preventive_actions: safeString(incidentData.corrective_preventive_actions),
        
        // Additional tracking
        severity: safeString(incidentData.severity),
        follow_up_required: safeBool(incidentData.follow_up_required),
        follow_up_date: safeDate(incidentData.follow_up_date),
        assigned_to: incidentData.assigned_to || ''
      }
      
      console.log('Form populated with:', newIncidentForm.value) // Debug log
      console.log('Incident time specifically:', newIncidentForm.value.incident_time) // Debug log for time
      
      showCreateIncidentModal.value = true
      activeDropdown.value = null
    } else {
      const errorData = await response.json().catch(() => ({ message: 'Unknown error' }))
      console.error('API Error:', errorData)
      toast.showError(errorData.message || 'Failed to load incident data for editing')
    }
  } catch (error) {
    console.error('Error loading incident for edit:', error)
    
    // Provide more specific error messages
    if (error.name === 'SyntaxError') {
      toast.showError('Invalid data format received from server. Please try again.')
    } else if (error.message.includes('fetch')) {
      toast.showError('Network error. Please check your connection and try again.')
    } else {
      toast.showError('An error occurred while loading the incident: ' + error.message)
    }
  }
}
const editIncidentFromView = () => {
  closeIncidentModal()
  editIncident(currentIncident.value)
}

const closeCreateIncidentModal = () => {
  showCreateIncidentModal.value = false
  isEditingIncident.value = false
  currentIncident.value = null
}

const submitNewIncident = async () => {
  isSaving.value = true
  
  try {
    // Prepare form data based on official template structure
    const formData = { ...newIncidentForm.value }
    
    // Handle location other
    if (formData.incident_location === 'other' && formData.location_other) {
      formData.incident_location = formData.location_other
    }
    
    // Remove helper fields
    delete formData.location_other
    
    const url = isEditingIncident.value 
      ? `/api/quality-reports/incident-reports/${currentIncident.value.id}`
      : '/api/quality-reports/add-incident-report'
    
    const method = isEditingIncident.value ? 'PUT' : 'POST'
    
    const response = await fetch(url, {
      method,
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(formData)
    })
    
    if (response.ok) {
      const result = await response.json()
      await loadIncidentReports()
      await loadOverviewData() // Refresh overview stats
      closeCreateIncidentModal()
      toast.showSuccess(
        isEditingIncident.value 
          ? 'Incident report updated successfully!' 
          : 'Incident report submitted successfully!'
      )
      
      // Switch to incidents tab if not already there
      if (activeTab.value !== 'incidents') {
        activeTab.value = 'incidents'
      }
    } else {
      const errorData = await response.json()
      toast.showError(errorData.message || `Failed to ${isEditingIncident.value ? 'update' : 'submit'} incident report`)
    }
  } catch (error) {
    console.error('Error submitting incident report:', error)
    toast.showError(`An error occurred while ${isEditingIncident.value ? 'updating' : 'submitting'} the incident report`)
  }
  
  isSaving.value = false
}

// Delete incident methods
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
    const response = await fetch(`/api/quality-reports/incident-reports/${currentIncident.value.id}`, {
      method: 'DELETE',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Content-Type': 'application/json'
      }
    })
    
    if (response.ok) {
      await loadIncidentReports()
      await loadOverviewData() // Refresh overview stats
      closeDeleteIncidentModal()
      toast.showSuccess('Incident report deleted successfully!')
    } else {
      const errorData = await response.json()
      toast.showError(errorData.message || 'Failed to delete incident report')
    }
  } catch (error) {
    console.error('Error deleting incident:', error)
    toast.showError('An error occurred while deleting the incident report')
  }
  
  isSaving.value = false
}

// Feedback actions
const viewFeedback = (feedback) => {
  currentFeedback.value = feedback
  activeDropdown.value = null
  // Could open a view modal here if needed
}

const respondToFeedback = (feedback) => {
  currentFeedback.value = feedback
  responseText.value = ''
  showResponseModal.value = true
  activeDropdown.value = null
}

const closeResponseModal = () => {
  showResponseModal.value = false
  currentFeedback.value = null
  responseText.value = ''
}

const submitResponse = async () => {
  isSaving.value = true
  
  try {
    const response = await fetch(`/api/quality-reports/feedback/${currentFeedback.value.id}/respond`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        response_text: responseText.value
      })
    })
    
    if (response.ok) {
      await loadPatientFeedback()
      closeResponseModal()
      toast.showSuccess('Response sent successfully!')
    } else {
      const errorData = await response.json()
      toast.showError(errorData.message || 'Failed to send response')
    }
  } catch (error) {
    console.error('Error sending response:', error)
    toast.showError('An error occurred while sending the response')
  }
  
  isSaving.value = false
}

// Nurse performance actions
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

// Incident actions
const viewIncident = (incident) => {
  currentIncident.value = incident
  showIncidentModal.value = true
  activeDropdown.value = null
}

const closeIncidentModal = () => {
  showIncidentModal.value = false
  currentIncident.value = null
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

const updateIncidentFromView = () => {
  closeIncidentModal()
  updateIncidentStatus(currentIncident.value)
}

const closeUpdateIncidentModal = () => {
  showUpdateIncidentModal.value = false
  currentIncident.value = null
}

const submitIncidentUpdate = async () => {
  isSaving.value = true
  
  try {
    const response = await fetch(`/api/quality-reports/incidents/${currentIncident.value.id}/update-status`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(incidentUpdateForm.value)
    })
    
    if (response.ok) {
      await loadIncidentReports()
      closeUpdateIncidentModal()
      toast.showSuccess('Incident status updated successfully!')
    } else {
      const errorData = await response.json()
      toast.showError(errorData.message || 'Failed to update incident status')
    }
  } catch (error) {
    console.error('Error updating incident:', error)
    toast.showError('An error occurred while updating the incident')
  }
  
  isSaving.value = false
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
    'procedure': 'badge-primary',
    'emergency': 'badge-danger',
    'other': 'badge-secondary'
  }
  return colorMap[category] || 'badge-secondary'
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

// Close dropdown when clicking outside
const handleClickOutside = (event) => {
  if (!event.target.closest('.action-dropdown')) {
    activeDropdown.value = null
  }
}

// Lifecycle
onMounted(async () => {
  await loadUsersForDropdowns()
  await refreshData()
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
.quality-reporting-page {
  min-height: 100vh;
  background: #f8f9fa;
}

/* Overview Cards */
.overview-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.overview-card {
  background: white;
  border-radius: 0.75rem;
  padding: 1.5rem;
  border: 1px solid #e5e7eb;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  display: flex;
  align-items: center;
  gap: 1rem;
}

.overview-icon {
  width: 3rem;
  height: 3rem;
  border-radius: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.overview-icon svg {
  width: 1.5rem;
  height: 1.5rem;
  color: white;
}

.overview-icon.satisfaction {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.overview-icon.performance {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
}

.overview-icon.incidents {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.overview-icon.sessions {
  background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
}

.overview-content {
  flex: 1;
}

.overview-value {
  font-size: 2rem;
  font-weight: 700;
  color: #1f2937;
  line-height: 1;
  margin-bottom: 0.25rem;
}

.overview-label {
  color: #6b7280;
  font-size: 0.875rem;
  font-weight: 500;
}

/* Tab Content */
.tab-content {
  animation: fadeIn 0.3s ease-out;
}

/* Data Tables */
.data-table-container {
  background: white;
  border-radius: 0.75rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  border: 1px solid #e5e7eb;
  overflow: visible;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table thead {
  background: #f9fafb;
}

.data-table th {
  padding: 0.75rem 1rem;
  text-align: left;
  font-size: 0.75rem;
  font-weight: 500;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  border-bottom: 1px solid #e5e7eb;
}

.data-table tbody tr:hover {
  background: #f9fafb;
}

.data-table td {
  padding: 1rem;
  font-size: 0.875rem;
  border-bottom: 1px solid #e5e7eb;
  vertical-align: top;
}

/* Feedback specific styles */
.rating-display {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.stars {
  display: flex;
  gap: 0.125rem;
}

.star {
  font-size: 1rem;
  color: #d1d5db;
}

.star.star-filled {
  color: #f59e0b;
}

.rating-number {
  font-size: 0.875rem;
  color: #6b7280;
  font-weight: 500;
}

.feedback-text {
  max-width: 300px;
  line-height: 1.4;
  color: #374151;
}

.feedback-summary {
  background: #f9fafb;
  padding: 1rem;
  border-radius: 0.5rem;
  margin-bottom: 1rem;
}

.feedback-summary p {
  margin: 0.25rem 0;
  font-size: 0.875rem;
}

/* Performance specific styles */
.metric-value {
  font-weight: 600;
  color: #1f2937;
}

.score-display {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.score-bar {
  width: 60px;
  height: 6px;
  background: #e5e7eb;
  border-radius: 3px;
  overflow: hidden;
}

.score-fill {
  height: 100%;
  background: linear-gradient(90deg, #ef4444 0%, #f59e0b 50%, #10b981 100%);
  transition: width 0.3s ease;
}

.score-number {
  font-size: 0.875rem;
  font-weight: 600;
  color: #374151;
}

/* Incident specific styles */
.incident-info {
  max-width: 250px;
}

.incident-title {
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 0.25rem;
}

.incident-description {
  color: #6b7280;
  font-size: 0.875rem;
  line-height: 1.3;
}

.incident-parties {
  font-size: 0.875rem;
}

.party-info {
  margin-bottom: 0.25rem;
}

.party-label {
  color: #6b7280;
  font-weight: 500;
}

.party-name {
  color: #374151;
  margin-left: 0.25rem;
}

.date-info {
  text-align: center;
}

.date-info .date {
  font-weight: 500;
  color: #1f2937;
}

.date-info .time {
  color: #6b7280;
  font-size: 0.875rem;
}

/* Incident details modal */
.incident-details {
  space-y: 1.5rem;
}


.incident-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1.5rem;
}

.incident-header h4 {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1f2937;
  margin: 0;
}

.incident-badges {
  display: flex;
  gap: 0.5rem;
}

.incident-sections {
  space-y: 1.5rem;
}

.incident-section h5 {
  font-size: 1rem;
  font-weight: 600;
  color: #1f2937;
  margin: 0 0 0.75rem 0;
}

.incident-section p {
  margin: 0;
  color: #374151;
  line-height: 1.5;
}

.parties-grid,
.timeline-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.party-detail,
.timeline-item {
  background: #f9fafb;
  padding: 0.75rem;
  border-radius: 0.5rem;
}

.party-detail label,
.timeline-item label {
  display: block;
  font-size: 0.875rem;
  font-weight: 500;
  color: #6b7280;
  margin-bottom: 0.25rem;
}

.party-detail span,
.timeline-item span {
  color: #1f2937;
  font-size: 0.875rem;
}

/* New incident form specific styles */
.incident-summary {
  background: #f9fafb;
  padding: 1rem;
  border-radius: 0.5rem;
  margin-bottom: 1rem;
}

.incident-summary p {
  margin: 0.25rem 0;
  font-size: 0.875rem;
}

/* Quality Metrics specific styles */
.metrics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
}

.metric-card {
  background: white;
  border-radius: 0.75rem;
  border: 1px solid #e5e7eb;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.metric-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
  background: #f9fafb;
}

.metric-header h3 {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1f2937;
  margin: 0;
  flex: 1;
}

.metric-icon {
  width: 1.25rem;
  height: 1.25rem;
  color: #6b7280;
}

.metric-content {
  padding: 1.5rem;
}

.primary-metric {
  text-align: center;
  margin-bottom: 1.5rem;
}

.metric-value {
  display: block;
  font-size: 2.5rem;
  font-weight: 700;
  color: #1f2937;
  line-height: 1;
  margin-bottom: 0.25rem;
}

.metric-label {
  color: #6b7280;
  font-size: 0.875rem;
  font-weight: 500;
}

.secondary-metrics {
  display: flex;
  justify-content: space-around;
  gap: 1rem;
}

.secondary-metric {
  text-align: center;
}

.secondary-metric .value {
  display: block;
  font-size: 1.25rem;
  font-weight: 600;
  color: #374151;
  margin-bottom: 0.25rem;
}

.secondary-metric .label {
  color: #6b7280;
  font-size: 0.75rem;
}

.care-metrics-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
}

.care-metric {
  text-align: center;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 0.5rem;
}

.care-metric-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1f2937;
  margin-bottom: 0.25rem;
}

.care-metric-label {
  color: #6b7280;
  font-size: 0.75rem;
  font-weight: 500;
}

.safety-overview {
  display: flex;
  justify-content: space-around;
  gap: 1rem;
}

.safety-metric {
  text-align: center;
}

.safety-metric .value {
  display: block;
  font-size: 1.5rem;
  font-weight: 700;
  color: #374151;
  margin-bottom: 0.25rem;
}

.safety-metric .value.critical {
  color: #dc2626;
}

.safety-metric .label {
  color: #6b7280;
  font-size: 0.75rem;
}

.efficiency-metrics {
  display: flex;
  justify-content: space-around;
  gap: 1rem;
}

.efficiency-metric {
  text-align: center;
}

.efficiency-metric .value {
  display: block;
  font-size: 1.25rem;
  font-weight: 600;
  color: #374151;
  margin-bottom: 0.25rem;
}

.efficiency-metric .label {
  color: #6b7280;
  font-size: 0.75rem;
}

/* Template-specific form styles */
.checkbox-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.checkbox-group-inline {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.radio-mark {
  width: 18px;
  height: 18px;
  background-color: #fff;
  border: 2px solid #d1d5db;
  border-radius: 50%;
  position: relative;
  transition: all 0.2s;
  flex-shrink: 0;
}

.checkbox-label:hover input[type="radio"]:not(:disabled) ~ .radio-mark {
  border-color: #3b82f6;
}

.checkbox-label input[type="radio"]:checked ~ .radio-mark {
  background-color: #3b82f6;
  border-color: #3b82f6;
}

.checkbox-label input[type="radio"]:checked ~ .radio-mark::after {
  content: '';
  position: absolute;
  left: 50%;
  top: 50%;
  transform: translate(-50%, -50%);
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: white;
}

.checkbox-label input[type="radio"]:disabled ~ .radio-mark {
  background-color: #f9fafb;
  border-color: #e5e7eb;
  cursor: not-allowed;
}

.inline-input {
  display: inline-block;
  width: auto;
  min-width: 150px;
  padding: 0.25rem 0.5rem;
  margin-left: 0.5rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  font-size: 0.875rem;
}

.template-note {
  margin-top: 2rem;
  padding: 1.5rem;
  background: #f8f9fa;
  border: 1px solid #e9ecef;
  border-radius: 0.5rem;
  border-left: 4px solid #667eea;
}

.template-note p {
  margin: 0 0 0.75rem 0;
  font-size: 0.875rem;
  color: #495057;
  line-height: 1.4;
}

.template-note p:last-child {
  margin-bottom: 0;
}

.confidential-notice {
  color: #dc2626 !important;
  font-weight: 600;
  text-align: center;
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #e9ecef;
}

/* Responsive Design */
@media (max-width: 768px) {
  .overview-cards {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .metrics-grid {
    grid-template-columns: 1fr;
  }
  
  .care-metrics-grid {
    grid-template-columns: 1fr;
  }
  
  .data-table {
    font-size: 0.8125rem;
  }
  
  .feedback-text,
  .incident-info {
    max-width: 200px;
  }
}

@media (max-width: 640px) {
  .overview-cards {
    grid-template-columns: 1fr;
  }
  
  .data-table-container {
    overflow-x: auto;
  }
  
  .data-table {
    min-width: 800px;
  }
}

/* ================================================
   NURSE PERFORMANCE MODAL STYLES
   ================================================ */

/* Main container for nurse performance details */
.nurse-performance-details {
  display: grid;
  grid-template-columns: 1fr 2fr;
  gap: 2rem;
  min-height: 500px;
}

/* Nurse Profile Section */
.nurse-profile-section {
  display: flex;
  flex-direction: column;
}

.nurse-profile-card {
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  border-radius: 1rem;
  padding: 2rem;
  text-align: center;
  border: 1px solid #e2e8f0;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
  height: fit-content;
}

.profile-avatar-large {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  border: 4px solid white;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
  margin: 0 auto 1.5rem;
  object-fit: cover;
}

.profile-name {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0 0 1rem 0;
}

.profile-badges {
  display: flex;
  justify-content: center;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
}

.profile-contact {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.contact-item {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  color: #6b7280;
  padding: 0.5rem;
  background: rgba(255, 255, 255, 0.7);
  border-radius: 0.5rem;
}

.contact-item svg {
  width: 1rem;
  height: 1rem;
  flex-shrink: 0;
}

/* Performance Details Section */
.performance-details-section {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

/* Performance Overview */
.performance-overview {
  margin-bottom: 1.5rem;
}

.score-card {
  background: white;
  border-radius: 1rem;
  padding: 2rem;
  border: 1px solid #e5e7eb;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.score-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.score-header h4 {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1f2937;
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
  font-size: 1.5rem;
  font-weight: 700;
  color: #1f2937;
}

/* Metrics Section */
.metrics-section {
  margin-bottom: 1.5rem;
}

.section-header {
  display: flex;
  align-items: center;
  margin-bottom: 1.5rem;
  font-size: 1.125rem;
  font-weight: 600;
  color: #1f2937;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid #e5e7eb;
}

.section-header svg {
  width: 1.25rem;
  height: 1.25rem;
  margin-right: 0.75rem;
  color: #6b7280;
}

.metrics-grid-detail {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
}

.metric-detail-card {
  background: #f9fafb;
  border-radius: 0.75rem;
  padding: 1.5rem;
  border: 1px solid #e5e7eb;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  transition: all 0.2s ease;
}

.metric-detail-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  background: white;
}

.metric-detail-card .metric-icon {
  width: 3rem;
  height: 3rem;
  border-radius: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1rem;
  flex-shrink: 0;
}

.metric-detail-card .metric-icon svg {
  width: 1.5rem;
  height: 1.5rem;
  color: white;
}

.metric-detail-card .metric-icon.patient-care {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.metric-detail-card .metric-icon.hours {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
}

.metric-detail-card .metric-icon.rating {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.metric-detail-card .metric-icon.punctuality {
  background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
}

.metric-detail-card .metric-icon.care-plans {
  background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
}

.metric-detail-card .metric-icon.incidents {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.metric-info {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.metric-info .metric-value {
  font-size: 1.875rem;
  font-weight: 700;
  color: #1f2937;
  line-height: 1;
  margin-bottom: 0.25rem;
}

.metric-info .metric-label {
  color: #6b7280;
  font-size: 0.875rem;
  font-weight: 500;
}

/* Feedback Section */
.feedback-section {
  background: white;
  border-radius: 1rem;
  padding: 1.5rem;
  border: 1px solid #e5e7eb;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.feedback-summary-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 2rem;
  margin-top: 1rem;
}

.feedback-stat {
  text-align: center;
  padding: 1.5rem;
  background: #f9fafb;
  border-radius: 0.75rem;
  border: 1px solid #e5e7eb;
}

.feedback-stat-value {
  font-size: 2rem;
  font-weight: 700;
  color: #1f2937;
  margin-bottom: 0.5rem;
  line-height: 1;
}

.feedback-stat-label {
  color: #6b7280;
  font-size: 0.875rem;
  font-weight: 500;
  margin-top: 0.5rem;
}

/* Performance Breakdown */
.performance-breakdown {
  background: white;
  border-radius: 1rem;
  padding: 1.5rem;
  border: 1px solid #e5e7eb;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.breakdown-items {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  margin-top: 1rem;
}

.breakdown-item {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.breakdown-label {
  min-width: 140px;
  font-weight: 500;
  color: #374151;
  font-size: 0.875rem;
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
  position: relative;
}

.breakdown-fill::after {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(90deg, transparent 0%, rgba(255, 255, 255, 0.3) 50%, transparent 100%);
  animation: shimmer 2s infinite;
}

@keyframes shimmer {
  0% { transform: translateX(-100%); }
  100% { transform: translateX(100%); }
}

.breakdown-score {
  min-width: 60px;
  font-weight: 600;
  color: #1f2937;
  font-size: 0.875rem;
  text-align: right;
}

/* Responsive Design for Nurse Performance Modal */
@media (max-width: 1024px) {
  .nurse-performance-details {
    grid-template-columns: 1fr;
    gap: 1.5rem;
  }
  
  .metrics-grid-detail {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .feedback-summary-grid {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
}

@media (max-width: 768px) {
  .nurse-profile-card {
    padding: 1.5rem;
  }
  
  .profile-avatar-large {
    width: 80px;
    height: 80px;
  }
  
  .profile-name {
    font-size: 1.25rem;
  }
  
  .metrics-grid-detail {
    grid-template-columns: 1fr;
  }
  
  .score-circle,
  .score-circle-svg {
    width: 100px;
    height: 100px;
  }
  
  .score-text {
    font-size: 1.25rem;
  }
  
  .breakdown-item {
    flex-direction: column;
    align-items: stretch;
    gap: 0.5rem;
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

@media (max-width: 640px) {
  .nurse-performance-details {
    gap: 1rem;
  }
  
  .score-card,
  .feedback-section,
  .performance-breakdown {
    padding: 1rem;
  }
  
  .metric-detail-card {
    padding: 1rem;
  }
  
  .profile-badges {
    flex-direction: column;
    gap: 0.25rem;
  }
}
</style>