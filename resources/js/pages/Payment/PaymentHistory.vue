<template>
  <MainLayout>
    <div class="payments-page">
      <!-- Page Header -->
      <div class="page-header">
        <div class="page-header-content">
          <h1>Payment History</h1>
          <p>Track and manage all payment transactions</p>
        </div>
        <div class="page-header-actions">
          <button @click="exportPayments" class="btn-modern btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Export
          </button>
          <button @click="loadPayments(1)" class="btn-modern btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Refresh
          </button>
        </div>
      </div>

      <!-- Stats Grid -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon blue">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M3 3h18a1 1 0 011 1v16a1 1 0 01-1 1H3a1 1 0 01-1-1V4a1 1 0 011-1zm9 12v2H6v-2h6zm6-8v6H6V7h12z"/>
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Total Payments</div>
            <div class="stat-value">{{ statistics.total_payments || 0 }}</div>
            <div class="stat-change neutral">
              {{ statistics.completion_rate || 0 }}% completion rate
            </div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon green">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Completed</div>
            <div class="stat-value">{{ statistics.completed_payments || 0 }}</div>
            <div class="stat-change positive">Successfully processed</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon purple">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Total Revenue</div>
            <div class="stat-value">{{ formatCurrency(statistics.total_revenue) }}</div>
            <div :class="['stat-change', getRevenueChangeClass()]">
              {{ formatRevenueChange() }}
            </div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon yellow">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Pending</div>
            <div class="stat-value">{{ statistics.pending_payments || 0 }}</div>
            <div class="stat-change neutral">Awaiting processing</div>
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
            placeholder="Search by reference, patient name, phone, email..."
            v-model="searchQuery"
            class="search-input"
          />
        </div>
        <div class="filters-group">
          <select v-model="statusFilter" class="filter-select">
            <option value="all">All Status</option>
            <option value="pending">Pending</option>
            <option value="processing">Processing</option>
            <option value="completed">Completed</option>
            <option value="failed">Failed</option>
            <option value="refunded">Refunded</option>
          </select>
          
          <select v-model="paymentTypeFilter" class="filter-select">
            <option value="all">All Types</option>
            <option value="assessment_fee">Assessment Fee</option>
            <option value="care_fee">Care Fee</option>
          </select>

          <input
            type="date"
            v-model="startDateFilter"
            class="filter-select"
            placeholder="Start Date"
          />

          <input
            type="date"
            v-model="endDateFilter"
            class="filter-select"
            placeholder="End Date"
          />
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="loading-state">
        <div class="loading-spinner"></div>
        <p class="loading-text">Loading payments...</p>
      </div>

      <!-- Payments Table -->
      <div v-else-if="!loading" class="payments-table-container">
        <div v-if="payments.data && payments.data.length > 0" class="table-wrapper">
          <table class="modern-table">
            <thead>
              <tr>
                <th>Reference</th>
                <th>Patient</th>
                <th>Payment Type</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Payment Method</th>
                <th>Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="payment in payments.data" :key="payment.id">
                <td>
                  <div class="reference-cell">
                    <div class="reference-primary">{{ payment.reference_number }}</div>
                    <div class="reference-secondary" v-if="payment.transaction_id">
                      TXN: {{ payment.transaction_id }}
                    </div>
                  </div>
                </td>
                
                <td>
                  <div class="patient-cell">
                    <div class="patient-name">
                      {{ payment.patient ? `${payment.patient.first_name} ${payment.patient.last_name}` : 'N/A' }}
                    </div>
                    <div class="patient-contact">
                      {{ payment.patient ? payment.patient.phone : '' }}
                    </div>
                  </div>
                </td>
                
                <td>
                  <span class="modern-badge badge-info">
                    {{ formatPaymentType(payment.payment_type) }}
                  </span>
                </td>
                
                <td>
                  <div class="amount-cell">
                    <div class="amount-primary">{{ payment.currency }} {{ formatAmount(payment.total_amount) }}</div>
                    <div class="amount-secondary">
                      Base: {{ formatAmount(payment.amount) }} + Tax: {{ formatAmount(payment.tax_amount) }}
                    </div>
                  </div>
                </td>
                
                <td>
                  <span class="modern-badge" :class="getStatusBadgeClass(payment.status)">
                    {{ capitalizeFirst(payment.status) }}
                  </span>
                </td>
                
                <td>
                  <span class="payment-method-badge">
                    {{ formatPaymentMethod(payment.payment_method) }}
                  </span>
                </td>
                
                <td>
                  <div class="date-cell">
                    <div class="date-primary">{{ formatDate(payment.created_at) }}</div>
                    <div class="date-secondary" v-if="payment.paid_at">
                      Paid: {{ formatDate(payment.paid_at) }}
                    </div>
                  </div>
                </td>
                
                <td>
                  <div class="action-cell">
                    <button @click="toggleDropdown(payment.id)" class="action-btn">
                      <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                      </svg>
                    </button>
                    
                    <div v-show="activeDropdown === payment.id" class="modern-dropdown">
                      <button @click="openViewModal(payment)" class="dropdown-item-modern">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        View Details
                      </button>
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>

          <!-- Pagination -->
          <div v-if="payments.last_page > 1" class="pagination-container">
            <div class="pagination-info">
              Showing {{ (payments.current_page - 1) * payments.per_page + 1 }} to {{ Math.min(payments.current_page * payments.per_page, payments.total) }} of {{ payments.total }} payments
            </div>
            <div class="pagination-controls">
              <button 
                @click="prevPage" 
                :disabled="payments.current_page === 1"
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
                  :class="['pagination-page', { active: page === payments.current_page }]"
                >
                  {{ page }}
                </button>
              </div>
              
              <button 
                @click="nextPage" 
                :disabled="payments.current_page === payments.last_page"
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
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
          </svg>
          <h3>No payments found</h3>
          <p>
            {{ hasActiveFilters ? 'Try adjusting your search or filters.' : 'No payment transactions yet.' }}
          </p>
        </div>
      </div>

      <!-- View Payment Modal -->
      <div v-if="showViewModal && currentPayment" class="modal-overlay" @click.self="closeViewModal">
        <div class="modal modal-xl">
          <div class="modal-header">
            <h2 class="modal-title">Payment Details</h2>
            <button @click="closeViewModal" class="modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="modal-body">
            <div class="payment-view-grid">
              <!-- Payment Overview -->
              <div class="payment-overview-section">
                <div class="overview-header">
                  <div class="payment-status-large" :class="getStatusBadgeClass(currentPayment.status)">
                    {{ capitalizeFirst(currentPayment.status) }}
                  </div>
                  <div class="payment-amount-large">
                    {{ currentPayment.currency }} {{ formatAmount(currentPayment.total_amount) }}
                  </div>
                </div>

                <div class="overview-details">
                  <div class="detail-row">
                    <span class="detail-label">Reference Number</span>
                    <span class="detail-value">{{ currentPayment.reference_number }}</span>
                  </div>
                  
                  <div class="detail-row" v-if="currentPayment.transaction_id">
                    <span class="detail-label">Transaction ID</span>
                    <span class="detail-value">{{ currentPayment.transaction_id }}</span>
                  </div>
                  
                  <div class="detail-row">
                    <span class="detail-label">Payment Type</span>
                    <span class="detail-value">{{ formatPaymentType(currentPayment.payment_type) }}</span>
                  </div>

                  <div class="detail-row">
                    <span class="detail-label">Payment Method</span>
                    <span class="detail-value">{{ formatPaymentMethod(currentPayment.payment_method) }}</span>
                  </div>

                  <div class="detail-row" v-if="currentPayment.payment_provider">
                    <span class="detail-label">Payment Provider</span>
                    <span class="detail-value">{{ capitalizeFirst(currentPayment.payment_provider) }}</span>
                  </div>
                </div>
              </div>

              <!-- Detailed Information -->
              <div class="details-section-view">
                <!-- Amount Breakdown -->
                <div class="details-group">
                  <h4 class="details-header">Amount Breakdown</h4>
                  <div class="amount-breakdown">
                    <div class="breakdown-row">
                      <span>Base Amount</span>
                      <span class="breakdown-value">{{ currentPayment.currency }} {{ formatAmount(currentPayment.amount) }}</span>
                    </div>
                    <div class="breakdown-row">
                      <span>Tax Amount</span>
                      <span class="breakdown-value">{{ currentPayment.currency }} {{ formatAmount(currentPayment.tax_amount) }}</span>
                    </div>
                    <div class="breakdown-row total-row">
                      <span>Total Amount</span>
                      <span class="breakdown-value">{{ currentPayment.currency }} {{ formatAmount(currentPayment.total_amount) }}</span>
                    </div>
                  </div>
                </div>

                <!-- Patient Information -->
                <div class="details-group" v-if="currentPayment.patient">
                  <h4 class="details-header">Patient Information</h4>
                  <div class="patient-info-grid">
                    <div class="info-item">
                      <span class="info-label">Name</span>
                      <span class="info-value">{{ currentPayment.patient.first_name }} {{ currentPayment.patient.last_name }}</span>
                    </div>
                    <div class="info-item">
                      <span class="info-label">Phone</span>
                      <span class="info-value">{{ currentPayment.patient.phone }}</span>
                    </div>
                    <div class="info-item email-column" v-if="currentPayment.patient.email">
                      <span class="info-label">Email</span>
                      <span class="info-value">{{ currentPayment.patient.email }}</span>
                    </div>
                  </div>
                </div>

                <!-- Payment Timeline -->
                <div class="details-group">
                  <h4 class="details-header">Payment Timeline</h4>
                  <div class="timeline">
                    <div class="timeline-item">
                      <div class="timeline-marker"></div>
                      <div class="timeline-content">
                        <div class="timeline-label">Created</div>
                        <div class="timeline-value">{{ formatDateTime(currentPayment.created_at) }}</div>
                      </div>
                    </div>
                    
                    <div class="timeline-item" v-if="currentPayment.paid_at">
                      <div class="timeline-marker completed"></div>
                      <div class="timeline-content">
                        <div class="timeline-label">Paid</div>
                        <div class="timeline-value">{{ formatDateTime(currentPayment.paid_at) }}</div>
                      </div>
                    </div>

                    <div class="timeline-item" v-if="currentPayment.refunded_at">
                      <div class="timeline-marker refunded"></div>
                      <div class="timeline-content">
                        <div class="timeline-label">Refunded</div>
                        <div class="timeline-value">{{ formatDateTime(currentPayment.refunded_at) }}</div>
                      </div>
                    </div>

                    <div class="timeline-item" v-if="currentPayment.expires_at && !currentPayment.paid_at">
                      <div class="timeline-marker warning"></div>
                      <div class="timeline-content">
                        <div class="timeline-label">Expires</div>
                        <div class="timeline-value">{{ formatDateTime(currentPayment.expires_at) }}</div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Description -->
                <div class="details-group" v-if="currentPayment.description">
                  <h4 class="details-header">Description</h4>
                  <p class="description-text">{{ currentPayment.description }}</p>
                </div>

                <!-- Failure Reason -->
                <div class="details-group" v-if="currentPayment.failure_reason">
                  <h4 class="details-header">Failure Reason</h4>
                  <div class="failure-reason">
                    {{ currentPayment.failure_reason }}
                  </div>
                </div>

                <!-- Admin Info -->
                <div class="details-group" v-if="isAdminCreated(currentPayment)">
                  <h4 class="details-header">Administrative Information</h4>
                  <div class="admin-info">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                      <p><strong>Admin Created:</strong> This payment was recorded by an administrator.</p>
                      <p v-if="getAdminCreator(currentPayment)">
                        <strong>Created by:</strong> {{ getAdminCreator(currentPayment) }}
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="modal-actions">
            <button @click="closeViewModal" class="btn btn-secondary">Close</button>
          </div>
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
import * as paymentService from '../../services/paymentHistoryService'

