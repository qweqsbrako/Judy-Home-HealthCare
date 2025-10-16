<template>
  <MainLayout>
    <div class="reports-page">
      <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Page Header -->
        <div class="page-header">
          <div class="page-header-content">
            <h1>Care Management & Nurse Performance</h1>
            <p>Comprehensive analytics for care plans, patient care, and nurse productivity</p>
          </div>
          <div class="page-header-actions">
            <button @click="exportAllReports" class="btn btn-secondary">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              Export All Reports
            </button>
            <button @click="refreshAllReports" class="btn btn-primary" :disabled="loading">
              <svg v-if="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              Refresh Data
            </button>
          </div>
        </div>

        <!-- Filters Section -->
        <div class="filters-section">
          <div class="filters-content">
            <div class="form-group">
              <label>Date Range</label>
              <div class="date-range">
                <input
                  type="date"
                  v-model="filters.dateFrom"
                  @change="applyFilters"
                  class="form-control"
                />
                <span>to</span>
                <input
                  type="date"
                  v-model="filters.dateTo"
                  @change="applyFilters"
                  class="form-control"
                />
              </div>
            </div>
            <div class="form-group">
              <label>Nurse Filter</label>
              <select v-model="filters.nurseId" @change="applyFilters" class="form-control">
                <option value="">All Nurses</option>
                <option v-for="nurse in availableNurses" :key="nurse.id" :value="nurse.id">
                  {{ nurse.first_name }} {{ nurse.last_name }}
                </option>
              </select>
            </div>
          </div>
        </div>

        <!-- Reports Tabs -->
        <div class="tabs-container">
          <div class="tabs">
            <button
              @click="activeTab = 'care'"
              :class="['tab', { 'tab-active': activeTab === 'care' }]"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
              </svg>
              Care Management
            </button>
            <button
              @click="activeTab = 'nurse'"
              :class="['tab', { 'tab-active': activeTab === 'nurse' }]"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
              Nurse Performance
            </button>
          </div>
        </div>

        <!-- Care Management Tab -->
        <div v-show="activeTab === 'care'" class="tab-content">
          <div class="reports-grid">
            
            <!-- 1. Care Plan Analytics -->
            <div class="report-card">
              <div class="report-header">
                <h3>Care Plan Analytics</h3>
                <button @click="exportReport('care_plan_analytics')" class="btn btn-sm btn-secondary">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3" />
                  </svg>
                </button>
              </div>

              <!-- Care Types Distribution -->
              <div class="chart-container">
                <h4>Care Plan Types Distribution</h4>
                <canvas ref="careTypesChart" width="400" height="300"></canvas>
              </div>

              <!-- Status Distribution -->
              <div class="chart-container">
                <h4>Care Plan Status Distribution</h4>
                <canvas ref="careStatusChart" width="400" height="200"></canvas>
              </div>

              <!-- Completion Rates -->
              <div class="table-container" v-if="carePlanAnalytics.completion_rates">
                <h4>Completion Rates by Care Type</h4>
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>Care Type</th>
                      <th>Total Plans</th>
                      <th>Completed</th>
                      <th>Completion Rate</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="rate in carePlanAnalytics.completion_rates" :key="rate.care_type">
                      <td>
                        <span class="care-type-badge">{{ formatCareType(rate.care_type) }}</span>
                      </td>
                      <td>{{ rate.total }}</td>
                      <td>{{ rate.completed }}</td>
                      <td>
                        <div class="progress-bar">
                          <div class="progress-fill" :style="{ width: rate.completion_rate + '%' }"></div>
                          <span class="progress-text">{{ rate.completion_rate }}%</span>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- 2. Patient Care Summary -->
            <div class="report-card">
              <div class="report-header">
                <h3>Patient Care Summary</h3>
                <button @click="exportReport('patient_care_summary')" class="btn btn-sm btn-secondary">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3" />
                  </svg>
                </button>
              </div>

              <!-- Care Complexity Chart -->
              <div class="chart-container">
                <h4>Care Complexity Distribution</h4>
                <canvas ref="complexityChart" width="400" height="250"></canvas>
              </div>

              <!-- Active Plans per Patient -->
              <div class="table-container" v-if="patientCareSummary.active_plans_per_patient">
                <h4>Patients with Multiple Active Care Plans</h4>
                <div class="table-scroll">
                  <table class="data-table">
                    <thead>
                      <tr>
                        <th>Patient</th>
                        <th>Active Plans</th>
                        <th>Contact</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="patient in patientCareSummary.active_plans_per_patient.filter(p => p.active_plans > 1)" :key="patient.patient_id">
                        <td>
                          <div class="patient-info">
                            <strong>{{ patient.patient.first_name }} {{ patient.patient.last_name }}</strong>
                          </div>
                        </td>
                        <td>
                          <span class="badge badge-warning">{{ patient.active_plans }} plans</span>
                        </td>
                        <td>{{ patient.patient.email }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- High Priority Patients -->
              <div class="table-container" v-if="patientCareSummary.high_priority_patients">
                <h4>Critical Priority Patients ({{ patientCareSummary.high_priority_patients.length }})</h4>
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>Patient</th>
                      <th>Contact</th>
                      <th>Phone</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="patient in patientCareSummary.high_priority_patients" :key="patient.patient_id">
                      <td>
                        <div class="patient-info">
                          <strong>{{ patient.patient.first_name }} {{ patient.patient.last_name }}</strong>
                          <span class="badge badge-danger">Critical</span>
                        </div>
                      </td>
                      <td>{{ patient.patient.email }}</td>
                      <td>{{ patient.patient.phone }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- 3. Care Plan Performance -->
            <div class="report-card">
              <div class="report-header">
                <h3>Care Plan Performance</h3>
                <button @click="exportReport('care_plan_performance')" class="btn btn-sm btn-secondary">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3" />
                  </svg>
                </button>
              </div>

              <!-- Average Duration Chart -->
              <div class="chart-container">
                <h4>Average Care Plan Duration by Type (Days)</h4>
                <canvas ref="durationChart" width="400" height="250"></canvas>
              </div>

              <!-- Doctor Performance -->
              <div class="table-container" v-if="carePlanPerformance.doctor_performance">
                <h4>Doctor Performance (5+ Care Plans)</h4>
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>Doctor</th>
                      <th>Total Plans</th>
                      <th>Completed</th>
                      <th>Avg Completion %</th>
                      <th>Success Rate</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="doctor in carePlanPerformance.doctor_performance" :key="doctor.doctor_id">
                      <td>
                        <div class="doctor-info">
                          <strong>Dr. {{ doctor.doctor.first_name }} {{ doctor.doctor.last_name }}</strong>
                        </div>
                      </td>
                      <td>{{ doctor.total_plans }}</td>
                      <td>{{ doctor.completed_plans }}</td>
                      <td>{{ Math.round(doctor.avg_completion_rate) }}%</td>
                      <td>
                        <div class="progress-bar">
                          <div class="progress-fill" :style="{ width: ((doctor.completed_plans / doctor.total_plans) * 100) + '%' }"></div>
                          <span class="progress-text">{{ Math.round((doctor.completed_plans / doctor.total_plans) * 100) }}%</span>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

          </div>
        </div>

        <!-- Nurse Performance Tab -->
        <div v-show="activeTab === 'nurse'" class="tab-content">
          <div class="reports-grid">
            
            <!-- 1. Nurse Productivity Report -->
            <div class="report-card">
              <div class="report-header">
                <h3>Nurse Productivity Report</h3>
                <button @click="exportReport('nurse_productivity')" class="btn btn-sm btn-secondary">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3" />
                  </svg>
                </button>
              </div>

              <!-- Productivity Summary -->
              <div class="table-container" v-if="nurseProductivity.hours_worked">
                <h4>Nurse Work Hours Summary</h4>
                <div class="table-scroll">
                  <table class="data-table">
                    <thead>
                      <tr>
                        <th>Nurse</th>
                        <th>Total Hours</th>
                        <th>Sessions</th>
                        <th>Avg Session</th>
                        <th>Patient Visits</th>
                        <th>Unique Patients</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="nurse in mergedNurseData" :key="nurse.nurse_id">
                        <td>
                          <div class="nurse-info">
                            <strong>{{ nurse.nurse.first_name }} {{ nurse.nurse.last_name }}</strong>
                          </div>
                        </td>
                        <td>{{ Math.round(nurse.total_hours) }}h</td>
                        <td>{{ nurse.total_sessions || 0 }}</td>
                        <td>{{ nurse.avg_session_hours ? Math.round(nurse.avg_session_hours * 10) / 10 : 0 }}h</td>
                        <td>{{ nurse.total_visits || 0 }}</td>
                        <td>{{ nurse.unique_patients || 0 }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- Hours Worked Chart -->
              <div class="chart-container">
                <h4>Total Hours Worked by Nurse</h4>
                <canvas ref="nurseHoursChart" width="400" height="300"></canvas>
              </div>
            </div>

            <!-- 2. Schedule Compliance Report -->
            <div class="report-card">
              <div class="report-header">
                <h3>Schedule Compliance Report</h3>
                <button @click="exportReport('schedule_compliance')" class="btn btn-sm btn-secondary">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3" />
                  </svg>
                </button>
              </div>

              <!-- Overall Compliance Stats -->
              <div class="stats-grid" v-if="scheduleCompliance.confirmations">
                <div class="stat-card">
                  <div class="stat-value">{{ scheduleCompliance.confirmations.total_scheduled || 0 }}</div>
                  <div class="stat-label">Total Scheduled</div>
                </div>
                <div class="stat-card success">
                  <div class="stat-value">{{ scheduleCompliance.confirmations.confirmed || 0 }}</div>
                  <div class="stat-label">Confirmed</div>
                </div>
                <div class="stat-card info">
                  <div class="stat-value">{{ scheduleCompliance.confirmations.confirmation_rate || 0 }}%</div>
                  <div class="stat-label">Confirmation Rate</div>
                </div>
              </div>

              <!-- On-Time Performance -->
              <div class="table-container" v-if="scheduleCompliance.on_time_rates">
                <h4>On-Time Performance by Nurse</h4>
                <div class="table-scroll">
                  <table class="data-table">
                    <thead>
                      <tr>
                        <th>Nurse</th>
                        <th>Total Shifts</th>
                        <th>On-Time</th>
                        <th>On-Time Rate</th>
                        <th>No-Shows</th>
                        <th>Cancellations</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="nurse in mergedComplianceData" :key="nurse.nurse_id">
                        <td>
                          <div class="nurse-info">
                            <strong>{{ nurse.nurse.first_name }} {{ nurse.nurse.last_name }}</strong>
                          </div>
                        </td>
                        <td>{{ nurse.total_shifts || 0 }}</td>
                        <td>{{ nurse.on_time_shifts || 0 }}</td>
                        <td>
                          <div class="progress-bar">
                            <div class="progress-fill" :style="{ width: (nurse.on_time_rate || 0) + '%' }"></div>
                            <span class="progress-text">{{ nurse.on_time_rate || 0 }}%</span>
                          </div>
                        </td>
                        <td>{{ nurse.no_show_shifts || 0 }}</td>
                        <td>{{ nurse.cancelled_shifts || 0 }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <!-- 3. Time Tracking Analytics -->
            <div class="report-card">
              <div class="report-header">
                <h3>Time Tracking Analytics</h3>
                <button @click="exportReport('time_tracking_analytics')" class="btn btn-sm btn-secondary">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3" />
                  </svg>
                </button>
              </div>

              <!-- Session Types Chart -->
              <div class="chart-container">
                <h4>Session Types Distribution</h4>
                <canvas ref="sessionTypesChart" width="400" height="250"></canvas>
              </div>

              <!-- Overtime Analysis -->
              <div class="table-container" v-if="timeTrackingAnalytics.total_hours">
                <h4>Work Hours & Overtime Analysis</h4>
                <div class="table-scroll">
                  <table class="data-table">
                    <thead>
                      <tr>
                        <th>Nurse</th>
                        <th>Total Hours</th>
                        <th>Overtime Hours</th>
                        <th>Avg Session</th>
                        <th>Avg Breaks</th>
                        <th>Break Duration</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="nurse in mergedTimeData" :key="nurse.nurse_id">
                        <td>
                          <div class="nurse-info">
                            <strong>{{ nurse.nurse.first_name }} {{ nurse.nurse.last_name }}</strong>
                          </div>
                        </td>
                        <td>{{ Math.round(nurse.total_hours) }}h</td>
                        <td>
                          <span :class="nurse.overtime_hours > 0 ? 'badge badge-warning' : 'text-muted'">
                            {{ Math.round(nurse.overtime_hours) }}h
                          </span>
                        </td>
                        <td>{{ nurse.avg_session_hours ? Math.round(nurse.avg_session_hours * 10) / 10 : 0 }}h</td>
                        <td>{{ nurse.avg_breaks_per_shift ? Math.round(nurse.avg_breaks_per_shift * 10) / 10 : 0 }}</td>
                        <td>{{ nurse.avg_break_duration ? Math.round(nurse.avg_break_duration) : 0 }}min</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>
    
    <!-- Toast Component -->
    <Toast />
  </MainLayout>
</template>

<script setup>
import { ref, computed, onMounted, nextTick, inject, watch } from 'vue'
import MainLayout from '../../layout/MainLayout.vue'
import Toast from '../../common/components/Toast.vue'
import Chart from 'chart.js/auto'
import {
  getAvailableNurses,
  getCarePlanAnalytics,
  getPatientCareSummary,
  getCarePlanPerformance,
  getNurseProductivity,
  getScheduleCompliance,
  getTimeTrackingAnalytics,
  exportCareNurseReport,
  exportAllCareReports,
  exportAllNurseReports
} from '../../services/careNurseReportsService'

const toast = inject('toast')

// Reactive data
const loading = ref(false)
const activeTab = ref('care')
const availableNurses = ref([])

// Report data
const carePlanAnalytics = ref({})
const patientCareSummary = ref({})
const carePlanPerformance = ref({})
const nurseProductivity = ref({})
const scheduleCompliance = ref({})
const timeTrackingAnalytics = ref({})

// Filters
const filters = ref({
  dateFrom: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
  dateTo: new Date().toISOString().split('T')[0],
  nurseId: ''
})

// Chart references
const careTypesChart = ref(null)
const careStatusChart = ref(null)
const complexityChart = ref(null)
const durationChart = ref(null)
const nurseHoursChart = ref(null)
const sessionTypesChart = ref(null)

// Chart instances
let careTypesChartInstance = null
let careStatusChartInstance = null
let complexityChartInstance = null
let durationChartInstance = null
let nurseHoursChartInstance = null
let sessionTypesChartInstance = null

// Computed properties
const mergedNurseData = computed(() => {
  const hoursWorked = nurseProductivity.value.hours_worked || []
  const patientVisits = nurseProductivity.value.patient_visits || []
  
  return hoursWorked.map(nurse => {
    const visits = patientVisits.find(v => v.nurse_id === nurse.nurse_id)
    return {
      ...nurse,
      total_visits: visits?.total_visits || 0,
      unique_patients: visits?.unique_patients || 0
    }
  })
})

const mergedComplianceData = computed(() => {
  const onTimeRates = scheduleCompliance.value.on_time_rates || []
  const noShows = scheduleCompliance.value.no_shows || []
  
  return onTimeRates.map(nurse => {
    const noShow = noShows.find(n => n.nurse_id === nurse.nurse_id)
    return {
      ...nurse,
      no_show_shifts: noShow?.no_show_shifts || 0,
      cancelled_shifts: noShow?.cancelled_shifts || 0
    }
  })
})

const mergedTimeData = computed(() => {
  const totalHours = timeTrackingAnalytics.value.total_hours || []
  const breakPatterns = timeTrackingAnalytics.value.break_patterns || []
  
  return totalHours.map(nurse => {
    const breaks = breakPatterns.find(b => b.nurse_id === nurse.nurse_id)
    return {
      ...nurse,
      avg_breaks_per_shift: breaks?.avg_breaks_per_shift || 0,
      avg_break_duration: breaks?.avg_break_duration || 0
    }
  })
})

// Watch for tab changes and reload data
watch(activeTab, async (newTab, oldTab) => {
  if (newTab !== oldTab) {
    await refreshAllReports()
  }
})

// Methods
const loadAvailableNurses = async () => {
  try {
    const response = await getAvailableNurses()
    availableNurses.value = response.data || response
  } catch (error) {
    console.error('Error loading nurses:', error)
    toast.showError('Failed to load nurses')
  }
}

const loadCarePlanAnalytics = async () => {
  try {
    const filterParams = {
      date_from: filters.value.dateFrom,
      date_to: filters.value.dateTo
    }
    
    carePlanAnalytics.value = await getCarePlanAnalytics(filterParams)
    await nextTick()
    renderCareTypesChart()
    renderCareStatusChart()
  } catch (error) {
    console.error('Error loading care plan analytics:', error)
    toast.showError('Failed to load care plan analytics')
  }
}

const loadPatientCareSummary = async () => {
  try {
    patientCareSummary.value = await getPatientCareSummary()
    await nextTick()
    renderComplexityChart()
  } catch (error) {
    console.error('Error loading patient care summary:', error)
    toast.showError('Failed to load patient care summary')
  }
}

const loadCarePlanPerformance = async () => {
  try {
    const filterParams = {
      date_from: filters.value.dateFrom,
      date_to: filters.value.dateTo
    }
    
    carePlanPerformance.value = await getCarePlanPerformance(filterParams)
    await nextTick()
    renderDurationChart()
  } catch (error) {
    console.error('Error loading care plan performance:', error)
    toast.showError('Failed to load care plan performance')
  }
}

const loadNurseProductivity = async () => {
  try {
    const filterParams = {
      date_from: filters.value.dateFrom,
      date_to: filters.value.dateTo
    }
    
    if (filters.value.nurseId) {
      filterParams.nurse_id = filters.value.nurseId
    }
    
    nurseProductivity.value = await getNurseProductivity(filterParams)
    await nextTick()
    renderNurseHoursChart()
  } catch (error) {
    console.error('Error loading nurse productivity:', error)
    toast.showError('Failed to load nurse productivity')
  }
}

const loadScheduleCompliance = async () => {
  try {
    const filterParams = {
      date_from: filters.value.dateFrom,
      date_to: filters.value.dateTo
    }
    
    if (filters.value.nurseId) {
      filterParams.nurse_id = filters.value.nurseId
    }
    
    scheduleCompliance.value = await getScheduleCompliance(filterParams)
  } catch (error) {
    console.error('Error loading schedule compliance:', error)
    toast.showError('Failed to load schedule compliance')
  }
}

const loadTimeTrackingAnalytics = async () => {
  try {
    const filterParams = {
      date_from: filters.value.dateFrom,
      date_to: filters.value.dateTo
    }
    
    if (filters.value.nurseId) {
      filterParams.nurse_id = filters.value.nurseId
    }
    
    timeTrackingAnalytics.value = await getTimeTrackingAnalytics(filterParams)
    await nextTick()
    renderSessionTypesChart()
  } catch (error) {
    console.error('Error loading time tracking analytics:', error)
    toast.showError('Failed to load time tracking analytics')
  }
}

// Chart rendering methods
const renderCareTypesChart = () => {
  if (!careTypesChart.value || !carePlanAnalytics.value.care_type_distribution) return
  
  if (careTypesChartInstance) {
    careTypesChartInstance.destroy()
  }
  
  const ctx = careTypesChart.value.getContext('2d')
  const data = carePlanAnalytics.value.care_type_distribution
  
  careTypesChartInstance = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: data.map(item => formatCareType(item.care_type)),
      datasets: [{
        data: data.map(item => item.count),
        backgroundColor: [
          '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
          '#06b6d4', '#84cc16', '#f97316', '#ec4899', '#6b7280'
        ]
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom'
        }
      }
    }
  })
}

const renderCareStatusChart = () => {
  if (!careStatusChart.value || !carePlanAnalytics.value.status_distribution) return
  
  if (careStatusChartInstance) {
    careStatusChartInstance.destroy()
  }
  
  const ctx = careStatusChart.value.getContext('2d')
  const data = carePlanAnalytics.value.status_distribution
  
  careStatusChartInstance = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: data.map(item => capitalizeFirst(item.status)),
      datasets: [{
        label: 'Care Plans',
        data: data.map(item => item.count),
        backgroundColor: '#3b82f6'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            stepSize: 1
          }
        }
      }
    }
  })
}

