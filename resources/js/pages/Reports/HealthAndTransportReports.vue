<template>
  <MainLayout>
    <div class="reports-page">
      <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Page Header -->
        <div class="page-header">
          <div class="page-header-content">
            <h1>Patient Health Reports</h1>
            <p>Health trends, patient outcomes, and medical analytics</p>
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
            <!-- <div class="form-group">
              <label>Patient Filter</label>
              <select v-model="filters.patientId" @change="applyFilters" class="form-control">
                <option value="">All Patients</option>
                <option v-for="patient in availablePatients" :key="patient.id" :value="patient.id">
                  {{ patient.first_name }} {{ patient.last_name }}
                </option>
              </select>
            </div> -->
          </div>
        </div>

        <!-- Patient Health Reports -->
        <div class="tab-content">
          <div class="reports-grid">
            
            <!-- 1. Patient Health Trends -->
            <div class="report-card">
              <div class="report-header">
                <h3>Patient Health Trends</h3>
                <button @click="exportReport('patient_health_trends')" class="btn btn-sm btn-secondary">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3" />
                  </svg>
                </button>
              </div>

              <!-- Condition Trends Chart -->
              <div class="chart-container">
                <h4>Patient Condition Trends</h4>
                <canvas ref="conditionTrendsChart" width="400" height="250"></canvas>
              </div>

              <!-- Average Pain Levels -->
              <div class="chart-container">
                <h4>Average Pain Levels by Patient</h4>
                <canvas ref="painLevelsChart" width="400" height="300"></canvas>
              </div>

              <!-- Condition Status Table -->
              <div class="table-container" v-if="healthTrends.condition_trends">
                <h4>Condition Status Overview</h4>
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>Patient</th>
                      <th>Condition</th>
                      <th>Avg Pain Level</th>
                      <th>Visit Count</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="trend in healthTrends.condition_trends" :key="trend.patient_id + trend.general_condition">
                      <td>
                        <div class="patient-info">
                          <strong>{{ trend.patient.first_name }} {{ trend.patient.last_name }}</strong>
                        </div>
                      </td>
                      <td>
                        <span :class="'badge ' + getConditionBadgeClass(trend.general_condition)">
                          {{ capitalizeFirst(trend.general_condition) }}
                        </span>
                      </td>
                      <td>{{ Math.round(trend.avg_pain_level * 10) / 10 }}/10</td>
                      <td>{{ trend.count }}</td>
                      <td>
                        <div class="status-indicator">
                          <span :class="getConditionIndicator(trend.general_condition)"></span>
                          {{ getConditionStatus(trend.general_condition) }}
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- 2. Progress Notes Analytics -->
            <div class="report-card">
              <div class="report-header">
                <h3>Progress Notes Analytics</h3>
                <button @click="exportReport('progress_notes_analytics')" class="btn btn-sm btn-secondary">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3" />
                  </svg>
                </button>
              </div>

              <!-- Pain Level Trends Chart -->
              <div class="chart-container">
                <h4>Pain Level Trends Over Time</h4>
                <canvas ref="painTrendsChart" width="400" height="200"></canvas>
              </div>

              <!-- Visit Frequency -->
              <div class="table-container" v-if="progressNotes.visit_frequency">
                <h4>Visit Frequency Analysis</h4>
                <div class="table-scroll">
                  <table class="data-table">
                    <thead>
                      <tr>
                        <th>Patient</th>
                        <th>Total Visits</th>
                        <th>Different Nurses</th>
                        <th>Avg Pain Level</th>
                        <th>First Visit</th>
                        <th>Last Visit</th>
                        <th>Duration</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="patient in progressNotes.visit_frequency" :key="patient.patient_id">
                        <td>
                          <div class="patient-info">
                            <strong>{{ patient.patient.first_name }} {{ patient.patient.last_name }}</strong>
                          </div>
                        </td>
                        <td>
                          <span class="badge badge-info">{{ patient.total_visits }}</span>
                        </td>
                        <td>{{ patient.different_nurses }}</td>
                        <td>
                          <span :class="getPainLevelBadge(patient.avg_pain_level)">
                            {{ Math.round(patient.avg_pain_level * 10) / 10 }}/10
                          </span>
                        </td>
                        <td>{{ formatDate(patient.first_visit) }}</td>
                        <td>{{ formatDate(patient.last_visit) }}</td>
                        <td>{{ calculateDuration(patient.first_visit, patient.last_visit) }} days</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- Intervention Effectiveness -->
              <div class="chart-container">
                <h4>Intervention Effectiveness</h4>
                <canvas ref="interventionChart" width="400" height="250"></canvas>
              </div>
            </div>

            <!-- 3. Patient Outcomes Report -->
            <div class="report-card">
              <div class="report-header">
                <h3>Patient Outcomes Report</h3>
                <button @click="exportReport('patient_outcomes')" class="btn btn-sm btn-secondary">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3" />
                  </svg>
                </button>
              </div>

              <!-- Care Satisfaction Chart -->
              <div class="chart-container">
                <h4>Care Satisfaction Levels</h4>
                <canvas ref="satisfactionChart" width="400" height="250"></canvas>
              </div>

              <!-- Recovery Rates -->
              <div class="table-container" v-if="patientOutcomes.recovery_rates">
                <h4>Patient Recovery Progress</h4>
                <div class="table-scroll">
                  <table class="data-table">
                    <thead>
                      <tr>
                        <th>Patient ID</th>
                        <th>Initial Condition</th>
                        <th>Latest Condition</th>
                        <th>Total Visits</th>
                        <th>Recovery Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="recovery in patientOutcomes.recovery_rates" :key="recovery.patient_id">
                        <td>#{{ recovery.patient_id }}</td>
                        <td>
                          <span :class="'badge ' + getConditionBadgeClass(recovery.initial_condition)">
                            {{ capitalizeFirst(recovery.initial_condition) }}
                          </span>
                        </td>
                        <td>
                          <span :class="'badge ' + getConditionBadgeClass(recovery.latest_condition)">
                            {{ capitalizeFirst(recovery.latest_condition) }}
                          </span>
                        </td>
                        <td>{{ recovery.total_visits }}</td>
                        <td>
                          <span :class="getRecoveryBadge(recovery.initial_condition, recovery.latest_condition)">
                            {{ getRecoveryStatus(recovery.initial_condition, recovery.latest_condition) }}
                          </span>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- Readmission Patterns -->
              <div class="table-container" v-if="patientOutcomes.readmission_patterns">
                <h4>Readmission Patterns (Multiple Care Plans)</h4>
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>Patient</th>
                      <th>Total Care Plans</th>
                      <th>Completed</th>
                      <th>Completion Rate</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="patient in patientOutcomes.readmission_patterns" :key="patient.patient_id">
                      <td>
                        <div class="patient-info">
                          <strong>{{ patient.patient.first_name }} {{ patient.patient.last_name }}</strong>
                        </div>
                      </td>
                      <td>{{ patient.total_care_plans }}</td>
                      <td>{{ patient.completed_plans }}</td>
                      <td>
                        <div class="progress-bar">
                          <div class="progress-fill" :style="{ width: ((patient.completed_plans / patient.total_care_plans) * 100) + '%' }"></div>
                          <span class="progress-text">{{ Math.round((patient.completed_plans / patient.total_care_plans) * 100) }}%</span>
                        </div>
                      </td>
                      <td>
                        <span :class="patient.total_care_plans > 2 ? 'badge badge-warning' : 'badge badge-info'">
                          {{ patient.total_care_plans > 2 ? 'High Readmission' : 'Normal' }}
                        </span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- 4. Medical Condition Reports -->
            <div class="report-card">
              <div class="report-header">
                <h3>Medical Condition Reports</h3>
                <button @click="exportReport('medical_conditions')" class="btn btn-sm btn-secondary">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3" />
                  </svg>
                </button>
              </div>

              <!-- Disease Prevalence Chart -->
              <div class="chart-container">
                <h4>Disease Prevalence (Top 10)</h4>
                <canvas ref="diseasePrevalenceChart" width="400" height="300"></canvas>
              </div>

              <!-- Treatment Effectiveness -->
              <div class="table-container" v-if="medicalConditions.treatment_effectiveness">
                <h4>Treatment Effectiveness by Care Type</h4>
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>Care Type</th>
                      <th>Total Plans</th>
                      <th>Avg Completion</th>
                      <th>Successful Treatments</th>
                      <th>Success Rate</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="treatment in medicalConditions.treatment_effectiveness" :key="treatment.care_type">
                      <td>
                        <span class="care-type-badge">{{ formatCareType(treatment.care_type) }}</span>
                      </td>
                      <td>{{ treatment.total_plans }}</td>
                      <td>{{ Math.round(treatment.avg_completion) }}%</td>
                      <td>{{ treatment.successful_treatments }}</td>
                      <td>
                        <div class="progress-bar">
                          <div class="progress-fill" :style="{ width: ((treatment.successful_treatments / treatment.total_plans) * 100) + '%' }"></div>
                          <span class="progress-text">{{ Math.round((treatment.successful_treatments / treatment.total_plans) * 100) }}%</span>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- Severity Trends -->
              <div class="table-container" v-if="medicalConditions.severity_trends">
                <h4>Condition Severity Distribution</h4>
                <div class="severity-grid">
                  <div v-for="severity in medicalConditions.severity_trends" :key="severity.general_condition + severity.mobility_status" class="severity-card">
                    <div class="severity-header">
                      <span :class="'badge ' + getConditionBadgeClass(severity.general_condition)">
                        {{ capitalizeFirst(severity.general_condition) }}
                      </span>
                    </div>
                    <div class="severity-details">
                      <div class="detail-row">
                        <span class="detail-label">Hydration:</span>
                        <span>{{ capitalizeFirst(severity.hydration_status) }}</span>
                      </div>
                      <div class="detail-row">
                        <span class="detail-label">Nutrition:</span>
                        <span>{{ capitalizeFirst(severity.nutrition_status) }}</span>
                      </div>
                      <div class="detail-row">
                        <span class="detail-label">Mobility:</span>
                        <span>{{ capitalizeFirst(severity.mobility_status) }}</span>
                      </div>
                      <div class="detail-row">
                        <span class="detail-label">Count:</span>
                        <strong>{{ severity.count }} patients</strong>
                      </div>
                    </div>
                  </div>
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
import { ref, onMounted, nextTick, inject } from 'vue'
import MainLayout from '../../layout/MainLayout.vue'
import Toast from '../../common/components/Toast.vue'
import Chart from 'chart.js/auto'
import {
  getPatientHealthTrends,
  getProgressNotesAnalytics,
  getPatientOutcomes,
  getMedicalConditions,
  getAvailablePatients,
  exportPatientReport,
  exportAllPatientReports
} from '../../services/patientReportsService'

