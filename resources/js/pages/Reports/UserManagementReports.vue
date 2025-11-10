<template>
  <MainLayout>
    <div class="reports-page">
      <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Page Header -->
        <div class="page-header">
          <div class="page-header-content">
            <h1>User Management Reports</h1>
            <p>Analytics and insights for user activity, roles, and verification status</p>
          </div>
          <div class="page-header-actions">
            <button @click="exportAllReportsHandler" class="btn btn-secondary">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              Export Reports
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
              <label>Role Filter</label>
              <select v-model="filters.role" @change="applyFilters" class="form-control">
                <option value="all">All Roles</option>
                <option value="patient">Patients</option>
                <option value="nurse">Nurses</option>
                <option value="doctor">Doctors</option>
                <option value="admin">Admins</option>
                <option value="superadmin">Super Admins</option>
              </select>
            </div> -->
          </div>
        </div>

        <!-- Reports Grid -->
        <div class="reports-grid">
          
          <!-- 1. User Activity Report -->
          <div class="report-card">
            <div class="report-header">
              <h3>User Activity Report</h3>
              <button @click="exportReportHandler('user_activity')" class="btn btn-sm btn-secondary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3" />
                </svg>
              </button>
            </div>
            
            <!-- Summary Stats -->
            <div class="stats-grid" v-if="userActivityData.summary">
              <div class="stat-card">
                <div class="stat-value">{{ userActivityData.summary.total_users || 0 }}</div>
                <div class="stat-label">Total Users</div>
              </div>
              <div class="stat-card">
                <div class="stat-value">{{ userActivityData.summary.new_users_this_month || 0 }}</div>
                <div class="stat-label">New This Month</div>
              </div>
              <div class="stat-card">
                <div class="stat-value">{{ userActivityData.summary.active_users_this_week || 0 }}</div>
                <div class="stat-label">Active This Week</div>
              </div>
              <div class="stat-card">
                <div class="stat-value">{{ userActivityData.summary.growth_rate || 0 }}%</div>
                <div class="stat-label">Growth Rate</div>
              </div>
            </div>

            <!-- User Registrations Chart -->
            <div class="chart-container">
              <h4>User Registrations Over Time</h4>
              <canvas ref="registrationsChart" width="400" height="200"></canvas>
            </div>

            <!-- Login Frequency Chart -->
            <div class="chart-container">
              <h4>Login Frequency Distribution</h4>
              <canvas ref="loginFrequencyChart" width="400" height="200"></canvas>
            </div>

            <!-- Recent Activity Table -->
            <div class="table-container" v-if="userActivityData.recent_activity">
              <h4>Recent User Activity</h4>
              <table class="data-table">
                <thead>
                  <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Last Login</th>
                    <th>Registration Date</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="user in userActivityData.recent_activity" :key="user.id">
                    <td>
                      <div class="user-info">
                        <strong>{{ user.first_name }} {{ user.last_name }}</strong>
                        <div class="user-email">{{ user.email }}</div>
                      </div>
                    </td>
                    <td>
                      <span :class="'badge ' + getRoleBadgeClass(user.role)">
                        {{ capitalizeFirst(user.role) }}
                      </span>
                    </td>
                    <td>{{ formatTimeAgo(user.last_login_at) }}</td>
                    <td>{{ formatDate(user.created_at) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- 2. Role Distribution Report -->
          <div class="report-card">
            <div class="report-header">
              <h3>Role Distribution Report</h3>
              <button @click="exportReportHandler('role_distribution')" class="btn btn-sm btn-secondary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3" />
                </svg>
              </button>
            </div>

            <!-- Role Distribution Chart -->
            <div class="chart-container">
              <h4>User Distribution by Role</h4>
              <canvas ref="roleDistributionChart" width="400" height="300"></canvas>
            </div>

            <!-- Role Status Breakdown -->
            <div class="table-container" v-if="roleDistributionData.role_status_breakdown">
              <h4>Role Status Breakdown</h4>
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Role</th>
                    <th>Verified</th>
                    <th>Pending</th>
                    <th>Rejected</th>
                    <th>Suspended</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="role in groupedRoleStatus" :key="role.name">
                    <td>
                      <span :class="'badge ' + getRoleBadgeClass(role.name)">
                        {{ capitalizeFirst(role.name) }}
                      </span>
                    </td>
                    <td>{{ role.verified || 0 }}</td>
                    <td>{{ role.pending || 0 }}</td>
                    <td>{{ role.rejected || 0 }}</td>
                    <td>{{ role.suspended || 0 }}</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Professional Stats -->
            <div class="table-container" v-if="roleDistributionData.professional_stats">
              <h4>Professional Information Statistics</h4>
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Role</th>
                    <th>Avg Experience</th>
                    <th>With Specialization</th>
                    <th>With License</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="stat in roleDistributionData.professional_stats" :key="stat.role">
                    <td>
                      <span :class="'badge ' + getRoleBadgeClass(stat.role)">
                        {{ capitalizeFirst(stat.role) }}
                      </span>
                    </td>
                    <td>{{ stat.avg_experience ? Math.round(stat.avg_experience) + ' years' : 'N/A' }}</td>
                    <td>{{ stat.with_specialization || 0 }}</td>
                    <td>{{ stat.with_license || 0 }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- 3. Verification Status Report -->
          <div class="report-card">
            <div class="report-header">
              <h3>Verification Status Report</h3>
              <button @click="exportReportHandler('verification_status')" class="btn btn-sm btn-secondary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3" />
                </svg>
              </button>
            </div>

            <!-- Verification Summary Stats -->
            <div class="stats-grid" v-if="verificationStatusData.summary">
              <div class="stat-card warning">
                <div class="stat-value">{{ verificationStatusData.summary.total_pending || 0 }}</div>
                <div class="stat-label">Pending Verification</div>
              </div>
              <div class="stat-card success">
                <div class="stat-value">{{ verificationStatusData.summary.total_verified || 0 }}</div>
                <div class="stat-label">Verified</div>
              </div>
              <div class="stat-card danger">
                <div class="stat-value">{{ verificationStatusData.summary.total_rejected || 0 }}</div>
                <div class="stat-label">Rejected</div>
              </div>
              <div class="stat-card info">
                <div class="stat-value">{{ verificationStatusData.summary.overall_approval_rate || 0 }}%</div>
                <div class="stat-label">Approval Rate</div>
              </div>
            </div>

            <!-- Verification Trends Chart -->
            <div class="chart-container">
              <h4>Verification Trends Over Time</h4>
              <canvas ref="verificationTrendsChart" width="400" height="200"></canvas>
            </div>

            <!-- Pending Verifications Table -->
            <div class="table-container" v-if="verificationStatusData.pending_verifications">
              <h4>Pending Verifications ({{ verificationStatusData.pending_verifications.length }})</h4>
              <div class="table-scroll">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>User</th>
                      <th>Role</th>
                      <th>Ghana Card</th>
                      <th>Registration Date</th>
                      <th>Waiting Time</th>
                      <!-- <th>Actions</th> -->
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="user in verificationStatusData.pending_verifications" :key="user.id">
                      <td>
                        <div class="user-info">
                          <strong>{{ user.first_name }} {{ user.last_name }}</strong>
                          <div class="user-email">{{ user.email }}</div>
                        </div>
                      </td>
                      <td>
                        <span :class="'badge ' + getRoleBadgeClass(user.role)">
                          {{ capitalizeFirst(user.role) }}
                        </span>
                      </td>
                      <td>{{ user.ghana_card_number }}</td>
                      <td>{{ formatDate(user.created_at) }}</td>
                      <td>{{ formatTimeAgo(user.created_at) }}</td>
                      <!-- <td>
                        <div class="button-group">
                          <button @click="verifyUserHandler(user.id)" class="btn btn-sm btn-success">
                            Verify
                          </button>
                          <button @click="rejectUserHandler(user.id)" class="btn btn-sm btn-danger">
                            Reject
                          </button>
                        </div>
                      </td> -->
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Approval Rates by Role -->
            <div class="table-container" v-if="verificationStatusData.approval_rates_by_role">
              <h4>Approval Rates by Role</h4>
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Role</th>
                    <th>Total Applications</th>
                    <th>Approved</th>
                    <th>Rejected</th>
                    <th>Approval Rate</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="rate in verificationStatusData.approval_rates_by_role" :key="rate.role">
                    <td>
                      <span :class="'badge ' + getRoleBadgeClass(rate.role)">
                        {{ capitalizeFirst(rate.role) }}
                      </span>
                    </td>
                    <td>{{ rate.total || 0 }}</td>
                    <td>{{ rate.approved || 0 }}</td>
                    <td>{{ rate.rejected || 0 }}</td>
                    <td>
                      <div class="progress-bar">
                        <div class="progress-fill" :style="{ width: (rate.approval_rate || 0) + '%' }"></div>
                        <span class="progress-text">{{ rate.approval_rate || 0 }}%</span>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
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
import { ref, computed, onMounted, nextTick, inject } from 'vue'
import MainLayout from '../../layout/MainLayout.vue'
import Toast from '../../common/components/Toast.vue'
import Chart from 'chart.js/auto'
import * as reportsService from '../../services/reportsService'

const toast = inject('toast')

// Reactive data
const loading = ref(false)
const userActivityData = ref({})
const roleDistributionData = ref({})
const verificationStatusData = ref({})

// Filters
const filters = ref({
  dateFrom: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0], // 30 days ago
  dateTo: new Date().toISOString().split('T')[0], // today
  role: 'all'
})

// Chart references
const registrationsChart = ref(null)
const loginFrequencyChart = ref(null)
const roleDistributionChart = ref(null)
const verificationTrendsChart = ref(null)

// Chart instances
let registrationsChartInstance = null
let loginFrequencyChartInstance = null
let roleDistributionChartInstance = null
let verificationTrendsChartInstance = null

// Computed properties
const groupedRoleStatus = computed(() => {
  if (!roleDistributionData.value.role_status_breakdown) return []
  
  const grouped = {}
  roleDistributionData.value.role_status_breakdown.forEach(item => {
    if (!grouped[item.role]) {
      grouped[item.role] = { name: item.role }
    }
    grouped[item.role][item.verification_status] = item.count
  })
  
  return Object.values(grouped)
})

// Methods
const loadUserActivityReport = async () => {
  try {
    const filterParams = {
      date_from: filters.value.dateFrom,
      date_to: filters.value.dateTo,
      role: filters.value.role
    }
    
    const response = await reportsService.getUserActivityReport(filterParams)
    userActivityData.value = response.data || response
    
    await nextTick()
    renderRegistrationsChart()
    renderLoginFrequencyChart()
  } catch (error) {
    console.error('Error loading user activity report:', error)
    toast.showError('Failed to load user activity report')
  }
}

const loadRoleDistributionReport = async () => {
  try {
    const response = await reportsService.getRoleDistributionReport()
    roleDistributionData.value = response.data || response
    
    await nextTick()
    renderRoleDistributionChart()
  } catch (error) {
    console.error('Error loading role distribution report:', error)
    toast.showError('Failed to load role distribution report')
  }
}

const loadVerificationStatusReport = async () => {
  try {
    const filterParams = {
      date_from: filters.value.dateFrom,
      date_to: filters.value.dateTo
    }
    
    const response = await reportsService.getVerificationStatusReport(filterParams)
    verificationStatusData.value = response.data || response
    
    await nextTick()
    renderVerificationTrendsChart()
  } catch (error) {
    console.error('Error loading verification status report:', error)
    toast.showError('Failed to load verification status report')
  }
}

const renderRegistrationsChart = () => {
  if (!registrationsChart.value || !userActivityData.value.registrations) return
  
  if (registrationsChartInstance) {
    registrationsChartInstance.destroy()
  }
  
  const ctx = registrationsChart.value.getContext('2d')
  const data = userActivityData.value.registrations
  
  registrationsChartInstance = new Chart(ctx, {
    type: 'line',
    data: {
      labels: data.map(item => item.date),
      datasets: [{
        label: 'New Registrations',
        data: data.map(item => item.count),
        borderColor: '#3b82f6',
        backgroundColor: 'rgba(59, 130, 246, 0.1)',
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
          ticks: {
            stepSize: 1
          }
        }
      }
    }
  })
}

const renderLoginFrequencyChart = () => {
  if (!loginFrequencyChart.value || !userActivityData.value.login_frequency) return
  
  if (loginFrequencyChartInstance) {
    loginFrequencyChartInstance.destroy()
  }
  
  const ctx = loginFrequencyChart.value.getContext('2d')
  const data = userActivityData.value.login_frequency
  
  loginFrequencyChartInstance = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: data.map(item => {
        const labels = {
          'active_7_days': 'Active (7 days)',
          'active_30_days': 'Active (30 days)',
          'active_90_days': 'Active (90 days)',
          'inactive': 'Inactive'
        }
        return labels[item.frequency] || item.frequency
      }),
      datasets: [{
        data: data.map(item => item.count),
        backgroundColor: [
          '#10b981',
          '#f59e0b',
          '#ef4444',
          '#6b7280'
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

const renderRoleDistributionChart = () => {
  if (!roleDistributionChart.value || !roleDistributionData.value.role_distribution) return
  
  if (roleDistributionChartInstance) {
    roleDistributionChartInstance.destroy()
  }
  
  const ctx = roleDistributionChart.value.getContext('2d')
  const data = roleDistributionData.value.role_distribution
  
  roleDistributionChartInstance = new Chart(ctx, {
    type: 'pie',
    data: {
      labels: data.map(item => capitalizeFirst(item.role)),
      datasets: [{
        data: data.map(item => item.count),
        backgroundColor: [
          '#3b82f6',
          '#10b981',
          '#f59e0b',
          '#ef4444',
          '#8b5cf6'
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

const renderVerificationTrendsChart = () => {
  if (!verificationTrendsChart.value || !verificationStatusData.value.verification_trends) return
  
  if (verificationTrendsChartInstance) {
    verificationTrendsChartInstance.destroy()
  }
  
  const ctx = verificationTrendsChart.value.getContext('2d')
  const data = verificationStatusData.value.verification_trends
  
  // Group data by status
  const groupedData = {}
  data.forEach(item => {
    if (!groupedData[item.verification_status]) {
      groupedData[item.verification_status] = {}
    }
    groupedData[item.verification_status][item.date] = item.count
  })
  
  const dates = [...new Set(data.map(item => item.date))].sort()
  
  // Define color mapping for verification statuses
  const statusColors = {
    'verified': '#10b981',   // Green
    'rejected': '#ef4444',   // Red
    'pending': '#f59e0b',    // Orange/Amber
    'suspended': '#6b7280'   // Gray
  }
  
  const datasets = Object.keys(groupedData).map((status) => ({
    label: capitalizeFirst(status),
    data: dates.map(date => groupedData[status][date] || 0),
    borderColor: statusColors[status.toLowerCase()] || '#6b7280',
    backgroundColor: 'transparent',
    tension: 0.4
  }))
  
  verificationTrendsChartInstance = new Chart(ctx, {
    type: 'line',
    data: {
      labels: dates,
      datasets: datasets
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
          beginAtZero: true,
          ticks: {
            stepSize: 1
          }
        }
      }
    }
  })
}

const applyFilters = () => {
  refreshAllReports()
}

const refreshAllReports = async () => {
  loading.value = true
  try {
    await Promise.all([
      loadUserActivityReport(),
      loadRoleDistributionReport(),
      loadVerificationStatusReport()
    ])
    toast.showSuccess('Reports refreshed successfully')
  } catch (error) {
    console.error('Error refreshing reports:', error)
    toast.showError('Failed to refresh reports')
  } finally {
    loading.value = false
  }
}

const exportReportHandler = async (reportType) => {
  try {
    toast.showInfo('Preparing export...')
    
    const filterParams = {
      date_from: filters.value.dateFrom,
      date_to: filters.value.dateTo,
      role: filters.value.role
    }
    
    const { blob, filename } = await reportsService.exportReport(reportType, filterParams)
    
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

const exportAllReportsHandler = async () => {
  try {
    toast.showInfo('Exporting all reports...')
    
    const filterParams = {
      date_from: filters.value.dateFrom,
      date_to: filters.value.dateTo,
      role: filters.value.role
    }
    
    const results = await reportsService.exportAllReports(filterParams)
    
    // Download all successful exports
    let successCount = 0
    let failCount = 0
    
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
        console.error(`Failed to export ${result.reportType}:`, result.error)
        failCount++
      }
    })
    
    if (successCount > 0 && failCount === 0) {
      toast.showSuccess(`All ${successCount} reports exported successfully`)
    } else if (successCount > 0 && failCount > 0) {
      toast.showWarning(`${successCount} reports exported, ${failCount} failed`)
    } else {
      toast.showError('Failed to export reports')
    }
  } catch (error) {
    console.error('Error exporting all reports:', error)
    toast.showError('Failed to export all reports')
  }
}

const verifyUserHandler = async (userId) => {
  try {
    await reportsService.verifyUser(userId)
    toast.showSuccess('User verified successfully')
    await loadVerificationStatusReport()
  } catch (error) {
    console.error('Error verifying user:', error)
    toast.showError(error.message || 'Failed to verify user')
  }
}

const rejectUserHandler = async (userId) => {
  try {
    await reportsService.rejectUser(userId)
    toast.showSuccess('User rejected successfully')
    await loadVerificationStatusReport()
  } catch (error) {
    console.error('Error rejecting user:', error)
    toast.showError(error.message || 'Failed to reject user')
  }
}

// Utility functions
const getRoleBadgeClass = (role) => {
  const colorMap = {
    'doctor': 'badge-primary',
    'nurse': 'badge-success',
    'admin': 'badge-warning',
    'superadmin': 'badge-danger',
    'patient': 'badge-secondary'
  }
  return colorMap[role] || 'badge-secondary'
}

const capitalizeFirst = (str) => {
  return str.charAt(0).toUpperCase() + str.slice(1)
}

const formatTimeAgo = (dateString) => {
  if (!dateString) return 'Never'
  const date = new Date(dateString)
  const now = new Date()
  const diffMs = now - date
  const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24))
  
  if (diffDays === 0) return 'Today'
  if (diffDays === 1) return '1 day ago'
  if (diffDays < 7) return `${diffDays} days ago`
  if (diffDays < 30) return `${Math.floor(diffDays / 7)} weeks ago`
  return `${Math.floor(diffDays / 30)} months ago`
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString()
}

// Lifecycle
onMounted(() => {
  refreshAllReports()
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

.stat-card.warning {
  border-left-color: #f59e0b;
}

.stat-card.success {
  border-left-color: #10b981;
}

.stat-card.danger {
  border-left-color: #ef4444;
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

.user-info {
  min-width: 0;
}

.user-info strong {
  display: block;
  color: #1f2937;
  font-weight: 500;
}

.user-email {
  color: #6b7280;
  font-size: 0.8125rem;
  margin-top: 0.125rem;
}

.button-group {
  display: flex;
  gap: 0.5rem;
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
</style>