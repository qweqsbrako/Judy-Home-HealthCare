<template>
  <MainLayout>
    <div class="reports-page">
      <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Page Header -->
        <div class="page-header">
          <div class="page-header-content">
            <h1>Financial Reports</h1>
            <p>Revenue analytics and payment statistics</p>
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

        <!-- Financial Summary Cards -->
        <div class="financial-summary">
          <div class="summary-card revenue">
            <div class="summary-icon">
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div class="summary-content">
              <div class="summary-label">Total Revenue</div>
              <div class="summary-value">{{ formatCurrency(getTotalRevenue()) }}</div>
              <div :class="['summary-change', getRevenueChangeClass()]">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path v-if="getRevenueChange() >= 0" fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd" />
                  <path v-else fill-rule="evenodd" d="M12 13a1 1 0 100 2h5a1 1 0 001-1V9a1 1 0 10-2 0v2.586l-4.293-4.293a1 1 0 00-1.414 0L8 9.586 3.707 5.293a1 1 0 00-1.414 1.414l5 5a1 1 0 001.414 0L11 9.414 14.586 13H12z" clip-rule="evenodd" />
                </svg>
                <span>{{ Math.abs(getRevenueChange()) }}% vs last period</span>
              </div>
            </div>
          </div>

          <div class="summary-card payments">
            <div class="summary-icon">
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
              </svg>
            </div>
            <div class="summary-content">
              <div class="summary-label">Payment Success Rate</div>
              <div class="summary-value">{{ getPaymentSuccessRate() }}%</div>
              <div class="summary-change positive">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd" />
                </svg>
                <span>Completion rate</span>
              </div>
            </div>
          </div>

          <div class="summary-card average">
            <div class="summary-icon">
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
              </svg>
            </div>
            <div class="summary-content">
              <div class="summary-label">Avg Transaction Value</div>
              <div class="summary-value">{{ formatCurrency(getAvgTransactionValue()) }}</div>
              <div class="summary-change neutral">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd" />
                </svg>
                <span>Per transaction</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Reports Grid -->
        <div class="reports-grid">
          
          <!-- 1. Payment Statistics Report -->
          <div class="report-card">
            <div class="report-header">
              <h3>Payment Statistics</h3>
              <button @click="exportReport('payment_statistics')" class="btn btn-sm btn-secondary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3" />
                </svg>
              </button>
            </div>

            <!-- Payment Trends Chart -->
            <div class="chart-container">
              <h4>Payment Trends Over Time</h4>
              <canvas ref="paymentTrendsChart" width="400" height="250"></canvas>
            </div>

            <!-- Payment Method Breakdown -->
            <div class="chart-container">
              <h4>Payment Method Distribution</h4>
              <canvas ref="paymentMethodChart" width="400" height="300"></canvas>
            </div>

            <!-- Payment Statistics Grid -->
            <div class="stats-grid" v-if="paymentStatistics.total_payments">
              <div class="stat-card">
                <div class="stat-value">{{ paymentStatistics.total_payments || 0 }}</div>
                <div class="stat-label">Total Payments</div>
              </div>
              <div class="stat-card">
                <div class="stat-value">{{ paymentStatistics.completed_payments || 0 }}</div>
                <div class="stat-label">Completed</div>
              </div>
              <div class="stat-card">
                <div class="stat-value">{{ paymentStatistics.pending_payments || 0 }}</div>
                <div class="stat-label">Pending</div>
              </div>
              <div class="stat-card">
                <div class="stat-value">{{ paymentStatistics.failed_payments || 0 }}</div>
                <div class="stat-label">Failed</div>
              </div>
            </div>

            <!-- Payment Type Breakdown -->
            <div class="table-container" v-if="paymentStatistics.payment_type_breakdown && paymentStatistics.payment_type_breakdown.length > 0">
              <h4>Revenue by Payment Type</h4>
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Payment Type</th>
                    <th>Count</th>
                    <th>Total Revenue</th>
                    <th>Percentage</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="type in paymentStatistics.payment_type_breakdown" :key="type.payment_type">
                    <td>
                      <span class="type-badge">{{ formatPaymentType(type.payment_type) }}</span>
                    </td>
                    <td>{{ type.count }}</td>
                    <td class="revenue-value">{{ formatCurrency(type.total_revenue) }}</td>
                    <td>
                      <div class="progress-bar">
                        <div class="progress-fill" :style="{ width: calculatePercentage(type.total_revenue) + '%' }"></div>
                        <span class="progress-text">{{ calculatePercentage(type.total_revenue) }}%</span>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- 2. Service Utilization Report -->
          <div class="report-card">
            <div class="report-header">
              <h3>Service Utilization</h3>
              <button @click="exportReport('service_utilization')" class="btn btn-sm btn-secondary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3" />
                </svg>
              </button>
            </div>

            <!-- Most Requested Services Chart -->
            <div class="chart-container">
              <h4>Most Requested Services</h4>
              <canvas ref="servicesChart" width="400" height="300"></canvas>
            </div>

            <!-- Service Duration Analysis -->
            <div class="table-container" v-if="serviceUtilization.service_duration && serviceUtilization.service_duration.length > 0">
              <h4>Service Duration Analysis</h4>
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Session Type</th>
                    <th>Session Count</th>
                    <th>Total Hours</th>
                    <th>Avg Duration</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="duration in serviceUtilization.service_duration" :key="duration.session_type">
                    <td>
                      <span class="session-badge">{{ formatSessionType(duration.session_type) }}</span>
                    </td>
                    <td>{{ duration.session_count }}</td>
                    <td>{{ Math.round(parseFloat(duration.total_hours || 0)) }} hrs</td>
                    <td>{{ parseFloat(duration.avg_duration_hours || 0).toFixed(1) }} hrs</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Geographic Utilization -->
            <div class="table-container" v-if="serviceUtilization.geographic_utilization && serviceUtilization.geographic_utilization.length > 0">
              <h4>Top Service Locations</h4>
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Location</th>
                    <th>Request Count</th>
                    <th>Share</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="location in serviceUtilization.geographic_utilization" :key="location.pickup_location">
                    <td>
                      <div class="location-info">
                        <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <strong>{{ location.pickup_location }}</strong>
                      </div>
                    </td>
                    <td>{{ location.request_count }}</td>
                    <td>
                      <div class="progress-bar">
                        <div class="progress-fill" :style="{ width: calculateLocationShare(location.request_count) + '%' }"></div>
                        <span class="progress-text">{{ calculateLocationShare(location.request_count) }}%</span>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Peak Usage Times -->
            <div class="chart-container" v-if="serviceUtilization.peak_usage_times && serviceUtilization.peak_usage_times.length > 0">
              <h4>Peak Usage Times</h4>
              <canvas ref="peakTimesChart" width="400" height="250"></canvas>
            </div>
          </div>

          <!-- 3. Revenue Analytics Report -->
          <div class="report-card">
            <div class="report-header">
              <h3>Revenue Analytics</h3>
              <button @click="exportReport('revenue_analytics')" class="btn btn-sm btn-secondary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3" />
                </svg>
              </button>
            </div>

            <!-- Revenue Trends Chart -->
            <div class="chart-container">
              <h4>Revenue Trends by Payment Type</h4>
              <canvas ref="revenueTrendsChart" width="400" height="300"></canvas>
            </div>

            <!-- Monthly Comparison -->
            <div class="comparison-card" v-if="revenueAnalytics.monthly_comparison">
              <h4>Monthly Comparison</h4>
              <div class="comparison-content">
                <div class="comparison-item">
                  <div class="comparison-label">This Month</div>
                  <div class="comparison-value current">{{ formatCurrency(revenueAnalytics.monthly_comparison.this_month) }}</div>
                </div>
                <div class="comparison-arrow">
                  <svg v-if="revenueAnalytics.monthly_comparison.change_percentage >= 0" class="w-8 h-8 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                  </svg>
                  <svg v-else class="w-8 h-8 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                  </svg>
                  <span :class="revenueAnalytics.monthly_comparison.change_percentage >= 0 ? 'text-success' : 'text-danger'">
                    {{ Math.abs(revenueAnalytics.monthly_comparison.change_percentage) }}%
                  </span>
                </div>
                <div class="comparison-item">
                  <div class="comparison-label">Last Month</div>
                  <div class="comparison-value previous">{{ formatCurrency(revenueAnalytics.monthly_comparison.last_month) }}</div>
                </div>
              </div>
            </div>

            <!-- Payment Metrics -->
            <div class="metrics-grid" v-if="revenueAnalytics.payment_metrics">
              <div class="metric-card">
                <div class="metric-icon">
                  <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
                <div class="metric-content">
                  <div class="metric-label">Total Processed</div>
                  <div class="metric-value">{{ formatCurrency(revenueAnalytics.payment_metrics.total_processed) }}</div>
                </div>
              </div>
              <div class="metric-card">
                <div class="metric-icon">
                  <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                  </svg>
                </div>
                <div class="metric-content">
                  <div class="metric-label">Avg Transaction</div>
                  <div class="metric-value">{{ formatCurrency(revenueAnalytics.payment_metrics.avg_transaction_value) }}</div>
                </div>
              </div>
              <div class="metric-card">
                <div class="metric-icon">
                  <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
                <div class="metric-content">
                  <div class="metric-label">Transactions</div>
                  <div class="metric-value">{{ revenueAnalytics.payment_metrics.transaction_count }}</div>
                </div>
              </div>
              <div class="metric-card">
                <div class="metric-icon">
                  <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z" />
                  </svg>
                </div>
                <div class="metric-content">
                  <div class="metric-label">Tax Collected</div>
                  <div class="metric-value">{{ formatCurrency(revenueAnalytics.payment_metrics.total_tax_collected) }}</div>
                </div>
              </div>
            </div>

            <!-- Top Paying Patients -->
            <div class="table-container" v-if="revenueAnalytics.top_paying_patients && revenueAnalytics.top_paying_patients.length > 0">
              <h4>Top Paying Patients</h4>
              <div class="table-scroll">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>Patient</th>
                      <th>Email</th>
                      <th>Total Paid</th>
                      <th>Payments</th>
                      <th>Avg Payment</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="patient in revenueAnalytics.top_paying_patients" :key="patient.patient_id">
                      <td>
                        <div class="patient-info">
                          <strong>{{ patient.first_name }} {{ patient.last_name }}</strong>
                        </div>
                      </td>
                      <td>{{ patient.email }}</td>
                      <td class="revenue-value">{{ formatCurrency(patient.total_paid) }}</td>
                      <td>{{ patient.payment_count }}</td>
                      <td>{{ formatCurrency(parseFloat(patient.total_paid) / patient.payment_count) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Revenue by Care Type -->
            <div class="chart-container" v-if="revenueAnalytics.revenue_by_care_type && revenueAnalytics.revenue_by_care_type.length > 0">
              <h4>Revenue by Care Type</h4>
              <canvas ref="revenueByCareTypeChart" width="400" height="300"></canvas>
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
  getPaymentStatistics,
  getServiceUtilization,
  getRevenueAnalytics,
  exportFinancialReport,
  exportAllFinancialReports
} from '../../services/financeReportsService'

const toast = inject('toast')

// Reactive data
const loading = ref(false)

// Report data
const paymentStatistics = ref({})
const serviceUtilization = ref({})
const revenueAnalytics = ref({})

// Filters
const filters = ref({
  dateFrom: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
  dateTo: new Date().toISOString().split('T')[0],
  period: 'custom'
})

// Chart references
const paymentTrendsChart = ref(null)
const paymentMethodChart = ref(null)
const servicesChart = ref(null)
const peakTimesChart = ref(null)
const revenueTrendsChart = ref(null)
const revenueByCareTypeChart = ref(null)

// Chart instances
let paymentTrendsChartInstance = null
let paymentMethodChartInstance = null
let servicesChartInstance = null
let peakTimesChartInstance = null
let revenueTrendsChartInstance = null
let revenueByCareTypeChartInstance = null

// Computed values for summary cards
const getTotalRevenue = () => {
  return parseFloat(paymentStatistics.value.total_revenue || 0)
}

const getRevenueChange = () => {
  return parseFloat(paymentStatistics.value.revenue_change_percentage || 0)
}

const getRevenueChangeClass = () => {
  const change = getRevenueChange()
  if (change > 0) return 'positive'
  if (change < 0) return 'negative'
  return 'neutral'
}

const getPaymentSuccessRate = () => {
  return parseFloat(paymentStatistics.value.completion_rate || 0)
}

const getAvgTransactionValue = () => {
  return parseFloat(paymentStatistics.value.average_payment_amount || 0)
}

// Methods
const loadPaymentStatistics = async () => {
  try {
    const filterParams = {
      date_from: filters.value.dateFrom,
      date_to: filters.value.dateTo
    }
    
    const data = await getPaymentStatistics(filterParams)
    if (data) {
      paymentStatistics.value = data
      
      // Debug logs
      console.log('Payment Statistics Data:', data)
      console.log('Payment Trends:', data.payment_trends)
      console.log('Payment Method Breakdown:', data.payment_method_breakdown)
      
      await nextTick()
      renderPaymentTrendsChart()
      renderPaymentMethodChart()
    }
  } catch (error) {
    console.error('Error loading payment statistics:', error)
    toast.showError('Failed to load payment statistics')
  }
}

const loadServiceUtilization = async () => {
  try {
    const filterParams = {
      date_from: filters.value.dateFrom,
      date_to: filters.value.dateTo
    }
    
    const data = await getServiceUtilization(filterParams)
    if (data) {
      serviceUtilization.value = data
      
      // Debug logs
      console.log('Service Utilization Data:', data)
      console.log('Most Requested Services:', data.most_requested_services)
      
      await nextTick()
      renderServicesChart()
      renderPeakTimesChart()
    }
  } catch (error) {
    console.error('Error loading service utilization:', error)
    toast.showError('Failed to load service utilization')
  }
}

const loadRevenueAnalytics = async () => {
  try {
    const filterParams = {
      date_from: filters.value.dateFrom,
      date_to: filters.value.dateTo
    }
    
    const data = await getRevenueAnalytics(filterParams)
    if (data) {
      revenueAnalytics.value = data
      await nextTick()
      renderRevenueTrendsChart()
      renderRevenueByCareTypeChart()
    }
  } catch (error) {
    console.error('Error loading revenue analytics:', error)
    toast.showError('Failed to load revenue analytics')
  }
}

// Chart rendering methods
const renderPaymentTrendsChart = () => {
  if (!paymentTrendsChart.value) return
  
  if (paymentTrendsChartInstance) {
    paymentTrendsChartInstance.destroy()
  }
  
  const ctx = paymentTrendsChart.value.getContext('2d')
  const data = paymentStatistics.value.payment_trends || []
  
  if (data.length === 0) {
    ctx.fillStyle = '#6b7280'
    ctx.textAlign = 'center'
    ctx.fillText('No payment trend data available', ctx.canvas.width / 2, ctx.canvas.height / 2)
    return
  }
  
  paymentTrendsChartInstance = new Chart(ctx, {
    type: 'line',
    data: {
      labels: data.map(item => item.date),
      datasets: [
        {
          label: 'Payment Count',
          data: data.map(item => parseInt(item.count)),
          borderColor: '#3b82f6',
          backgroundColor: 'rgba(59, 130, 246, 0.1)',
          tension: 0.4,
          fill: true,
          yAxisID: 'y'
        },
        {
          label: 'Revenue',
          data: data.map(item => parseFloat(item.total)),
          borderColor: '#10b981',
          backgroundColor: 'rgba(16, 185, 129, 0.1)',
          tension: 0.4,
          fill: true,
          yAxisID: 'y1'
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: {
        mode: 'index',
        intersect: false
      },
      scales: {
        y: {
          type: 'linear',
          position: 'left',
          title: {
            display: true,
            text: 'Payment Count'
          }
        },
        y1: {
          type: 'linear',
          position: 'right',
          title: {
            display: true,
            text: 'Revenue (GHS)'
          },
          grid: {
            drawOnChartArea: false
          }
        }
      },
      plugins: {
        legend: {
          position: 'top'
        }
      }
    }
  })
}

const renderPaymentMethodChart = () => {
  if (!paymentMethodChart.value) return
  
  if (paymentMethodChartInstance) {
    paymentMethodChartInstance.destroy()
  }
  
  const ctx = paymentMethodChart.value.getContext('2d')
  const data = paymentStatistics.value.payment_method_breakdown || {}
  
  if (Object.keys(data).length === 0) {
    ctx.fillStyle = '#6b7280'
    ctx.textAlign = 'center'
    ctx.fillText('No payment method data available', ctx.canvas.width / 2, ctx.canvas.height / 2)
    return
  }
  
  const labels = Object.keys(data).map(key => formatPaymentMethod(key))
  const counts = Object.values(data).map(item => parseInt(item.count))
  
  paymentMethodChartInstance = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: labels,
      datasets: [{
        data: counts,
        backgroundColor: [
          '#3b82f6',
          '#10b981',
          '#f59e0b',
          '#ef4444',
          '#8b5cf6',
          '#ec4899'
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

const renderServicesChart = () => {
  if (!servicesChart.value) return
  
  if (servicesChartInstance) {
    servicesChartInstance.destroy()
  }
  
  const ctx = servicesChart.value.getContext('2d')
  const data = serviceUtilization.value.most_requested_services || []
  
  if (data.length === 0) {
    ctx.fillStyle = '#6b7280'
    ctx.textAlign = 'center'
    ctx.fillText('No service data available', ctx.canvas.width / 2, ctx.canvas.height / 2)
    return
  }
  
  servicesChartInstance = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: data.map(item => formatCareType(item.care_type)),
      datasets: [{
        label: 'Request Count',
        data: data.map(item => parseInt(item.request_count)),
        backgroundColor: '#3b82f6',
        borderRadius: 4
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      indexAxis: 'y',
      scales: {
        x: {
          beginAtZero: true
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

const renderPeakTimesChart = () => {
  if (!peakTimesChart.value) return
  
  if (peakTimesChartInstance) {
    peakTimesChartInstance.destroy()
  }
  
  const ctx = peakTimesChart.value.getContext('2d')
  const data = serviceUtilization.value.peak_usage_times || []
  
  if (data.length === 0) {
    ctx.fillStyle = '#6b7280'
    ctx.textAlign = 'center'
    ctx.fillText('No peak times data available', ctx.canvas.width / 2, ctx.canvas.height / 2)
    return
  }
  
  // Group by hour
  const hourMap = new Map()
  data.forEach(item => {
    const hour = parseInt(item.hour)
    if (!hourMap.has(hour)) {
      hourMap.set(hour, 0)
    }
    hourMap.set(hour, hourMap.get(hour) + parseInt(item.schedule_count))
  })
  
  const hours = Array.from(hourMap.keys()).sort((a, b) => a - b)
  const counts = hours.map(hour => hourMap.get(hour))
  
  peakTimesChartInstance = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: hours.map(h => `${h}:00`),
      datasets: [{
        label: 'Requests',
        data: counts,
        backgroundColor: '#10b981',
        borderRadius: 4
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          beginAtZero: true
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

const renderRevenueTrendsChart = () => {
  if (!revenueTrendsChart.value) return
  
  if (revenueTrendsChartInstance) {
    revenueTrendsChartInstance.destroy()
  }
  
  const ctx = revenueTrendsChart.value.getContext('2d')
  const data = revenueAnalytics.value.revenue_trends || []
  
  if (data.length === 0) {
    ctx.fillStyle = '#6b7280'
    ctx.textAlign = 'center'
    ctx.fillText('No revenue trend data available', ctx.canvas.width / 2, ctx.canvas.height / 2)
    return
  }
  
  // Group by date and payment type
  const dateMap = new Map()
  data.forEach(item => {
    if (!dateMap.has(item.date)) {
      dateMap.set(item.date, { assessment: 0, care: 0 })
    }
    const dateData = dateMap.get(item.date)
    if (item.payment_type === 'assessment_fee') {
      dateData.assessment += parseFloat(item.daily_revenue || 0)
    } else if (item.payment_type === 'care_fee') {
      dateData.care += parseFloat(item.daily_revenue || 0)
    }
  })
  
  const dates = Array.from(dateMap.keys()).sort()
  
  revenueTrendsChartInstance = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: dates,
      datasets: [
        {
          label: 'Assessment Fees',
          data: dates.map(date => dateMap.get(date).assessment),
          backgroundColor: 'rgba(59, 130, 246, 0.8)',
          borderColor: '#3b82f6',
          borderWidth: 1
        },
        {
          label: 'Care Fees',
          data: dates.map(date => dateMap.get(date).care),
          backgroundColor: 'rgba(16, 185, 129, 0.8)',
          borderColor: '#10b981',
          borderWidth: 1
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        x: {
          stacked: true
        },
        y: {
          stacked: true,
          beginAtZero: true
        }
      },
      plugins: {
        legend: {
          position: 'top'
        }
      }
    }
  })
}

const renderRevenueByCareTypeChart = () => {
  if (!revenueByCareTypeChart.value) return
  
  if (revenueByCareTypeChartInstance) {
    revenueByCareTypeChartInstance.destroy()
  }
  
  const ctx = revenueByCareTypeChart.value.getContext('2d')
  const data = revenueAnalytics.value.revenue_by_care_type || []
  
  if (data.length === 0) {
    ctx.fillStyle = '#6b7280'
    ctx.textAlign = 'center'
    ctx.fillText('No care type revenue data available', ctx.canvas.width / 2, ctx.canvas.height / 2)
    return
  }
  
  revenueByCareTypeChartInstance = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: data.map(item => formatCareType(item.care_type)),
      datasets: [{
        data: data.map(item => parseFloat(item.total_revenue)),
        backgroundColor: [
          '#3b82f6',
          '#10b981',
          '#f59e0b',
          '#ef4444',
          '#8b5cf6',
          '#ec4899',
          '#06b6d4',
          '#14b8a6'
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
      loadPaymentStatistics(),
      loadServiceUtilization(),
      loadRevenueAnalytics()
    ])
    toast.showSuccess('Financial reports refreshed successfully')
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
    
    const { blob, filename } = await exportFinancialReport(reportType, filterParams)
    
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
    
    const results = await exportAllFinancialReports(filterParams)
    
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
const formatCurrency = (amount) => {
  if (amount === null || amount === undefined) return 'GHS 0.00'
  const numAmount = parseFloat(amount)
  return `GHS ${numAmount.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')}`
}

const formatPaymentType = (type) => {
  const typeMap = {
    'assessment_fee': 'Assessment Fee',
    'care_fee': 'Care Fee'
  }
  return typeMap[type] || type
}

const formatPaymentMethod = (method) => {
  const methodMap = {
    'mobile_money': 'Mobile Money',
    'card': 'Card',
    'bank_transfer': 'Bank Transfer',
    'cash': 'Cash',
    'not_specified': 'Not Specified'
  }
  return methodMap[method] || method
}

const formatCareType = (type) => {
  return type
    .split('_')
    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ')
}

const formatSessionType = (type) => {
  return type
    .split('_')
    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ')
}

const calculatePercentage = (value) => {
  if (!paymentStatistics.value.payment_type_breakdown) return 0
  const total = paymentStatistics.value.payment_type_breakdown.reduce((sum, item) => sum + parseFloat(item.total_revenue || 0), 0)
  return total > 0 ? Math.round((parseFloat(value) / total) * 100) : 0
}

const calculateLocationShare = (count) => {
  if (!serviceUtilization.value.geographic_utilization) return 0
  const total = serviceUtilization.value.geographic_utilization.reduce((sum, item) => sum + parseInt(item.request_count), 0)
  return total > 0 ? Math.round((parseInt(count) / total) * 100) : 0
}

// Lifecycle
onMounted(() => {
  refreshAllReports()
})
</script>

<style scoped>
/* All styles remain exactly the same */
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

.financial-summary {
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

.summary-card.revenue {
  border-left: 4px solid #10b981;
}

.summary-card.payments {
  border-left: 4px solid #3b82f6;
}

.summary-card.average {
  border-left: 4px solid #f59e0b;
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

.summary-card.revenue .summary-icon {
  background: #d1fae5;
  color: #065f46;
}

.summary-card.payments .summary-icon {
  background: #dbeafe;
  color: #1e40af;
}

.summary-card.average .summary-icon {
  background: #fef3c7;
  color: #92400e;
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

.summary-change.negative {
  color: #ef4444;
}

.summary-change.neutral {
  color: #6b7280;
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
  border-bottom: 1px solid #e5e7eb;
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

.type-badge,
.session-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 500;
  background: #dbeafe;
  color: #1e40af;
}

.revenue-value {
  color: #10b981;
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

.location-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.patient-info strong {
  display: block;
  color: #1f2937;
  font-weight: 500;
}

.comparison-card {
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.comparison-card h4 {
  font-size: 1rem;
  font-weight: 600;
  color: #1f2937;
  margin: 0 0 1rem 0;
}

.comparison-content {
  display: flex;
  align-items: center;
  justify-content: space-around;
  gap: 2rem;
}

.comparison-item {
  text-align: center;
}

.comparison-label {
  font-size: 0.875rem;
  color: #6b7280;
  margin-bottom: 0.5rem;
}

.comparison-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1f2937;
}

.comparison-value.current {
  color: #10b981;
}

.comparison-value.previous {
  color: #6b7280;
}

.comparison-arrow {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
}

.comparison-arrow span {
  font-size: 1.25rem;
  font-weight: 700;
}

.text-success {
  color: #10b981;
}

.text-danger {
  color: #ef4444;
}

.metrics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.metric-card {
  background: #f8fafc;
  border-radius: 0.5rem;
  padding: 1rem;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.metric-icon {
  width: 40px;
  height: 40px;
  border-radius: 0.5rem;
  background: #dbeafe;
  color: #1e40af;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.metric-icon svg {
  width: 20px;
  height: 20px;
}

.metric-content {
  flex: 1;
}

.metric-label {
  font-size: 0.75rem;
  color: #6b7280;
  margin-bottom: 0.25rem;
}

.metric-value {
  font-size: 1.25rem;
  font-weight: 700;
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

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.625rem 1rem;
  border-radius: 0.5rem;
  font-weight: 500;
  font-size: 0.875rem;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-primary {
  background: #3b82f6;
  color: white;
}

.btn-primary:hover {
  background: #2563eb;
}

.btn-primary:disabled {
  background: #9ca3af;
  cursor: not-allowed;
}

.btn-secondary {
  background: white;
  color: #374151;
  border: 1px solid #d1d5db;
}

.btn-secondary:hover {
  background: #f9fafb;
}

.btn-sm {
  padding: 0.375rem 0.75rem;
  font-size: 0.8125rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.animate-spin {
  animation: spin 1s linear infinite;
}
</style>