const toast = inject('toast')

// Reactive data
const loading = ref(false)
const availablePatients = ref([])

// Report data
const healthTrends = ref({})
const progressNotes = ref({})
const patientOutcomes = ref({})
const medicalConditions = ref({})

// Filters
const filters = ref({
  dateFrom: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
  dateTo: new Date().toISOString().split('T')[0],
  patientId: ''
})

// Chart references
const conditionTrendsChart = ref(null)
const painLevelsChart = ref(null)
const painTrendsChart = ref(null)
const interventionChart = ref(null)
const satisfactionChart = ref(null)
const diseasePrevalenceChart = ref(null)

// Chart instances
let conditionTrendsChartInstance = null
let painLevelsChartInstance = null
let painTrendsChartInstance = null
let interventionChartInstance = null
let satisfactionChartInstance = null
let diseasePrevalenceChartInstance = null

// Methods
const loadAvailablePatients = async () => {
  try {
    const response = await getAvailablePatients()
    availablePatients.value = response.data || response
  } catch (error) {
    console.error('Error loading patients:', error)
    toast.showError('Failed to load patients')
  }
}

const loadHealthTrends = async () => {
  try {
    const filterParams = {
      date_from: filters.value.dateFrom,
      date_to: filters.value.dateTo
    }
    
    if (filters.value.patientId) {
      filterParams.patient_id = filters.value.patientId
    }
    
    healthTrends.value = await getPatientHealthTrends(filterParams)
    await nextTick()
    renderConditionTrendsChart()
    renderPainLevelsChart()
  } catch (error) {
    console.error('Error loading health trends:', error)
    toast.showError('Failed to load health trends')
  }
}