const renderComplexityChart = () => {
  if (!complexityChart.value || !patientCareSummary.value.complexity_analysis) return
  
  if (complexityChartInstance) {
    complexityChartInstance.destroy()
  }
  
  const ctx = complexityChart.value.getContext('2d')
  const data = patientCareSummary.value.complexity_analysis
  
  complexityChartInstance = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: data.map(item => capitalizeFirst(item.complexity_level)),
      datasets: [
        {
          label: 'Count',
          data: data.map(item => item.count),
          backgroundColor: '#3b82f6',
          yAxisID: 'y'
        },
        {
          label: 'Avg Duration (Days)',
          data: data.map(item => Math.round(item.avg_duration_days || 0)),
          backgroundColor: '#10b981',
          yAxisID: 'y1'
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          type: 'linear',
          display: true,
          position: 'left',
          beginAtZero: true
        },
        y1: {
          type: 'linear',
          display: true,
          position: 'right',
          beginAtZero: true,
          grid: {
            drawOnChartArea: false,
          },
        }
      }
    }
  })
}

const renderDurationChart = () => {
  if (!durationChart.value || !carePlanPerformance.value.average_duration) return
  
  if (durationChartInstance) {
    durationChartInstance.destroy()
  }
  
  const ctx = durationChart.value.getContext('2d')
  const data = carePlanPerformance.value.average_duration
  
  durationChartInstance = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: data.map(item => formatCareType(item.care_type)),
      datasets: [{
        label: 'Average Duration (Days)',
        data: data.map(item => Math.round(item.avg_duration_days || 0)),
        backgroundColor: '#3b82f6'
      }]
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false
        }
      },
      scales: {
        x: {
          beginAtZero: true
        }
      }
    }
  })
}

