<template>
  <MainLayout>
    <div class="reports-page">
      <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Page Header -->
        <div class="page-header">
          <div class="page-header-content">
            <h1>Financial & Billing Reports</h1>
            <p>Cost analysis, service utilization, and revenue analytics</p>
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
              <div class="summary-value">${{ formatCurrency(getTotalRevenue()) }}</div>
              <div class="summary-change positive">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd" />
                </svg>
                <span>+12.5% from last period</span>
              </div>
            </div>
          </div>

          <div class="summary-card costs">
            <div class="summary-icon">
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
              </svg>
            </div>
            <div class="summary-content">
              <div class="summary-label">Total Costs</div>
              <div class="summary-value">${{ formatCurrency(getTotalCosts()) }}</div>
              <div class="summary-change negative">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M12 13a1 1 0 100 2h5a1 1 0 001-1V9a1 1 0 10-2 0v2.586l-4.293-4.293a1 1 0 00-1.414 0L8 9.586 3.707 5.293a1 1 0 00-1.414 1.414l5 5a1 1 0 001.414 0L11 9.414 14.586 13H12z" clip-rule="evenodd" />
                </svg>
                <span>+8.3% from last period</span>
              </div>
            </div>
          </div>

          <div class="summary-card profit">
            <div class="summary-icon">
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
            </div>
            <div class="summary-content">
              <div class="summary-label">Net Profit</div>
              <div class="summary-value">${{ formatCurrency(getNetProfit()) }}</div>
              <div class="summary-change positive">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd" />
                </svg>
                <span>+18.2% from last period</span>
              </div>
            </div>
          </div>

          <div class="summary-card transactions">
            <div class="summary-icon">
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
              </svg>
            </div>
            <div class="summary-content">
              <div class="summary-label">Transactions</div>
              <div class="summary-value">{{ getTotalTransactions() }}</div>
              <div class="summary-change positive">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd" />
                </svg>
                <span>+15.8% from last period</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Reports Grid -->
        <div class="reports-grid">
          
          <!-- 1. Cost Analysis Report -->
          <div class="report-card">
            <div class="report-header">
              <h3>Cost Analysis Report</h3>
              <button @click="exportReport('cost_analysis')" class="btn btn-sm btn-secondary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3" />
                </svg>
              </button>
            </div>

            <!-- Cost Breakdown Chart -->
            <div class="chart-container">
              <h4>Cost Breakdown by Category</h4>
              <canvas ref="costBreakdownChart" width="400" height="300"></canvas>
            </div>

            <!-- Care Delivery Costs -->
            <div class="table-container" v-if="costAnalysis.care_delivery_costs">
              <h4>Care Delivery Costs by Nurse</h4>
              <div class="table-scroll">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>Nurse</th>
                      <th>Total Hours</th>
                      <th>Estimated Cost</th>
                      <th>Avg Cost/Hour</th>
                      <th>Cost Trend</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="nurse in costAnalysis.care_delivery_costs" :key="nurse.nurse_id">
                      <td>
                        <div class="nurse-info">
                          <strong>{{ nurse.nurse.first_name }} {{ nurse.nurse.last_name }}</strong>
                        </div>
                      </td>
                      <td>{{ Math.round(nurse.total_hours) }}h</td>
                      <td class="cost-value">${{ formatCurrency(nurse.estimated_cost_usd) }}</td>
                      <td>${{ formatCurrency(25) }}</td>
                      <td>
                        <span class="badge badge-info">Standard</span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Transportation Costs -->
            <div class="table-container" v-if="costAnalysis.transport_costs">
              <h4>Transportation Cost Summary</h4>
              <div class="cost-summary-grid">
                <div class="cost-summary-item">
                  <div class="cost-label">Total Transport Cost</div>
                  <div class="cost-amount">${{ formatCurrency(costAnalysis.transport_costs.total_transport_cost) }}</div>
                </div>
                <div class="cost-summary-item">
                  <div class="cost-label">Average Cost per Trip</div>
                  <div class="cost-amount">${{ formatCurrency(costAnalysis.transport_costs.avg_cost_per_trip) }}</div>
                </div>
                <div class="cost-summary-item">
                  <div class="cost-label">Total Distance</div>
                  <div class="cost-amount">{{ costAnalysis.transport_costs.total_distance }} km</div>
                </div>
                <div class="cost-summary-item">
                  <div class="cost-label">Total Trips</div>
                  <div class="cost-amount">{{ costAnalysis.transport_costs.total_trips }}</div>
                </div>
              </div>
            </div>

            <!-- Resource Utilization -->
            <div class="table-container" v-if="costAnalysis.resource_utilization">
              <h4>Resource Utilization by Care Type</h4>
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Care Type</th>
                    <th>Total Plans</th>
                    <th>Avg Duration (Days)</th>
                    <th>Utilization Rate</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="resource in costAnalysis.resource_utilization" :key="resource.care_type">
                    <td>
                      <span class="care-type-badge">{{ formatCareType(resource.care_type) }}</span>
                    </td>
                    <td>{{ resource.total_plans }}</td>
                    <td>{{ Math.round(resource.avg_duration_days) }} days</td>
                    <td>
                      <div class="progress-bar">
                        <div class="progress-fill" :style="{ width: calculateUtilization(resource.total_plans) + '%' }"></div>
                        <span class="progress-text">{{ calculateUtilization(resource.total_plans) }}%</span>
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
              <h3>Service Utilization Report</h3>
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

            <!-- Peak Usage Times -->
            <div class="chart-container">
              <h4>Peak Usage Times (By Hour)</h4>
              <canvas ref="peakTimesChart" width="400" height="250"></canvas>
            </div>

            <!-- Service Duration Analysis -->
            <div class="table-container" v-if="serviceUtilization.service_duration">
              <h4>Service Duration Analysis</h4>
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Session Type</th>
                    <th>Session Count</th>
                    <th>Total Hours</th>
                    <th>Avg Duration</th>
                    <th>Efficiency</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="service in serviceUtilization.service_duration" :key="service.session_type">
                    <td>
                      <span class="service-badge">{{ formatSessionType(service.session_type) }}</span>
                    </td>
                    <td>{{ service.session_count }}</td>
                    <td>{{ Math.round(service.total_hours) }}h</td>
                    <td>{{ Math.round(service.avg_duration_hours * 10) / 10 }}h</td>
                    <td>
                      <span :class="getEfficiencyBadge(service.avg_duration_hours)">
                        {{ getEfficiencyStatus(service.avg_duration_hours) }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Geographic Utilization -->
            <div class="table-container" v-if="serviceUtilization.geographic_utilization">
              <h4>Top Service Locations</h4>
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Location</th>
                    <th>Request Count</th>
                    <th>Percentage</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="location in serviceUtilization.geographic_utilization" :key="location.pickup_location">
                    <td>
                      <div class="location-info">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <strong>{{ location.pickup_location }}</strong>
                      </div>
                    </td>
                    <td>{{ location.request_count }}</td>
                    <td>
                      <div class="progress-bar">
                        <div class="progress-fill" :style="{ width: calculateLocationPercentage(location.request_count) + '%' }"></div>
                        <span class="progress-text">{{ calculateLocationPercentage(location.request_count) }}%</span>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- 3. Revenue Analytics -->
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
              <h4>Revenue Trends Over Time</h4>
              <canvas ref="revenueTrendsChart" width="400" height="250"></canvas>
            </div>

            <!-- Payment Metrics -->
            <div class="stats-grid" v-if="revenueAnalytics.payment_metrics">
              <div class="stat-card">
                <div class="stat-value">${{ formatCurrency(revenueAnalytics.payment_metrics.total_processed) }}</div>
                <div class="stat-label">Total Processed</div>
              </div>
              <div class="stat-card">
                <div class="stat-value">${{ formatCurrency(revenueAnalytics.payment_metrics.avg_transaction_value) }}</div>
                <div class="stat-label">Avg Transaction</div>
              </div>
              <div class="stat-card">
                <div class="stat-value">{{ revenueAnalytics.payment_metrics.transaction_count }}</div>
                <div class="stat-label">Transactions</div>
              </div>
            </div>

            <!-- Service Revenue Breakdown -->
            <div class="chart-container">
              <h4>Revenue by Service Type</h4>
              <canvas ref="serviceRevenueChart" width="400" height="300"></canvas>
            </div>

            <!-- Outstanding Balances -->
            <div class="table-container" v-if="revenueAnalytics.outstanding_balances && revenueAnalytics.outstanding_balances.length > 0">
              <h4>Outstanding Balances (Top 10)</h4>
              <div class="table-scroll">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>Patient ID</th>
                      <th>Estimated Amount Due</th>
                      <th>Status</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="balance in revenueAnalytics.outstanding_balances.slice(0, 10)" :key="balance.patient_id">
                      <td>#{{ balance.patient_id }}</td>
                      <td class="cost-value">${{ formatCurrency(balance.estimated_amount_due) }}</td>
                      <td>
                        <span :class="getBalanceStatusBadge(balance.estimated_amount_due)">
                          {{ getBalanceStatus(balance.estimated_amount_due) }}
                        </span>
                      </td>
                      <td>
                        <button @click="sendPaymentReminder(balance.patient_id)" class="btn btn-sm btn-secondary">
                          Send Reminder
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Revenue Performance Indicators -->
            <div class="performance-indicators">
              <div class="indicator-card">
                <div class="indicator-icon revenue-icon">
                  <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                  </svg>
                </div>
                <div class="indicator-content">
                  <div class="indicator-label">Revenue Growth Rate</div>
                  <div class="indicator-value positive">+12.5%</div>
                  <div class="indicator-description">Compared to previous period</div>
                </div>
              </div>

              <div class="indicator-card">
                <div class="indicator-icon collection-icon">
                  <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
                <div class="indicator-content">
                  <div class="indicator-label">Collection Rate</div>
                  <div class="indicator-value">92.3%</div>
                  <div class="indicator-description">Of total receivables</div>
                </div>
              </div>

              <div class="indicator-card">
                <div class="indicator-icon profit-icon">
                  <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
                <div class="indicator-content">
                  <div class="indicator-label">Profit Margin</div>
                  <div class="indicator-value positive">28.7%</div>
                  <div class="indicator-description">Net profit percentage</div>
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
import { ref, computed, onMounted, nextTick, inject } from 'vue'
import MainLayout from '../../layout/MainLayout.vue'
import Toast from '../../common/components/Toast.vue'
import Chart from 'chart.js/auto'

const toast = inject('toast')

// Reactive data
const loading = ref(false)

// Report data
const costAnalysis = ref({})
const serviceUtilization = ref({})
const revenueAnalytics = ref({})

// Filters
const filters = ref({
  dateFrom: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
  dateTo: new Date().toISOString().split('T')[0],
  period: 'custom'
})

// Chart references
const costBreakdownChart = ref(null)
const servicesChart = ref(null)
const peakTimesChart = ref(null)
const revenueTrendsChart = ref(null)
const serviceRevenueChart = ref(null)

// Chart instances
let costBreakdownChartInstance = null
let servicesChartInstance = null
let peakTimesChartInstance = null
let revenueTrendsChartInstance = null
let serviceRevenueChartInstance = null

// Computed properties for summary cards
const getTotalRevenue = () => {
  let total = 0
  
  if (revenueAnalytics.value.payment_metrics) {
    total += revenueAnalytics.value.payment_metrics.total_processed || 0
  }
  
  if (revenueAnalytics.value.service_revenue) {
    const serviceTotal = revenueAnalytics.value.service_revenue.reduce((sum, item) => 
      sum + (parseFloat(item.estimated_revenue) || 0), 0
    )
    total += serviceTotal
  }
  
  return total
}

const getTotalCosts = () => {
  let total = 0
  
  if (costAnalysis.value.care_delivery_costs) {
    total += costAnalysis.value.care_delivery_costs.reduce((sum, item) => 
      sum + (parseFloat(item.estimated_cost_usd) || 0), 0
    )
  }
  
  if (costAnalysis.value.transport_costs) {
    total += parseFloat(costAnalysis.value.transport_costs.total_transport_cost) || 0
  }
  
  return total
}

const getNetProfit = () => {
  return getTotalRevenue() - getTotalCosts()
}

const getTotalTransactions = () => {
  return revenueAnalytics.value.payment_metrics?.transaction_count || 0
}

// Methods
const loadCostAnalysis = async () => {
  try {
    const params = new URLSearchParams({
      date_from: filters.value.dateFrom,
      date_to: filters.value.dateTo
    })
    
    const response = await fetch(`/api/reports/cost-analysis?${params}`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Content-Type': 'application/json'
      }
    })
    
    if (response.ok) {
      costAnalysis.value = await response.json()
      await nextTick()
      renderCostBreakdownChart()
    }
  } catch (error) {
    console.error('Error loading cost analysis:', error)
    toast.showError('Failed to load cost analysis')
  }
}

