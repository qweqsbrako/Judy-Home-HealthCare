<template>
  <MainLayout>
    <div class="reports-page">
      <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Page Header -->
        <div class="page-header">
          <div class="page-header-content">
            <h1>Transportation Reports</h1>
            <p>Driver performance and transport analytics</p>
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
              <label>Report Period</label>
              <select v-model="filters.period" @change="handlePeriodChange" class="form-control">
                <option value="custom">Custom Range</option>
                <option value="today">Today</option>
                <option value="week">This Week</option>
                <option value="month">This Month</option>
                <option value="quarter">This Quarter</option>
                <option value="year">This Year</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Transport Summary Cards -->
        <div class="transport-summary">
          <div class="summary-card requests">
            <div class="summary-icon">
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
              </svg>
            </div>
            <div class="summary-content">
              <div class="summary-label">Total Requests</div>
              <div class="summary-value">{{ getTotalRequests() }}</div>
              <div class="summary-change positive">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd" />
                </svg>
                <span>Period total</span>
              </div>
            </div>
          </div>

          <div class="summary-card completion">
            <div class="summary-icon">
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div class="summary-content">
              <div class="summary-label">Completion Rate</div>
              <div class="summary-value">{{ getCompletionRate() }}%</div>
              <div class="summary-change positive">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd" />
                </svg>
                <span>Overall rate</span>
              </div>
            </div>
          </div>

          <div class="summary-card drivers">
            <div class="summary-icon">
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
            </div>
            <div class="summary-content">
              <div class="summary-label">Available Drivers</div>
              <div class="summary-value">{{ getAvailableDrivers() }}</div>
              <div class="summary-change positive">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd" />
                </svg>
                <span>Active drivers</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Reports Grid -->
        <div class="reports-grid">
          
          <!-- 1. Transport Utilization Report -->
          <div class="report-card">
            <div class="report-header">
              <h3>Transport Utilization Report</h3>
              <button @click="exportReport('transport_utilization')" class="btn btn-sm btn-secondary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3" />
                </svg>
              </button>
            </div>

            <!-- Request Volume Chart -->
            <div class="chart-container">
              <h4>Request Volumes Over Time</h4>
              <canvas ref="requestVolumeChart" width="400" height="250"></canvas>
            </div>

            <!-- Transport Types Distribution -->
            <div class="chart-container">
              <h4>Transport Types Distribution</h4>
              <canvas ref="transportTypesChart" width="400" height="300"></canvas>
            </div>

            <!-- Priority Analysis -->
            <div class="table-container" v-if="transportUtilization.priority_levels && transportUtilization.priority_levels.length > 0">
              <h4>Priority Levels Analysis</h4>
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Priority</th>
                    <th>Request Count</th>
                    <th>Avg Response Time</th>
                    <th>Completion Rate</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="priority in transportUtilization.priority_levels" :key="priority.priority">
                    <td>
                      <span :class="getPriorityBadge(priority.priority)">
                        {{ formatPriority(priority.priority) }}
                      </span>
                    </td>
                    <td>{{ priority.count }}</td>
                    <td>{{ Math.round(priority.avg_response_time || 0) }} min</td>
                    <td>
                      <div class="progress-bar">
                        <div class="progress-fill" :style="{ width: calculateCompletionRate(priority) + '%' }"></div>
                        <span class="progress-text">{{ calculateCompletionRate(priority) }}%</span>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Status Distribution -->
            <div class="stats-grid" v-if="transportUtilization.status_distribution && transportUtilization.status_distribution.length > 0">
              <div class="stat-card" v-for="status in transportUtilization.status_distribution" :key="status.status">
                <div class="stat-value">{{ status.count }}</div>
                <div class="stat-label">{{ formatStatus(status.status) }}</div>
              </div>
            </div>
          </div>

          <!-- 2. Driver Performance Report -->
          <div class="report-card">
            <div class="report-header">
              <h3>Driver Performance Report</h3>
              <button @click="exportReport('driver_performance')" class="btn btn-sm btn-secondary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3" />
                </svg>
              </button>
            </div>

            <!-- Driver Ratings Chart -->
            <div class="chart-container">
              <h4>Driver Ratings Distribution</h4>
              <canvas ref="driverRatingsChart" width="400" height="300"></canvas>
            </div>

            <!-- Completion Rates -->
            <div class="table-container" v-if="driverPerformance.completion_rates && driverPerformance.completion_rates.length > 0">
              <h4>Driver Completion Rates</h4>
              <div class="table-scroll">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>Driver</th>
                      <th>Total Trips</th>
                      <th>Completed</th>
                      <th>Cancelled</th>
                      <th>Completion Rate</th>
                      <th>Performance</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="driver in driverPerformance.completion_rates" :key="driver.driver_id">
                      <td>
                        <div class="driver-info">
                          <strong>{{ driver.driver.first_name }} {{ driver.driver.last_name }}</strong>
                          <div class="driver-meta">{{ driver.driver.phone || 'No phone' }}</div>
                        </div>
                      </td>
                      <td>{{ driver.total_trips }}</td>
                      <td class="success-value">{{ driver.completed_trips }}</td>
                      <td class="danger-value">{{ driver.cancelled_trips }}</td>
                      <td>
                        <div class="progress-bar">
                          <div class="progress-fill" :style="{ width: driver.completion_rate + '%' }"></div>
                          <span class="progress-text">{{ driver.completion_rate }}%</span>
                        </div>
                      </td>
                      <td>
                        <span :class="getPerformanceBadge(driver.completion_rate)">
                          {{ getPerformanceStatus(driver.completion_rate) }}
                        </span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Response Times -->
            <div class="table-container" v-if="driverPerformance.response_times && driverPerformance.response_times.length > 0">
              <h4>Average Response Times</h4>
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Driver</th>
                    <th>Avg Response Time</th>
                    <th>Min Time</th>
                    <th>Max Time</th>
                    <th>Efficiency</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="driver in driverPerformance.response_times" :key="driver.driver_id">
                    <td>
                      <strong>{{ driver.driver.first_name }} {{ driver.driver.last_name }}</strong>
                    </td>
                    <td>{{ Math.round(driver.avg_response_time || 0) }} min</td>
                    <td>{{ Math.round(driver.min_response_time || 0) }} min</td>
                    <td>{{ Math.round(driver.max_response_time || 0) }} min</td>
                    <td>
                      <span :class="getResponseTimeBadge(driver.avg_response_time)">
                        {{ getResponseTimeStatus(driver.avg_response_time) }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Driver Ratings -->
            <div class="table-container" v-if="driverPerformance.driver_ratings && driverPerformance.driver_ratings.length > 0">
              <h4>Driver Ratings Summary</h4>
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Driver</th>
                    <th>Average Rating</th>
                    <th>Rated Trips</th>
                    <th>Rating Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="driver in driverPerformance.driver_ratings" :key="driver.driver_id">
                    <td>
                      <strong>{{ driver.driver.first_name }} {{ driver.driver.last_name }}</strong>
                    </td>
                    <td>
                      <div class="rating-display">
                        <span class="rating-value">{{ parseFloat(driver.avg_rating).toFixed(1) }}</span>
                        <div class="stars">
                          <span v-for="star in 5" :key="star" :class="star <= Math.round(driver.avg_rating) ? 'star-filled' : 'star-empty'">★</span>
                        </div>
                      </div>
                    </td>
                    <td>{{ driver.rated_trips }}</td>
                    <td>
                      <span :class="getRatingBadge(driver.avg_rating)">
                        {{ getRatingStatus(driver.avg_rating) }}
                      </span>
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
import { ref, onMounted, nextTick, inject } from 'vue'
import MainLayout from '../../layout/MainLayout.vue'
import Toast from '../../common/components/Toast.vue'
import Chart from 'chart.js/auto'
import {
  getTransportUtilization,
  getDriverPerformance,
  exportTransportReport,
  exportAllTransportReports
} from '../../services/transportReportsService'

const toast = inject('toast')

// Reactive data
const loading = ref(false)

// Report data
const transportUtilization = ref({})
const driverPerformance = ref({})

// Filters
const filters = ref({
  dateFrom: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
  dateTo: new Date().toISOString().split('T')[0],
  period: 'custom'
})

// Chart references
const requestVolumeChart = ref(null)
const transportTypesChart = ref(null)
const driverRatingsChart = ref(null)

// Chart instances
let requestVolumeChartInstance = null
let transportTypesChartInstance = null
let driverRatingsChartInstance = null

// Computed properties for summary cards
const getTotalRequests = () => {
  let total = 0
  if (transportUtilization.value.request_volumes) {
    total = transportUtilization.value.request_volumes.reduce((sum, item) => 
      sum + (item.total_requests || 0), 0
    )
  }
  if (transportUtilization.value.status_distribution) {
    total = transportUtilization.value.status_distribution.reduce((sum, item) => 
      sum + (item.count || 0), 0
    )
  }
  if (transportUtilization.value.peak_hours) {
    total = transportUtilization.value.peak_hours.reduce((sum, item) => 
      sum + (item.request_count || 0), 0
    )
  }
  return total || 0
}

const getCompletionRate = () => {
  if (transportUtilization.value.status_distribution) {
    const completed = transportUtilization.value.status_distribution.find(s => s.status === 'completed')?.count || 0
    const total = transportUtilization.value.status_distribution.reduce((sum, item) => sum + item.count, 0)
    return total > 0 ? Math.round((completed / total) * 100) : 0
  }
  if (transportUtilization.value.priority_levels) {
    const totalCompleted = transportUtilization.value.priority_levels.reduce((sum, item) => sum + (item.completed || 0), 0)
    const totalRequests = transportUtilization.value.priority_levels.reduce((sum, item) => sum + (item.count || 0), 0)
    return totalRequests > 0 ? Math.round((totalCompleted / totalRequests) * 100) : 0
  }
  return 0
}

const getAvailableDrivers = () => {
  if (driverPerformance.value.availability_metrics) {
    return driverPerformance.value.availability_metrics.filter(d => d.is_available).length || 0
  }
  if (driverPerformance.value.completion_rates) {
    return driverPerformance.value.completion_rates.length || 0
  }
  return 0
}

// Methods
const loadTransportUtilization = async () => {
  try {
    const filterParams = {
      date_from: filters.value.dateFrom,
      date_to: filters.value.dateTo
    }
    
    const data = await getTransportUtilization(filterParams)
    if (data) {
      transportUtilization.value = data
      await nextTick()
      renderRequestVolumeChart()
      renderTransportTypesChart()
    }
  } catch (error) {
    console.error('Error loading transport utilization:', error)
    toast.showError('Failed to load transport utilization')
  }
}

const loadDriverPerformance = async () => {
  try {
    const filterParams = {
      date_from: filters.value.dateFrom,
      date_to: filters.value.dateTo
    }
    
    const data = await getDriverPerformance(filterParams)
    if (data) {
      driverPerformance.value = data
      await nextTick()
      renderDriverRatingsChart()
    }
  } catch (error) {
    console.error('Error loading driver performance:', error)
    toast.showError('Failed to load driver performance')
  }
}

// Chart rendering methods
const renderRequestVolumeChart = () => {
  if (!requestVolumeChart.value) return
  
  if (requestVolumeChartInstance) {
    requestVolumeChartInstance.destroy()
  }
  
  const ctx = requestVolumeChart.value.getContext('2d')
  
  let data = []
  if (transportUtilization.value.request_volumes) {
    data = transportUtilization.value.request_volumes
  } else if (transportUtilization.value.peak_hours) {
    data = transportUtilization.value.peak_hours.map(item => ({
      date: `Hour ${item.hour}`,
      total_requests: item.request_count,
      ambulance_requests: item.transport_type === 'ambulance' ? item.request_count : 0,
      regular_requests: item.transport_type === 'regular' ? item.request_count : 0
    }))
  }
  
  if (data.length === 0) {
    ctx.fillStyle = '#6b7280'
    ctx.textAlign = 'center'
    ctx.fillText('No data available for the selected period', ctx.canvas.width / 2, ctx.canvas.height / 2)
    return
  }
  
  requestVolumeChartInstance = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: data.map(item => item.date),
      datasets: [
        {
          label: 'Total Requests',
          data: data.map(item => item.total_requests || 0),
          backgroundColor: 'rgba(59, 130, 246, 0.8)',
          borderColor: '#3b82f6',
          borderWidth: 1,
          borderRadius: 4,
          borderSkipped: false
        },
        {
          label: 'Ambulance',
          data: data.map(item => item.ambulance_requests || 0),
          backgroundColor: 'rgba(239, 68, 68, 0.8)',
          borderColor: '#ef4444',
          borderWidth: 1,
          borderRadius: 4,
          borderSkipped: false
        },
        {
          label: 'Regular',
          data: data.map(item => item.regular_requests || 0),
          backgroundColor: 'rgba(16, 185, 129, 0.8)',
          borderColor: '#10b981',
          borderWidth: 1,
          borderRadius: 4,
          borderSkipped: false
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          beginAtZero: true,
          grid: {
            color: 'rgba(0, 0, 0, 0.1)'
          }
        },
        x: {
          grid: {
            display: false
          }
        }
      },
      plugins: {
        legend: {
          position: 'top',
          labels: {
            usePointStyle: true,
            padding: 20
          }
        },
        tooltip: {
          mode: 'index',
          intersect: false
        }
      },
      interaction: {
        mode: 'nearest',
        axis: 'x',
        intersect: false
      }
    }
  })
}