const renderNurseHoursChart = () => {
  if (!nurseHoursChart.value || !nurseProductivity.value.hours_worked) return
  
  if (nurseHoursChartInstance) {
    nurseHoursChartInstance.destroy()
  }
  
  const ctx = nurseHoursChart.value.getContext('2d')
  const data = nurseProductivity.value.hours_worked.slice(0, 10)
  
  nurseHoursChartInstance = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: data.map(item => `${item.nurse.first_name} ${item.nurse.last_name}`),
      datasets: [{
        label: 'Total Hours',
        data: data.map(item => Math.round(item.total_hours)),
        backgroundColor: '#10b981'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false
        }
      },
      scales: {
        y: {
          beginAtZero: true
        },
        x: {
          ticks: {
            maxRotation: 45
          }
        }
      }
    }
  })
}

const renderSessionTypesChart = () => {
  if (!sessionTypesChart.value || !timeTrackingAnalytics.value.session_types) return
  
  if (sessionTypesChartInstance) {
    sessionTypesChartInstance.destroy()
  }
  
  const ctx = sessionTypesChart.value.getContext('2d')
  const data = timeTrackingAnalytics.value.session_types
  
  sessionTypesChartInstance = new Chart(ctx, {
    type: 'pie',
    data: {
      labels: data.map(item => formatSessionType(item.session_type)),
      datasets: [{
        data: data.map(item => item.count),
        backgroundColor: [
          '#3b82f6',
          '#ef4444',
          '#f59e0b',
          '#10b981'
        ]
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom'
        }
      }
    }
  })
}