const loadServiceUtilization = async () => {
  try {
    const params = new URLSearchParams({
      date_from: filters.value.dateFrom,
      date_to: filters.value.dateTo
    })
    
    const response = await fetch(`/api/reports/service-utilization?${params}`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Content-Type': 'application/json'
      }
    })
    
    if (response.ok) {
      serviceUtilization.value = await response.json()
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
    const params = new URLSearchParams({
      date_from: filters.value.dateFrom,
      date_to: filters.value.dateTo
    })
    
    const response = await fetch(`/api/reports/revenue-analytics?${params}`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Content-Type': 'application/json'
      }
    })
    
    if (response.ok) {
      revenueAnalytics.value = await response.json()
      await nextTick()
      renderRevenueTrendsChart()
      renderServiceRevenueChart()
    }
  } catch (error) {
    console.error('Error loading revenue analytics:', error)
    toast.showError('Failed to load revenue analytics')
  }
}

// Chart rendering methods
const renderCostBreakdownChart = () => {
  if (!costBreakdownChart.value) return
  
  if (costBreakdownChartInstance) {
    costBreakdownChartInstance.destroy()
  }
  
  const ctx = costBreakdownChart.value.getContext('2d')
  
  const careDeliveryCost = costAnalysis.value.care_delivery_costs?.reduce((sum, item) => 
    sum + (parseFloat(item.estimated_cost_usd) || 0), 0
  ) || 0
  
  const transportCost = parseFloat(costAnalysis.value.transport_costs?.total_transport_cost) || 0
  
  costBreakdownChartInstance = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: ['Care Delivery', 'Transportation', 'Other Expenses'],
      datasets: [{
        data: [careDeliveryCost, transportCost, careDeliveryCost * 0.15],
        backgroundColor: ['#3b82f6', '#10b981', '#f59e0b']
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
  if (!servicesChart.value || !serviceUtilization.value.most_requested_services) return
  
  if (servicesChartInstance) {
    servicesChartInstance.destroy()
  }
  
  const ctx = servicesChart.value.getContext('2d')
  const data = serviceUtilization.value.most_requested_services
  
  servicesChartInstance = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: data.map(item => formatCareType(item.care_type)),
      datasets: [{
        label: 'Request Count',
        data: data.map(item => item.request_count),
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
          beginAtZero: true
        }
      }
    }
  })
}