const loadProgressNotes = async () => {
  try {
    const filterParams = {
      date_from: filters.value.dateFrom,
      date_to: filters.value.dateTo
    }
    
    if (filters.value.patientId) {
      filterParams.patient_id = filters.value.patientId
    }
    
    progressNotes.value = await getProgressNotesAnalytics(filterParams)
    await nextTick()
    renderPainTrendsChart()
    renderInterventionChart()
  } catch (error) {
    console.error('Error loading progress notes:', error)
    toast.showError('Failed to load progress notes analytics')
  }
}

const loadPatientOutcomes = async () => {
  try {
    const filterParams = {
      date_from: filters.value.dateFrom,
      date_to: filters.value.dateTo
    }
    
    if (filters.value.patientId) {
      filterParams.patient_id = filters.value.patientId
    }
    
    patientOutcomes.value = await getPatientOutcomes(filterParams)
    await nextTick()
    renderSatisfactionChart()
  } catch (error) {
    console.error('Error loading patient outcomes:', error)
    toast.showError('Failed to load patient outcomes')
  }
}

const loadMedicalConditions = async () => {
  try {
    const filterParams = {
      date_from: filters.value.dateFrom,
      date_to: filters.value.dateTo
    }
    
    if (filters.value.patientId) {
      filterParams.patient_id = filters.value.patientId
    }
    
    medicalConditions.value = await getMedicalConditions(filterParams)
    await nextTick()
    renderDiseasePrevalenceChart()
  } catch (error) {
    console.error('Error loading medical conditions:', error)
    toast.showError('Failed to load medical conditions report')
  }
}