const applyFilters = () => {
  if (activeTab.value === 'care') {
    loadCarePlanAnalytics()
    loadCarePlanPerformance()
  } else {
    loadNurseProductivity()
    loadScheduleCompliance()
    loadTimeTrackingAnalytics()
  }
}

const refreshAllReports = async () => {
  loading.value = true
  try {
    if (activeTab.value === 'care') {
      await Promise.all([
        loadCarePlanAnalytics(),
        loadPatientCareSummary(),
        loadCarePlanPerformance()
      ])
    } else {
      await Promise.all([
        loadNurseProductivity(),
        loadScheduleCompliance(),
        loadTimeTrackingAnalytics()
      ])
    }
    toast.showSuccess('Reports refreshed successfully')
  } catch (error) {
    console.error('Error refreshing reports:', error)
    toast.showError('Failed to refresh reports')
  } finally {
    loading.value = false
  }
}

const exportReport = async (reportType) => {
  try {
    const filterParams = {
      date_from: filters.value.dateFrom,
      date_to: filters.value.dateTo
    }
    
    if (filters.value.nurseId) {
      filterParams.nurse_id = filters.value.nurseId
    }
    
    const { blob, filename } = await exportCareNurseReport(reportType, filterParams)
    
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = filename
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
    
    toast.showSuccess('Report exported successfully')
  } catch (error) {
    console.error('Error exporting report:', error)
    toast.showError(error.message || 'Failed to export report')
  }
}