const renderTransportTypesChart = () => {
  if (!transportTypesChart.value) return
  
  if (transportTypesChartInstance) {
    transportTypesChartInstance.destroy()
  }
  
  const ctx = transportTypesChart.value.getContext('2d')
  
  let data = []
  if (transportUtilization.value.transport_types) {
    data = transportUtilization.value.transport_types
  } else if (transportUtilization.value.request_volumes) {
    const ambulanceTotal = transportUtilization.value.request_volumes.reduce((sum, item) => sum + (item.ambulance_requests || 0), 0)
    const regularTotal = transportUtilization.value.request_volumes.reduce((sum, item) => sum + (item.regular_requests || 0), 0)
    
    data = [
      { transport_type: 'ambulance', count: ambulanceTotal },
      { transport_type: 'regular', count: regularTotal }
    ].filter(item => item.count > 0)
  }
  
  if (data.length === 0) {
    ctx.fillStyle = '#6b7280'
    ctx.textAlign = 'center'
    ctx.fillText('No transport type data available', ctx.canvas.width / 2, ctx.canvas.height / 2)
    return
  }
  
  transportTypesChartInstance = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: data.map(item => formatTransportType(item.transport_type)),
      datasets: [{
        data: data.map(item => item.count),
        backgroundColor: ['#3b82f6', '#ef4444', '#10b981', '#f59e0b']
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

const renderDriverRatingsChart = () => {
  if (!driverRatingsChart.value) return
  
  if (driverRatingsChartInstance) {
    driverRatingsChartInstance.destroy()
  }
  
  const ctx = driverRatingsChart.value.getContext('2d')
  
  let data = []
  if (driverPerformance.value.driver_ratings) {
    data = driverPerformance.value.driver_ratings
  } else if (driverPerformance.value.completion_rates) {
    data = driverPerformance.value.completion_rates.map(item => ({
      driver: item.driver,
      avg_rating: item.completion_rate / 20
    }))
  }
  
  if (data.length === 0) {
    ctx.fillStyle = '#6b7280'
    ctx.textAlign = 'center'
    ctx.fillText('No driver rating data available', ctx.canvas.width / 2, ctx.canvas.height / 2)
    return
  }
  
  driverRatingsChartInstance = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: data.map(item => `${item.driver?.first_name || 'Unknown'} ${item.driver?.last_name || ''}`),
      datasets: [{
        label: 'Average Rating',
        data: data.map(item => parseFloat(item.avg_rating || 0)),
        backgroundColor: '#3b82f6',
        borderRadius: 4
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          beginAtZero: true,
          max: 5
        }
      },
      plugins: {
        legend: {
          display: false
        }
      }
    }
  })
}