// Chart rendering methods
const renderConditionTrendsChart = () => {
  if (!conditionTrendsChart.value || !healthTrends.value.condition_trends) return
  
  if (conditionTrendsChartInstance) {
    conditionTrendsChartInstance.destroy()
  }
  
  const ctx = conditionTrendsChart.value.getContext('2d')
  
  const conditions = ['improved', 'stable', 'deteriorating']
  const data = conditions.map(condition => {
    const items = healthTrends.value.condition_trends.filter(t => t.general_condition === condition)
    return items.reduce((sum, item) => sum + item.count, 0)
  })
  
  conditionTrendsChartInstance = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: ['Improved', 'Stable', 'Deteriorating'],
      datasets: [{
        data: data,
        backgroundColor: ['#10b981', '#f59e0b', '#ef4444']
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

const renderPainLevelsChart = () => {
  if (!painLevelsChart.value || !healthTrends.value.condition_trends) return
  
  if (painLevelsChartInstance) {
    painLevelsChartInstance.destroy()
  }
  
  const ctx = painLevelsChart.value.getContext('2d')
  const data = healthTrends.value.condition_trends.slice(0, 10)
  
  painLevelsChartInstance = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: data.map(t => `${t.patient_name}`),
      datasets: [{
        label: 'Average Pain Level',
        data: data.map(t => Math.round(t.avg_pain_level * 10) / 10),
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
          max: 10,
          ticks: {
            stepSize: 1
          }
        }
      }
    }
  })
}

const renderPainTrendsChart = () => {
  if (!painTrendsChart.value || !progressNotes.value.pain_level_trends) return
  
  if (painTrendsChartInstance) {
    painTrendsChartInstance.destroy()
  }
  
  const ctx = painTrendsChart.value.getContext('2d')
  const data = progressNotes.value.pain_level_trends
  
  painTrendsChartInstance = new Chart(ctx, {
    type: 'line',
    data: {
      labels: data.map(item => item.date),
      datasets: [{
        label: 'Average Pain Level',
        data: data.map(item => Math.round(item.avg_pain_level * 10) / 10),
        borderColor: '#ef4444',
        backgroundColor: 'rgba(239, 68, 68, 0.1)',
        tension: 0.4,
        fill: true
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
          max: 10
        }
      }
    }
  })
}

const renderInterventionChart = () => {
  if (!interventionChart.value || !progressNotes.value.intervention_effectiveness) return
  
  if (interventionChartInstance) {
    interventionChartInstance.destroy()
  }
  
  const ctx = interventionChart.value.getContext('2d')
  const data = progressNotes.value.intervention_effectiveness
  
  interventionChartInstance = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: data.map(item => capitalizeFirst(item.general_condition)),
      datasets: [
        {
          label: 'Total Cases',
          data: data.map(item => item.count),
          backgroundColor: '#3b82f6'
        },
        {
          label: 'With Interventions',
          data: data.map(item => item.with_interventions),
          backgroundColor: '#10b981'
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom'
        }
      },
      scales: {
        y: {
          beginAtZero: true
        }
      }
    }
  })
}