const exportAllReports = async () => {
  try {
    const filterParams = {
      date_from: filters.value.dateFrom,
      date_to: filters.value.dateTo
    }
    
    if (filters.value.nurseId) {
      filterParams.nurse_id = filters.value.nurseId
    }
    
    toast.showInfo('Exporting all reports... This may take a moment.')
    
    const results = activeTab.value === 'care'
      ? await exportAllCareReports(filterParams)
      : await exportAllNurseReports(filterParams)
    
    let successCount = 0
    let failureCount = 0
    
    results.forEach(result => {
      if (result.success) {
        const url = window.URL.createObjectURL(result.blob)
        const link = document.createElement('a')
        link.href = url
        link.download = result.filename
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)
        successCount++
      } else {
        failureCount++
        console.error(`Failed to export ${result.reportType}:`, result.error)
      }
    })
    
    if (successCount > 0) {
      toast.showSuccess(`Successfully exported ${successCount} report(s)`)
    }
    
    if (failureCount > 0) {
      toast.showWarning(`${failureCount} report(s) failed to export`)
    }
  } catch (error) {
    console.error('Error exporting all reports:', error)
    toast.showError('Failed to export reports')
  }
}

// Utility functions
const formatCareType = (careType) => {
  return careType.replace(/_/g, ' ')
    .split(' ')
    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ')
}