const toast = inject('toast')

// Reactive data
const payments = ref({ data: [], total: 0, per_page: 15, current_page: 1, last_page: 1 })
const statistics = ref({})
const loading = ref(true)
const searchQuery = ref('')
const statusFilter = ref('all')
const paymentTypeFilter = ref('all')
const startDateFilter = ref('')
const endDateFilter = ref('')

// Modal states
const showViewModal = ref(false)
const currentPayment = ref(null)

// Dropdown state
const activeDropdown = ref(null)

// Computed properties
const hasActiveFilters = computed(() => {
  return searchQuery.value || statusFilter.value !== 'all' ||
    paymentTypeFilter.value !== 'all' || startDateFilter.value || endDateFilter.value
})

// Methods
const loadPayments = async (page = 1) => {
  loading.value = true
  try {
    const params = {
      page: page,
      per_page: 15,
      search: searchQuery.value || undefined,
      status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
      payment_type: paymentTypeFilter.value !== 'all' ? paymentTypeFilter.value : undefined,
      start_date: startDateFilter.value || undefined,
      end_date: endDateFilter.value || undefined
    }
    
    Object.keys(params).forEach(key => params[key] === undefined && delete params[key])
    
    const response = await paymentService.getPayments(params)
    
    if (response.success && response.data) {
      if (response.pagination) {
        payments.value = {
          data: response.data || [],
          total: response.pagination.total || 0,
          current_page: response.pagination.current_page || 1,
          last_page: response.pagination.last_page || 1,
          per_page: response.pagination.per_page || 15
        }
      } else {
        payments.value = {
          data: response.data || [],
          total: 0,
          current_page: 1,
          last_page: 1,
          per_page: 15
        }
      }
    }
  } catch (error) {
    console.error('Error loading payments:', error)
    toast.showError('Failed to load payments')
    payments.value = { data: [], total: 0, current_page: 1, last_page: 1, per_page: 15 }
  }
  loading.value = false
}