const handlePeriodChange = () => {
  const today = new Date()
  let dateFrom = new Date()
  
  switch (filters.value.period) {
    case 'today':
      dateFrom = new Date(today)
      break
    case 'week':
      dateFrom.setDate(today.getDate() - 7)
      break
    case 'month':
      dateFrom.setMonth(today.getMonth() - 1)
      break
    case 'quarter':
      dateFrom.setMonth(today.getMonth() - 3)
      break
    case 'year':
      dateFrom.setFullYear(today.getFullYear() - 1)
      break
    default:
      return
  }
  
  filters.value.dateFrom = dateFrom.toISOString().split('T')[0]
  filters.value.dateTo = today.toISOString().split('T')[0]
  
  applyFilters()
}

const applyFilters = () => {
  refreshAllReports()
}

const refreshAllReports = async () => {
  loading.value = true
  try {
    await Promise.all([
      loadTransportUtilization(),
      loadDriverPerformance()
    ])
    toast.showSuccess('Transport reports refreshed successfully')
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
    
    const { blob, filename } = await exportTransportReport(reportType, filterParams)
    
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
    
    toast.showInfo('Exporting all reports... This may take a moment.')
    
    const results = await exportAllTransportReports(filterParams)
    
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
const formatPriority = (priority) => {
  const priorityMap = {
    'emergency': 'Emergency',
    'urgent': 'Urgent',
    'routine': 'Routine'
  }
  return priorityMap[priority] || priority
}

const formatStatus = (status) => {
  return status.replace(/_/g, ' ')
    .split(' ')
    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ')
}

const formatTransportType = (type) => {
  const typeMap = {
    'ambulance': 'Ambulance',
    'regular': 'Regular Transport'
  }
  return typeMap[type] || type
}

const calculateCompletionRate = (priority) => {
  if (!priority) return 0
  const total = priority.count || 0
  const completed = priority.completed || 0
  return total > 0 ? Math.round((completed / total) * 100) : 0
}

const getPriorityBadge = (priority) => {
  const badgeMap = {
    'emergency': 'badge badge-danger',
    'urgent': 'badge badge-warning',
    'routine': 'badge badge-success'
  }
  return badgeMap[priority] || 'badge badge-secondary'
}

const getPerformanceBadge = (rate) => {
  if (rate >= 95) return 'badge badge-success'
  if (rate >= 85) return 'badge badge-warning'
  return 'badge badge-danger'
}

const getPerformanceStatus = (rate) => {
  if (rate >= 95) return 'Excellent'
  if (rate >= 85) return 'Good'
  return 'Needs Improvement'
}

const getResponseTimeBadge = (time) => {
  if (time <= 15) return 'badge badge-success'
  if (time <= 30) return 'badge badge-warning'
  return 'badge badge-danger'
}

const getResponseTimeStatus = (time) => {
  if (time <= 15) return 'Excellent'
  if (time <= 30) return 'Good'
  return 'Slow'
}

const getRatingBadge = (rating) => {
  if (rating >= 4.5) return 'badge badge-success'
  if (rating >= 4.0) return 'badge badge-warning'
  return 'badge badge-danger'
}

const getRatingStatus = (rating) => {
  if (rating >= 4.5) return 'Excellent'
  if (rating >= 4.0) return 'Good'
  return 'Needs Improvement'
}

// Lifecycle
onMounted(() => {
  refreshAllReports()
})
</script>

<style scoped>
/* Styles remain the same as before */
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

.transport-summary {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.summary-card {
  background: white;
  border-radius: 0.75rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  border: 1px solid #e5e7eb;
  padding: 1.5rem;
  display: flex;
  gap: 1rem;
}

.summary-card.requests {
  border-left: 4px solid #3b82f6;
}

.summary-card.completion {
  border-left: 4px solid #10b981;
}

.summary-card.drivers {
  border-left: 4px solid #8b5cf6;
}

.summary-icon {
  width: 48px;
  height: 48px;
  border-radius: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.summary-card.requests .summary-icon {
  background: #dbeafe;
  color: #1e40af;
}

.summary-card.completion .summary-icon {
  background: #d1fae5;
  color: #065f46;
}

.summary-card.drivers .summary-icon {
  background: #ede9fe;
  color: #6b21a8;
}

.summary-icon svg {
  width: 24px;
  height: 24px;
}

.summary-content {
  flex: 1;
}

.summary-label {
  font-size: 0.875rem;
  color: #6b7280;
  font-weight: 500;
  margin-bottom: 0.5rem;
}

.summary-value {
  font-size: 1.875rem;
  font-weight: 700;
  color: #1f2937;
  margin-bottom: 0.5rem;
}

.summary-change {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.75rem;
  font-weight: 500;
}

.summary-change.positive {
  color: #10b981;
}

.summary-change svg {
  width: 16px;
  height: 16px;
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

.driver-info strong {
  display: block;
  color: #1f2937;
  font-weight: 500;
}

.driver-meta {
  font-size: 0.75rem;
  color: #6b7280;
}

.success-value {
  color: #10b981;
  font-weight: 600;
}

.danger-value {
  color: #ef4444;
  font-weight: 600;
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

.rating-display {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.rating-value {
  font-weight: 600;
  color: #1f2937;
}

.stars {
  display: flex;
  gap: 0.125rem;
}

.star-filled {
  color: #fbbf24;
}

.star-empty {
  color: #d1d5db;
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