const renderPeakTimesChart = () => {
  if (!peakTimesChart.value || !serviceUtilization.value.peak_usage_times) return
  
  if (peakTimesChartInstance) {
    peakTimesChartInstance.destroy()
  }
  
  const ctx = peakTimesChart.value.getContext('2d')
  const data = serviceUtilization.value.peak_usage_times
  
  // Group by hour
  const hourlyData = {}
  data.forEach(item => {
    if (!hourlyData[item.hour]) {
      hourlyData[item.hour] = 0
    }
    hourlyData[item.hour] += item.schedule_count
  })
  
  const hours = Object.keys(hourlyData).sort((a, b) => a - b)
  const counts = hours.map(hour => hourlyData[hour])
  
  peakTimesChartInstance = new Chart(ctx, {
    type: 'line',
    data: {
      labels: hours.map(hour => `${hour}:00`),
      datasets: [{
        label: 'Schedule Count',
        data: counts,
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
          beginAtZero: true
        }
      }
    }
  })
}

const renderRevenueTrendsChart = () => {
  if (!revenueTrendsChart.value || !revenueAnalytics.value.transport_revenue) return
  
  if (revenueTrendsChartInstance) {
    revenueTrendsChartInstance.destroy()
  }
  
  const ctx = revenueTrendsChart.value.getContext('2d')
  const data = revenueAnalytics.value.transport_revenue
  
  // Group by date
  const dailyRevenue = {}
  data.forEach(item => {
    if (!dailyRevenue[item.date]) {
      dailyRevenue[item.date] = 0
    }
    dailyRevenue[item.date] += parseFloat(item.daily_revenue) || 0
  })
  
  const dates = Object.keys(dailyRevenue).sort()
  const revenues = dates.map(date => dailyRevenue[date])
  
  revenueTrendsChartInstance = new Chart(ctx, {
    type: 'line',
    data: {
      labels: dates,
      datasets: [{
        label: 'Daily Revenue',
        data: revenues,
        borderColor: '#10b981',
        backgroundColor: 'rgba(16, 185, 129, 0.1)',
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
          beginAtZero: true
        }
      }
    }
  })
}