const getStatistics = async () => {
  try {
    const response = await paymentService.getStatistics()
    
    if (response.success && response.data) {
      statistics.value = response.data
    } else {
      statistics.value = response.data || {}
    }
  } catch (error) {
    console.error('Error loading statistics:', error)
    statistics.value = {}
  }
}

const formatAmount = (amount) => {
  return parseFloat(amount).toLocaleString('en-US', { 
    minimumFractionDigits: 2, 
    maximumFractionDigits: 2 
  })
}

const formatCurrency = (amount) => {
  return `GHS ${formatAmount(amount || 0)}`
}

const formatPaymentType = (type) => {
  return type ? type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) : 'N/A'
}

const formatPaymentMethod = (method) => {
  return method ? method.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) : 'N/A'
}

const capitalizeFirst = (str) => {
  return str ? str.charAt(0).toUpperCase() + str.slice(1) : ''
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
  return new Date(dateString).toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getStatusBadgeClass = (status) => {
  const statusMap = {
    'completed': 'badge-success',
    'pending': 'badge-warning',
    'processing': 'badge-info',
    'failed': 'badge-danger',
    'refunded': 'badge-secondary'
  }
  return statusMap[status] || 'badge-secondary'
}

const getRevenueChangeClass = () => {
  const change = statistics.value.revenue_change_percentage || 0
  if (change > 0) return 'positive'
  if (change < 0) return 'negative'
  return 'neutral'
}

const formatRevenueChange = () => {
  const change = statistics.value.revenue_change_percentage || 0
  const prefix = change > 0 ? '+' : ''
  return `${prefix}${change.toFixed(1)}% from last month`
}

const isAdminCreated = (payment) => {
  return payment.metadata && payment.metadata.is_admin_created
}

const getAdminCreator = (payment) => {
  if (payment.metadata && payment.metadata.admin_name) {
    return payment.metadata.admin_name
  }
  return null
}

const toggleDropdown = (paymentId) => {
  activeDropdown.value = activeDropdown.value === paymentId ? null : paymentId
}

const openViewModal = (payment) => {
  currentPayment.value = payment
  showViewModal.value = true
  activeDropdown.value = null
}

const closeViewModal = () => {
  showViewModal.value = false
}

const exportPayments = async () => {
  try {
    toast.showInfo('Preparing export...')
    
    const filters = {
      status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
      payment_type: paymentTypeFilter.value !== 'all' ? paymentTypeFilter.value : undefined,
      search: searchQuery.value || undefined,
      start_date: startDateFilter.value || undefined,
      end_date: endDateFilter.value || undefined
    }
    
    Object.keys(filters).forEach(key => filters[key] === undefined && delete filters[key])
    
    const { blob, filename } = await paymentService.exportPayments(filters)
    
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = filename
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
    
    toast.showSuccess('Payments exported successfully!')
  } catch (error) {
    console.error('Error exporting payments:', error)
    toast.showError('Failed to export payments')
  }
}

const goToPage = (page) => {
  if (page >= 1 && page <= payments.value.last_page) {
    loadPayments(page)
  }
}

const nextPage = () => {
  if (payments.value.current_page < payments.value.last_page) {
    goToPage(payments.value.current_page + 1)
  }
}

const prevPage = () => {
  if (payments.value.current_page > 1) {
    goToPage(payments.value.current_page - 1)
  }
}

const getPaginationPages = () => {
  const pages = []
  const maxVisible = 5
  let start = Math.max(1, payments.value.current_page - Math.floor(maxVisible / 2))
  let end = Math.min(payments.value.last_page, start + maxVisible - 1)
  
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
    loadPayments(1)
  }, 500)
})