const formatSessionType = (sessionType) => {
  return sessionType.replace(/_/g, ' ')
    .split(' ')
    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ')
}

const capitalizeFirst = (str) => {
  return str.charAt(0).toUpperCase() + str.slice(1)
}

// Lifecycle
onMounted(async () => {
  await loadAvailableNurses()
  await refreshAllReports()
})
</script>

<style scoped>
.reports-page {
  min-height: 100vh;
  background: #f8f9fa;
}

.page-header {
  display: flex;
  flex-direction: column;
  margin-bottom: 2rem;
}

@media (min-width: 768px) {
  .page-header {
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
  }
}

.page-header-content h1 {
  font-size: 2rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0 0 0.5rem 0;
}

.page-header-content p {
  color: #6b7280;
  margin: 0;
}

.page-header-actions {
  margin-top: 1rem;
  display: flex;
  gap: 0.75rem;
}

@media (min-width: 768px) {
  .page-header-actions {
    margin-top: 0;
  }
}

.filters-section {
  background: white;
  border-radius: 0.75rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  border: 1px solid #e5e7eb;
  padding: 1.5rem;
  margin-bottom: 2rem;
}

.filters-content {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1rem;
}

@media (min-width: 768px) {
  .filters-content {
    grid-template-columns: 2fr 1fr;
  }
}