const renderServiceRevenueChart = () => {
  if (!serviceRevenueChart.value || !revenueAnalytics.value.service_revenue) return
  
  if (serviceRevenueChartInstance) {
    serviceRevenueChartInstance.destroy()
  }
  
  const ctx = serviceRevenueChart.value.getContext('2d')
  const data = revenueAnalytics.value.service_revenue
  
  serviceRevenueChartInstance = new Chart(ctx, {
    type: 'pie',
    data: {
      labels: data.map(item => formatCareType(item.care_type)),
      datasets: [{
        data: data.map(item => parseFloat(item.estimated_revenue) || 0),
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
      return // custom range, don't change dates
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
      loadCostAnalysis(),
      loadServiceUtilization(),
      loadRevenueAnalytics()
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
    const params = new URLSearchParams({
      report_type: reportType,
      format: 'csv',
      date_from: filters.value.dateFrom,
      date_to: filters.value.dateTo
    })
    
    const response = await fetch(`/api/reports/export?${params}`, {
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
      
      toast.showSuccess('Report exported successfully')
    } else {
      throw new Error('Export failed')
    }
  } catch (error) {
    console.error('Error exporting report:', error)
    toast.showError('Failed to export report')
  }
}

const exportAllReports = () => {
  const reports = ['cost_analysis', 'service_utilization', 'revenue_analytics']
  
  reports.forEach((reportType, index) => {
    setTimeout(() => exportReport(reportType), index * 1000)
  })
}

const sendPaymentReminder = async (patientId) => {
  try {
    const response = await fetch(`/api/patients/${patientId}/send-payment-reminder`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Content-Type': 'application/json'
      }
    })
    
    if (response.ok) {
      toast.showSuccess('Payment reminder sent successfully')
    } else {
      throw new Error('Failed to send reminder')
    }
  } catch (error) {
    console.error('Error sending payment reminder:', error)
    toast.showError('Failed to send payment reminder')
  }
}

// Utility functions
const formatCurrency = (amount) => {
  if (!amount) return '0.00'
  return parseFloat(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')
}

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

const calculateUtilization = (totalPlans) => {
  const max = Math.max(...(costAnalysis.value.resource_utilization?.map(r => r.total_plans) || [1]))
  return Math.round((totalPlans / max) * 100)
}

const calculateLocationPercentage = (requestCount) => {
  const total = serviceUtilization.value.geographic_utilization?.reduce((sum, item) => 
    sum + item.request_count, 0
  ) || 1
  return Math.round((requestCount / total) * 100)
}

const getEfficiencyBadge = (avgDuration) => {
  if (avgDuration <= 4) return 'badge badge-success'
  if (avgDuration <= 6) return 'badge badge-warning'
  return 'badge badge-danger'
}

const getEfficiencyStatus = (avgDuration) => {
  if (avgDuration <= 4) return 'Efficient'
  if (avgDuration <= 6) return 'Normal'
  return 'Needs Review'
}

const getBalanceStatusBadge = (amount) => {
  if (amount < 500) return 'badge badge-success'
  if (amount < 1000) return 'badge badge-warning'
  return 'badge badge-danger'
}

const getBalanceStatus = (amount) => {
  if (amount < 500) return 'Low Priority'
  if (amount < 1000) return 'Medium Priority'
  return 'High Priority'
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

.summary-card.costs {
  border-left: 4px solid #ef4444;
}

.summary-card.profit {
  border-left: 4px solid #3b82f6;
}

.summary-card.transactions {
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

.summary-card.costs .summary-icon {
  background: #fee2e2;
  color: #991b1b;
}

.summary-card.profit .summary-icon {
  background: #dbeafe;
  color: #1e40af;
}

.summary-card.transactions .summary-icon {
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

.cost-value {
  color: #1f2937;
  font-weight: 600;
  font-family: 'Courier New', monospace;
}

.cost-summary-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.cost-summary-item {
  background: #f9fafb;
  padding: 1rem;
  border-radius: 0.5rem;
  text-align: center;
}

.cost-label {
  font-size: 0.875rem;
  color: #6b7280;
  margin-bottom: 0.5rem;
}

.cost-amount {
  font-size: 1.25rem;
  font-weight: 700;
  color: #1f2937;
}

.care-type-badge,
.service-badge {
  background: #e0f2fe;
  color: #0369a1;
  padding: 0.25rem 0.5rem;
  border-radius: 0.375rem;
  font-size: 0.8125rem;
  font-weight: 500;
  display: inline-block;
}

.location-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.location-info svg {
  color: #6b7280;
  flex-shrink: 0;
}

.nurse-info strong {
  display: block;
  color: #1f2937;
  font-weight: 500;
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

.performance-indicators {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  padding: 1.5rem;
}

.indicator-card {
  display: flex;
  gap: 1rem;
  padding: 1.5rem;
  background: #f9fafb;
  border-radius: 0.5rem;
  border: 1px solid #e5e7eb;
}

.indicator-icon {
  width: 48px;
  height: 48px;
  border-radius: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.revenue-icon {
  background: #d1fae5;
  color: #065f46;
}

.collection-icon {
  background: #dbeafe;
  color: #1e40af;
}

.profit-icon {
  background: #fef3c7;
  color: #92400e;
}

.indicator-icon svg {
  width: 24px;
  height: 24px;
}

.indicator-content {
  flex: 1;
}

.indicator-label {
  font-size: 0.875rem;
  color: #6b7280;
  font-weight: 500;
  margin-bottom: 0.5rem;
}

.indicator-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1f2937;
  margin-bottom: 0.25rem;
}

.indicator-value.positive {
  color: #10b981;
}

.indicator-description {
  font-size: 0.75rem;
  color: #6b7280;
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