watch([statusFilter, paymentTypeFilter, startDateFilter, endDateFilter], () => {
  loadPayments(1)
})

onMounted(async () => {
  try {
    await Promise.all([
      loadPayments(),
      getStatistics()
    ])
    
    document.addEventListener('click', handleClickOutside)
  } catch (error) {
    console.error('Mount error:', error)
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

.payments-page {
  padding: 32px;
  background: #f8fafc;
  min-height: 100vh;
  max-width: 100vw;
  overflow-x: hidden;
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

.stat-change.negative {
  color: #ef4444;
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
.payments-table-container {
  background: white;
  border-radius: 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  border: 1px solid #f1f5f9;
  overflow: visible;
}

.table-wrapper {
  overflow-x: auto;
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
  white-space: nowrap;
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

/* Table Cells */
.reference-cell,
.patient-cell,
.amount-cell,
.date-cell {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 150px;
}

.reference-primary,
.patient-name,
.amount-primary,
.date-primary {
  font-size: 14px;
  color: #0f172a;
  font-weight: 600;
}

.reference-secondary,
.patient-contact,
.amount-secondary,
.date-secondary {
  font-size: 13px;
  color: #94a3b8;
}

.payment-method-badge {
  display: inline-flex;
  padding: 4px 10px;
  background: #f1f5f9;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 500;
  color: #475569;
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
  white-space: nowrap;
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
  flex-shrink: 0;
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

.email-column {
  word-break: break-word;
  overflow-wrap: break-word;
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

.modal-xl {
  max-width: 1000px;
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
  flex-shrink: 0;
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

/* Buttons */
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

.btn-secondary {
  background: white;
  color: #334155;
  border: 1px solid #e2e8f0;
}

.btn-secondary:hover:not(:disabled) {
  background: #f8fafc;
  border-color: #cbd5e1;
}

/* Payment View Modal */
.payment-view-grid {
  display: grid;
  grid-template-columns: 320px 1fr;
  gap: 32px;
}

.payment-overview-section {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.overview-header {
  text-align: center;
  padding: 24px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 16px;
  color: white;
}

.payment-status-large {
  display: inline-block;
  padding: 8px 16px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 700;
  margin-bottom: 16px;
  background: rgba(255, 255, 255, 0.2);
}

.payment-amount-large {
  font-size: 36px;
  font-weight: 800;
  letter-spacing: -1px;
}

.overview-details {
  background: #f8fafc;
  padding: 20px;
  border-radius: 12px;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  padding: 12px 0;
  border-bottom: 1px solid #e2e8f0;
}

.detail-row:last-child {
  border-bottom: none;
}

.detail-label {
  font-size: 13px;
  color: #64748b;
  font-weight: 600;
}

.detail-value {
  font-size: 14px;
  color: #0f172a;
  font-weight: 500;
  text-align: right;
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

.amount-breakdown {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.breakdown-row {
  display: flex;
  justify-content: space-between;
  padding: 10px 0;
  border-bottom: 1px solid #e2e8f0;
}

.breakdown-row.total-row {
  border-top: 2px solid #334155;
  border-bottom: none;
  padding-top: 16px;
  margin-top: 8px;
  font-weight: 700;
}

.breakdown-value {
  font-weight: 600;
}

.patient-info-grid,
.info-item {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 16px;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.info-label {
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.info-value {
  font-size: 14px;
  color: #0f172a;
  font-weight: 500;
}

.timeline {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.timeline-item {
  display: flex;
  gap: 16px;
  align-items: flex-start;
}

.timeline-marker {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background: #cbd5e1;
  margin-top: 4px;
  flex-shrink: 0;
}

.timeline-marker.completed {
  background: #10b981;
}

.timeline-marker.refunded {
  background: #ef4444;
}

.timeline-marker.warning {
  background: #f59e0b;
}

.timeline-content {
  flex: 1;
}

.timeline-label {
  font-size: 13px;
  font-weight: 600;
  color: #64748b;
  margin-bottom: 2px;
}

.timeline-value {
  font-size: 14px;
  color: #0f172a;
}

.description-text {
  color: #334155;
  line-height: 1.6;
  margin: 0;
}

.failure-reason {
  padding: 16px;
  background: #fee2e2;
  border: 1px solid #fecaca;
  border-radius: 8px;
  color: #991b1b;
  font-size: 14px;
  line-height: 1.6;
}

.admin-info {
  display: flex;
  gap: 12px;
  padding: 16px;
  background: #fef3c7;
  border: 1px solid #fbbf24;
  border-radius: 8px;
}

.admin-info svg {
  width: 20px;
  height: 20px;
  color: #d97706;
  flex-shrink: 0;
  margin-top: 2px;
}

.admin-info p {
  margin: 0 0 8px 0;
  color: #92400e;
  font-size: 14px;
  line-height: 1.5;
}

.admin-info p:last-child {
  margin-bottom: 0;
}

/* Responsive Design */
@media (max-width: 1024px) {
  .payment-view-grid {
    grid-template-columns: 1fr;
  }

  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .payments-page {
    padding: 16px;
  }

  .page-header {
    flex-direction: column;
    gap: 16px;
  }

  .page-header-actions {
    width: 100%;
    flex-direction: column;
  }

  .btn-modern {
    width: 100%;
    justify-content: center;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .filters-section {
    flex-direction: column;
  }

  .search-wrapper,
  .filters-group {
    width: 100%;
  }

  .filters-group {
    flex-direction: column;
  }

  .filter-select {
    width: 100%;
  }

  .table-wrapper {
    overflow-x: auto;
  }

  .modern-table {
    min-width: 900px;
  }

  .pagination-container {
    flex-direction: column;
  }

  .pagination-info {
    text-align: center;
  }

}
</style>