.date-range {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.date-range span {
  color: #6b7280;
  font-size: 0.875rem;
  font-weight: 500;
}

.tabs-container {
  border-bottom: 1px solid #e5e7eb;
  margin-bottom: 2rem;
}

.tabs {
  display: flex;
  gap: 2rem;
  overflow-x: auto;
  scrollbar-width: none;
  -ms-overflow-style: none;
}

.tabs::-webkit-scrollbar {
  display: none;
}

.tab {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 1rem 0;
  background: none;
  border: none;
  font-size: 1rem;
  font-weight: 500;
  color: #6b7280;
  cursor: pointer;
  border-bottom: 2px solid transparent;
  transition: all 0.2s;
  white-space: nowrap;
  text-decoration: none;
}

.tab.tab-active {
  color: #3b82f6;
  border-bottom-color: #3b82f6;
}

.tab:hover:not(.tab-active) {
  color: #374151;
}

.tab svg {
  width: 18px;
  height: 18px;
  flex-shrink: 0;
}

.tab-content {
  animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.reports-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 2rem;
}

.report-card {
  background: white;
  border-radius: 0.75rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  border: 1px solid #e5e7eb;
  overflow: hidden;
}

.report-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
  background: #f9fafb;
}

.report-header h3 {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1f2937;
  margin: 0;
  flex: 1;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.stat-card {
  background: #f8fafc;
  border-radius: 0.5rem;
  padding: 1rem;
  text-align: center;
  border-left: 4px solid #3b82f6;
}

.stat-card.success {
  border-left-color: #10b981;
}

.stat-card.info {
  border-left-color: #3b82f6;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1f2937;
  margin-bottom: 0.25rem;
}

.stat-label {
  font-size: 0.875rem;
  color: #6b7280;
  font-weight: 500;
}

.chart-container {
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.chart-container h4 {
  font-size: 1rem;
  font-weight: 600;
  color: #1f2937;
  margin: 0 0 1rem 0;
}

.chart-container canvas {
  max-height: 300px;
}

.table-container {
  padding: 1.5rem;
}

.table-container h4 {
  font-size: 1rem;
  font-weight: 600;
  color: #1f2937;
  margin: 0 0 1rem 0;
}

.table-scroll {
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
}

.data-table th {
  background: #f9fafb;
  padding: 0.75rem;
  text-align: left;
  font-weight: 500;
  color: #6b7280;
  border-bottom: 1px solid #e5e7eb;
  white-space: nowrap;
}

.data-table td {
  padding: 0.75rem;
  border-bottom: 1px solid #f3f4f6;
  vertical-align: top;
}

.data-table tr:hover {
  background: #f9fafb;
}

.care-type-badge,
.nurse-info strong,
.patient-info strong,
.doctor-info strong {
  display: block;
  color: #1f2937;
  font-weight: 500;
}

.care-type-badge {
  background: #e0f2fe;
  color: #0369a1;
  padding: 0.25rem 0.5rem;
  border-radius: 0.375rem;
  font-size: 0.8125rem;
  font-weight: 500;
  display: inline-block;
}

.progress-bar {
  position: relative;
  background: #f3f4f6;
  border-radius: 0.375rem;
  height: 1.25rem;
  overflow: hidden;
  min-width: 80px;
}

.progress-fill {
  height: 100%;
  background: #10b981;
  transition: width 0.3s ease;
}

.progress-text {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-size: 0.75rem;
  font-weight: 500;
  color: #1f2937;
}

.form-control {
  width: 100%;
  padding: 0.5rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.form-control:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-group {
  margin-bottom: 0;
}

.form-group label {
  display: block;
  font-weight: 500;
  color: #374151;
  margin-bottom: 0.5rem;
  font-size: 0.875rem;
}

.text-muted {
  color: #6b7280;
}
</style>