const renderSatisfactionChart = () => {
  if (!satisfactionChart.value || !patientOutcomes.value.care_satisfaction) return
  
  if (satisfactionChartInstance) {
    satisfactionChartInstance.destroy()
  }
  
  const ctx = satisfactionChart.value.getContext('2d')
  const data = patientOutcomes.value.care_satisfaction
  
  satisfactionChartInstance = new Chart(ctx, {
    type: 'pie',
    data: {
      labels: data.map(item => capitalizeFirst(item.satisfaction_proxy.replace('_', ' '))),
      datasets: [{
        data: data.map(item => item.count),
        backgroundColor: ['#10b981', '#f59e0b', '#ef4444']
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

const renderDiseasePrevalenceChart = () => {
  if (!diseasePrevalenceChart.value || !medicalConditions.value.disease_prevalence) return
  
  if (diseasePrevalenceChartInstance) {
    diseasePrevalenceChartInstance.destroy()
  }
  
  const ctx = diseasePrevalenceChart.value.getContext('2d')
  const data = medicalConditions.value.disease_prevalence.slice(0, 10)
  
  diseasePrevalenceChartInstance = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: data.map(item => item.presenting_condition.substring(0, 30)),
      datasets: [{
        label: 'Patient Count',
        data: data.map(item => item.unique_patients),
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

const applyFilters = () => {
  loadHealthTrends()
  loadProgressNotes()
  loadPatientOutcomes()
  loadMedicalConditions()
}

const refreshAllReports = async () => {
  loading.value = true
  try {
    await Promise.all([
      loadHealthTrends(),
      loadProgressNotes(),
      loadPatientOutcomes(),
      loadMedicalConditions()
    ])
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
    
    if (filters.value.patientId) {
      filterParams.patient_id = filters.value.patientId
    }
    
    const { blob, filename } = await exportPatientReport(reportType, filterParams)
    
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
    
    if (filters.value.patientId) {
      filterParams.patient_id = filters.value.patientId
    }
    
    toast.showInfo('Exporting all reports... This may take a moment.')
    
    const results = await exportAllPatientReports(filterParams)
    
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
const getConditionBadgeClass = (condition) => {
  const colorMap = {
    'improved': 'badge-success',
    'stable': 'badge-warning',
    'deteriorating': 'badge-danger'
  }
  return colorMap[condition] || 'badge-secondary'
}

const getConditionIndicator = (condition) => {
  const indicators = {
    'improved': 'indicator-green',
    'stable': 'indicator-yellow',
    'deteriorating': 'indicator-red'
  }
  return indicators[condition] || 'indicator-gray'
}

const getConditionStatus = (condition) => {
  const status = {
    'improved': 'Improving',
    'stable': 'Stable',
    'deteriorating': 'Needs Attention'
  }
  return status[condition] || 'Unknown'
}

const getPainLevelBadge = (painLevel) => {
  if (painLevel <= 3) return 'badge badge-success'
  if (painLevel <= 6) return 'badge badge-warning'
  return 'badge badge-danger'
}

const getRecoveryBadge = (initial, latest) => {
  const order = { 'deteriorating': 0, 'stable': 1, 'improved': 2 }
  if (order[latest] > order[initial]) return 'badge badge-success'
  if (order[latest] === order[initial]) return 'badge badge-warning'
  return 'badge badge-danger'
}

const getRecoveryStatus = (initial, latest) => {
  const order = { 'deteriorating': 0, 'stable': 1, 'improved': 2 }
  if (order[latest] > order[initial]) return 'Recovering'
  if (order[latest] === order[initial]) return 'No Change'
  return 'Declining'
}

const formatCareType = (careType) => {
  return careType.replace(/_/g, ' ')
    .split(' ')
    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ')
}

const capitalizeFirst = (str) => {
  return str.charAt(0).toUpperCase() + str.slice(1)
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString()
}

const calculateDuration = (start, end) => {
  if (!start || !end) return 0
  const diffMs = new Date(end) - new Date(start)
  return Math.floor(diffMs / (1000 * 60 * 60 * 24))
}

// Lifecycle
onMounted(async () => {
  await loadAvailablePatients()
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

.status-indicator {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  color: #374151;
}

.status-indicator span:first-child {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  display: inline-block;
}

.indicator-green {
  background: #10b981;
}

.indicator-yellow {
  background: #f59e0b;
}

.indicator-red {
  background: #ef4444;
}

.indicator-gray {
  background: #6b7280;
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

.severity-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 1rem;
}

.severity-card {
  background: #f9fafb;
  border-radius: 0.5rem;
  border: 1px solid #e5e7eb;
  overflow: hidden;
}

.severity-header {
  padding: 0.75rem 1rem;
  background: white;
  border-bottom: 1px solid #e5e7eb;
}

.severity-details {
  padding: 1rem;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  padding: 0.5rem 0;
  border-bottom: 1px solid #f3f4f6;
  font-size: 0.875rem;
}

.detail-row:last-child {
  border-bottom: none;
}

.detail-label {
  color: #6b7280;
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

.patient-info strong {
  display: block;
  color: #1f2937;
  font-weight: 500;
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
</style>