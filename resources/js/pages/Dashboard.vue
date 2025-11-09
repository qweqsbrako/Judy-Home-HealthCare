<template>
  <MainLayout>
    <!-- Loading State -->
    <div v-if="loading" class="dashboard-loading">
      <div class="loading-spinner">
        <div class="spinner"></div>
        <p>Loading dashboard...</p>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="dashboard-error">
      <div class="error-content">
        <svg class="error-icon" viewBox="0 0 24 24" fill="currentColor">
          <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
        </svg>
        <h2>Failed to Load Dashboard</h2>
        <p>{{ error }}</p>
        <button @click="loadDashboard" class="retry-btn">Retry</button>
      </div>
    </div>

    <!-- Dashboard Content -->
    <div v-else class="dashboard-container">
      <!-- Dashboard Header -->
      <div class="dashboard-header">
        <div class="header-info">
          <h1 class="dashboard-title">Welcome back, {{ user.first_name }} 👋</h1>
          <p class="dashboard-subtitle">Here's what's happening with your {{ user.role === 'patient' ? 'care' : 'patients' }} today</p>
        </div>
        <div class="header-actions">
          <div class="header-time">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8z"/>
              <path d="M12.5 7H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>
            </svg>
            <span class="time-display">{{ currentTime }} • {{ currentDate }}</span>
          </div>
          <button v-if="user.role === 'patient'" @click="requestCare" class="header-btn primary">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
            </svg>
            <span class="btn-text">Request Care</span>
          </button>
          <button v-if="user.role === 'nurse'" @click="toggleClockInOut" class="header-btn" :class="{ 'active': isWorking }">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8z"/>
              <path d="M12.5 7H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>
            </svg>
            <span class="btn-text">{{ isWorking ? 'Clock Out' : 'Clock In' }}</span>
          </button>
        </div>
      </div>

      <!-- Key Metrics Grid -->
      <div class="metrics-grid">
        <!-- Patient Stats -->
        <template v-if="user.role === 'patient'">
          <div class="metric-card health">
            <div class="metric-icon">💙</div>
            <div class="metric-content">
              <div class="metric-label">Health Overview</div>
              <div class="metric-value">{{ patientStats.totalSessions }}</div>
              <div class="metric-sublabel">Care Sessions</div>
              <div class="metric-footer">
                <span class="metric-detail">{{ patientStats.vitalsRecorded }} vitals recorded</span>
              </div>
            </div>
          </div>

          <div class="metric-card payment">
            <div class="metric-icon">💰</div>
            <div class="metric-content">
              <div class="metric-label">Financial Summary</div>
              <div class="metric-value">GHS {{ patientStats.totalSpent.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</div>
              <div class="metric-sublabel">Total Spent</div>
              <div class="metric-footer">
                <span class="metric-detail alert">GHS {{ patientStats.pendingBills.toFixed(2) }} pending</span>
              </div>
            </div>
          </div>

          <div class="metric-card schedule">
            <div class="metric-icon">📅</div>
            <div class="metric-content">
              <div class="metric-label">Next Appointment</div>
              <div class="metric-value">{{ patientStats.nextAppointment.time }}</div>
              <div class="metric-sublabel">{{ patientStats.nextAppointment.date }}</div>
              <div class="metric-footer">
                <span class="metric-detail">{{ patientStats.nextAppointment.nurse }}</span>
              </div>
            </div>
          </div>
        </template>

        <!-- Nurse Stats -->
        <template v-if="user.role === 'nurse'">
          <div class="metric-card patients">
            <div class="metric-icon">👥</div>
            <div class="metric-content">
              <div class="metric-label">Patient Load</div>
              <div class="metric-value">{{ nurseStats.activePatients }}</div>
              <div class="metric-sublabel">Active Patients</div>
              <div class="metric-footer">
                <span class="metric-detail">{{ nurseStats.newPatients }} new this week</span>
              </div>
            </div>
          </div>

          <div class="metric-card time">
            <div class="metric-icon">⏱️</div>
            <div class="metric-content">
              <div class="metric-label">Time Tracking</div>
              <div class="metric-value">{{ nurseStats.hoursWorked }}h</div>
              <div class="metric-sublabel">This Week</div>
              <div class="metric-footer">
                <span class="metric-detail">{{ nurseStats.hoursToday }}h today</span>
              </div>
            </div>
          </div>

          <div class="metric-card performance">
            <div class="metric-icon">⭐</div>
            <div class="metric-content">
              <div class="metric-label">Performance</div>
              <div class="metric-value">{{ nurseStats.avgRating }}/5</div>
              <div class="metric-sublabel">Patient Rating</div>
              <div class="metric-footer">
                <span class="metric-detail">{{ nurseStats.vitalsRecorded }} vitals recorded</span>
              </div>
            </div>
          </div>
        </template>

        <!-- Doctor Stats -->
        <template v-if="user.role === 'doctor'">
          <div class="metric-card patients">
            <div class="metric-icon">📋</div>
            <div class="metric-content">
              <div class="metric-label">Care Plans</div>
              <div class="metric-value">{{ doctorStats.activePlans }}</div>
              <div class="metric-sublabel">Active Plans</div>
              <div class="metric-footer">
                <span class="metric-detail">{{ doctorStats.patientsUnderCare }} patients</span>
              </div>
            </div>
          </div>

          <div class="metric-card performance">
            <div class="metric-icon">✅</div>
            <div class="metric-content">
              <div class="metric-label">Reviews</div>
              <div class="metric-value">{{ doctorStats.pendingReviews }}</div>
              <div class="metric-sublabel">Pending Reviews</div>
              <div class="metric-footer">
                <span class="metric-detail">{{ doctorStats.recentAssessments }} recent</span>
              </div>
            </div>
          </div>
        </template>

        <!-- Admin Stats -->
        <template v-if="canAccess(['admin', 'superadmin'])">
          <div class="metric-card users">
            <div class="metric-icon">👥</div>
            <div class="metric-content">
              <div class="metric-label">User Management</div>
              <div class="metric-value">{{ adminStats.totalUsers }}</div>
              <div class="metric-sublabel">Total Users</div>
              <div class="metric-footer">
                <span class="metric-detail alert">{{ adminStats.pendingVerifications }} pending</span>
              </div>
            </div>
          </div>

          <div class="metric-card revenue">
            <div class="metric-icon">💵</div>
            <div class="metric-content">
              <div class="metric-label">Revenue</div>
              <div class="metric-value">GHS {{ adminStats.monthlyRevenue.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</div>
              <div class="metric-sublabel">Monthly</div>
              <div class="metric-footer">
                <span class="metric-detail" :class="adminStats.revenueGrowth >= 0 ? 'success' : 'alert'">
                  {{ adminStats.revenueGrowth >= 0 ? '+' : '' }}{{ adminStats.revenueGrowth }}% growth
                </span>
              </div>
            </div>
          </div>

          <div class="metric-card incidents">
            <div class="metric-icon">⚠️</div>
            <div class="metric-content">
              <div class="metric-label">Quality Control</div>
              <div class="metric-value">{{ adminStats.openIncidents }}</div>
              <div class="metric-sublabel">Open Incidents</div>
              <div class="metric-footer">
                <span class="metric-detail">{{ adminStats.qualityScore }}% quality</span>
              </div>
            </div>
          </div>
        </template>
      </div>

      <!-- Main Content Grid -->
      <div class="content-grid">
        <!-- Left Column -->
        <div class="left-column">
          <!-- Today's Schedule (Nurse) / Upcoming Appointments (Others) -->
          <div class="card">
            <div class="card-header">
              <div>
                <h3>{{ user.role === 'nurse' ? "Today's Schedule" : 'Upcoming Appointments' }}</h3>
                <p class="card-subtitle">
                  {{ user.role === 'nurse' 
                    ? `You have ${todaysSchedule.length} appointments scheduled for today` 
                    : `Your upcoming appointments for the week` 
                  }}
                </p>
              </div>
              <button class="view-all-btn" @click="viewAllSchedules">View All</button>
            </div>
            
            <div class="card-content">
              <!-- Today's Schedule for Nurses -->
              <div v-if="user.role === 'nurse'" class="schedule-content">
                <div v-if="todaysSchedule.length === 0" class="empty-state">
                  <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/>
                  </svg>
                  <p>No appointments scheduled for today</p>
                </div>
                <div v-else class="appointments-grid">
                  <div v-for="appointment in todaysSchedule" :key="appointment.id" class="appointment-card">
                    <div class="appointment-header">
                      <div class="appointment-avatar">
                        <img :src="appointment.patient_avatar" :alt="appointment.patient_name">
                      </div>
                      <div class="appointment-info">
                        <h4>{{ appointment.patient_name }}</h4>
                        <p class="appointment-time">
                          <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8z"/>
                            <path d="M12.5 7H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>
                          </svg>
                          {{ appointment.time }} • {{ appointment.duration }}
                        </p>
                      </div>
                      <span class="appointment-status" :class="appointment.status">
                        {{ formatStatus(appointment.status) }}
                      </span>
                    </div>
                    <div class="appointment-body">
                      <div class="appointment-detail">
                        <span class="detail-label">Care Type</span>
                        <span class="detail-value">{{ formatCareType(appointment.care_type) }}</span>
                      </div>
                      <div class="appointment-detail">
                        <span class="detail-label">Location</span>
                        <span class="detail-value">📍 {{ appointment.location }}</span>
                      </div>
                      <div v-if="appointment.priority" class="appointment-detail">
                        <span class="detail-label">Priority</span>
                        <span class="detail-value">
                          <span class="priority-badge" :class="appointment.priority">
                            {{ appointment.priority }}
                          </span>
                        </span>
                      </div>
                    </div>
                    <div class="appointment-footer">
                      <button class="btn-secondary btn-sm" @click="viewScheduleDetail(appointment.id)">View</button>
                      <button v-if="appointment.status === 'scheduled' || appointment.status === 'confirmed'" 
                              class="btn-primary btn-sm" 
                              @click="startShift(appointment.id)">
                        Start Visit
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Upcoming Appointments for Others -->
              <div v-else class="upcoming-list">
                <div v-if="upcomingAppointments.length === 0" class="empty-state">
                  <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/>
                  </svg>
                  <p>No upcoming appointments</p>
                </div>
                <div v-else>
                  <div v-for="appointment in upcomingAppointments" :key="appointment.id" class="upcoming-item">
                    <div class="upcoming-date">
                      <div class="date-day">{{ appointment.day }}</div>
                      <div class="date-month">{{ appointment.month }}</div>
                    </div>
                    <div class="upcoming-info">
                      <h4 v-if="appointment.patient && appointment.nurse">
                        <span class="info-label">Patient:</span> {{ appointment.patient }}
                        <span class="info-divider">|</span>
                        <span class="info-label">Nurse:</span> {{ appointment.nurse }}
                      </h4>
                      <h4 v-else>{{ appointment.name }}</h4>
                      <p>{{ appointment.time }} • {{ appointment.type }}</p>
                    </div>
                    <span class="upcoming-status" :class="appointment.status">{{ formatStatus(appointment.status) }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Recent Care Requests (Admin) -->
          <div v-if="canAccess(['admin', 'superadmin'])" class="card">
            <div class="card-header">
              <div>
                <h3>Recent Care Requests</h3>
                <p class="card-subtitle">Latest care requests from patients</p>
              </div>
              <button class="view-all-btn" @click="viewAllCareRequests">View All</button>
            </div>
            
            <div class="card-content">
              <div v-if="recentCareRequests.length === 0" class="empty-state">
                <svg viewBox="0 0 24 24" fill="currentColor">
                  <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/>
                </svg>
                <p>No care requests yet</p>
              </div>
              <div v-else class="care-requests-list">
                <div v-for="request in recentCareRequests" :key="request.id" class="care-request-card">
                  <div class="care-request-header">
                    <div class="care-request-info">
                      <h4>{{ request.patient ? request.patient.name : 'Unknown Patient' }}</h4>
                      <p class="care-request-type">{{ formatCareType(request.care_type) }}</p>
                    </div>
                    <span class="care-request-status" :class="getStatusClass(request.status)">
                      {{ request.formatted_status }}
                    </span>
                  </div>
                  
                  <div class="care-request-body">
                    <div class="care-request-detail">
                      <span class="detail-icon">📍</span>
                      <span class="detail-text">{{ request.city || request.region || 'Location not specified' }}</span>
                    </div>
                    <div class="care-request-detail" v-if="request.urgency_level">
                      <span class="detail-icon">⚠️</span>
                      <span class="detail-text">
                        <span class="urgency-badge" :class="request.urgency_level">
                          {{ request.urgency_level }}
                        </span>
                      </span>
                    </div>
                    <div class="care-request-detail" v-if="request.assignedNurse">
                      <span class="detail-icon">👨‍⚕️</span>
                      <span class="detail-text">{{ request.assignedNurse.name }}</span>
                    </div>
                    <div class="care-request-detail">
                      <span class="detail-icon">🕐</span>
                      <span class="detail-text">{{ formatTime(request.created_at) }}</span>
                    </div>
                  </div>
                  
                  <div class="care-request-footer">
                    <button class="btn-primary btn-sm" @click="viewCareRequestDetail(request.id)">View Details</button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column -->
        <div class="right-column">
          <!-- Recent Activity -->
          <div class="card">
            <div class="card-header">
              <h3>Recent Activity</h3>
            </div>
            <div class="card-content">
              <div v-if="recentActivity.length === 0" class="empty-state">
                <svg viewBox="0 0 24 24" fill="currentColor">
                  <path d="M13 2.05v3.03c3.39.49 6 3.39 6 6.92 0 .9-.18 1.75-.48 2.54l2.6 1.53c.56-1.24.88-2.62.88-4.07 0-5.18-3.95-9.45-9-9.95zM12 19c-3.87 0-7-3.13-7-7 0-3.53 2.61-6.43 6-6.92V2.05c-5.06.5-9 4.76-9 9.95 0 5.52 4.47 10 9.99 10 3.31 0 6.24-1.61 8.06-4.09l-2.6-1.53C16.17 17.98 14.21 19 12 19z"/>
                </svg>
                <p>No recent activity</p>
              </div>
              <div v-else class="activity-list">
                <div v-for="activity in recentActivity" :key="activity.id" class="activity-item">
                  <div class="activity-icon" :class="activity.type">
                    <span v-if="activity.type === 'vitals'">❤️</span>
                    <span v-else-if="activity.type === 'assignment'">📋</span>
                    <span v-else-if="activity.type === 'payment'">💰</span>
                    <span v-else>⚠️</span>
                  </div>
                  <div class="activity-content">
                    <div class="activity-header">
                      <h4>{{ activity.title }}</h4>
                      <span class="activity-time">{{ formatTime(activity.created_at) }}</span>
                    </div>
                    <p>{{ activity.description }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Alerts & Notifications -->
          <div class="card">
            <div class="card-header">
              <h3>Alerts</h3>
              <span v-if="alerts.length > 0" class="alert-badge">{{ alerts.length }}</span>
            </div>
            <div class="card-content">
              <div v-if="alerts.length === 0" class="empty-state">
                <svg viewBox="0 0 24 24" fill="currentColor">
                  <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                </svg>
                <p>No alerts at this time</p>
              </div>
              <div v-else class="alerts-list">
                <div v-for="alert in alerts" :key="alert.id" class="alert-item" :class="alert.type">
                  <div class="alert-header">
                    <h4>{{ alert.title }}</h4>
                    <span class="alert-time">{{ formatTime(alert.created_at) }}</span>
                  </div>
                  <p>{{ alert.message }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import MainLayout from '../layout/MainLayout.vue'
import { getDashboardData } from '../services/dashboardService'

const router = useRouter()

// State
const loading = ref(true)
const error = ref(null)
const isWorking = ref(false)
const currentTime = ref('')
const currentDate = ref('')

// User data
const user = ref({
  id: null,
  first_name: '',
  last_name: '',
  email: '',
  role: '',
  avatar_url: ''
})

// Stats data
const patientStats = ref({
  totalSessions: 0,
  vitalsRecorded: 0,
  assignedNurses: 0,
  pendingBills: 0,
  totalSpent: 0,
  insuranceCovered: 0,
  nextAppointment: {
    time: 'Loading...',
    date: '',
    nurse: '',
    type: ''
  },
  carePlan: null
})

const nurseStats = ref({
  activePatients: 0,
  newPatients: 0,
  criticalPatients: 0,
  hoursWorked: 0,
  hoursToday: 0,
  overtimeHours: 0,
  vitalsRecorded: 0,
  notesWritten: 0,
  avgRating: 0
})

const doctorStats = ref({
  activePlans: 0,
  patientsUnderCare: 0,
  newPatients: 0,
  recentAssessments: 0,
  pendingReviews: 0
})

const adminStats = ref({
  totalUsers: 0,
  activeUsers: 0,
  pendingVerifications: 0,
  monthlyRevenue: 0,
  revenueGrowth: 0,
  avgBill: 0,
  openIncidents: 0,
  resolvedIncidents: 0,
  qualityScore: 0
})

// Activity data
const recentActivity = ref([])
const todaysSchedule = ref([])
const alerts = ref([])
const upcomingAppointments = ref([])
const recentCareRequests = ref([])

// Methods
const loadDashboard = async () => {
  loading.value = true
  error.value = null
  
  try {
    const data = await getDashboardData()
    
    if (data.user) {
      user.value = data.user
    }
    
    if (data.stats) {
      if (user.value.role === 'patient') {
        patientStats.value = { ...patientStats.value, ...data.stats }
      } else if (user.value.role === 'nurse') {
        nurseStats.value = { ...nurseStats.value, ...data.stats }
      } else if (user.value.role === 'doctor') {
        doctorStats.value = { ...doctorStats.value, ...data.stats }
      } else if (user.value.role === 'admin' || user.value.role === 'superadmin') {
        adminStats.value = { ...adminStats.value, ...data.stats }
      }
    }
    
    recentActivity.value = data.recentActivity || []
    alerts.value = data.alerts || []
    todaysSchedule.value = data.todaysSchedule || []
    upcomingAppointments.value = data.upcomingAppointments || []
    recentCareRequests.value = data.recentCareRequests || []
    
  } catch (err) {
    console.error('Error loading dashboard:', err)
    error.value = err.message
  } finally {
    loading.value = false
  }
}

const updateTime = () => {
  const now = new Date()
  currentTime.value = now.toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit'
  })
  currentDate.value = now.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric'
  })
}

const canAccess = (roles) => {
  return roles.includes(user.value.role)
}

const formatTime = (date) => {
  if (!date) return ''
  
  const now = new Date()
  const diff = now - new Date(date)
  const minutes = Math.floor(diff / 60000)
  const hours = Math.floor(minutes / 60)
  const days = Math.floor(hours / 24)

  if (days > 0) return `${days}d ago`
  if (hours > 0) return `${hours}h ago`
  if (minutes > 0) return `${minutes}m ago`
  return 'Just now'
}

const formatStatus = (status) => {
  if (!status) return ''
  return status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatCareType = (careType) => {
  if (!careType) return 'General Care'
  return careType.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const requestCare = () => {
  router.push('/care/request')
}

const toggleClockInOut = () => {
  isWorking.value = !isWorking.value
  alert(`${isWorking.value ? 'Clocked In' : 'Clocked Out'}!`)
}

const viewAllSchedules = () => {
  router.push('/care/schedules')
}

const viewScheduleDetail = (id) => {
  router.push(`/schedules/${id}`)
}

const startShift = (id) => {
  router.push(`/schedules/${id}/start`)
}

const getStatusClass = (status) => {
  const statusMap = {
    'pending_payment': 'pending',
    'payment_received': 'confirmed',
    'nurse_assigned': 'confirmed',
    'assessment_scheduled': 'scheduled',
    'assessment_completed': 'completed',
    'under_review': 'pending',
    'awaiting_care_payment': 'pending',
    'care_active': 'confirmed',
    'care_completed': 'completed',
    'cancelled': 'cancelled',
    'rejected': 'cancelled'
  }
  return statusMap[status] || 'pending'
}

const viewAllCareRequests = () => {
  router.push('/care/patient/requests')
}

const viewCareRequestDetail = (id) => {
  router.push('/care/patient/requests')
}

// Lifecycle
let timeInterval
let dashboardInterval

onMounted(() => {
  loadDashboard()
  updateTime()
  
  timeInterval = setInterval(updateTime, 60000)
  dashboardInterval = setInterval(loadDashboard, 300000)
})

onUnmounted(() => {
  if (timeInterval) clearInterval(timeInterval)
  if (dashboardInterval) clearInterval(dashboardInterval)
})
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

* {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

.dashboard-container {
  width: 100%;
  padding: 0 16px;
}

/* Loading and Error States */
.dashboard-loading {
  min-height: 60vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.loading-spinner {
  text-align: center;
}

.spinner {
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

.loading-spinner p {
  color: #64748b;
  font-size: 14px;
  font-weight: 500;
}

.dashboard-error {
  min-height: 60vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.error-content {
  text-align: center;
  max-width: 400px;
}

.error-icon {
  width: 64px;
  height: 64px;
  color: #ef4444;
  margin: 0 auto 16px;
}

.error-content h2 {
  font-size: 20px;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 8px 0;
  letter-spacing: -0.3px;
}

.error-content p {
  color: #64748b;
  margin: 0 0 24px 0;
  font-size: 14px;
}

.retry-btn {
  background: #667eea;
  color: white;
  border: none;
  padding: 12px 24px;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  min-height: 44px;
}

.retry-btn:hover {
  background: #5a67d8;
  transform: translateY(-1px);
}

/* Dashboard Header */
.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding-bottom: 24px;
  margin-bottom: 24px;
  border-bottom: 1px solid #e2e8f0;
  gap: 16px;
}

.dashboard-title {
  font-size: 24px;
  font-weight: 800;
  color: #0f172a;
  margin: 0 0 6px 0;
  letter-spacing: -0.5px;
  line-height: 1.2;
}

.dashboard-subtitle {
  color: #64748b;
  font-size: 14px;
  margin: 0;
  font-weight: 400;
  line-height: 1.4;
}

.header-actions {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  align-items: center;
  flex-shrink: 0;
}

.header-time {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 12px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  color: #475569;
  font-size: 12px;
  font-weight: 600;
  white-space: nowrap;
}

.header-time svg {
  width: 16px;
  height: 16px;
  color: #64748b;
  flex-shrink: 0;
}

.header-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 10px 16px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: white;
  color: #334155;
  cursor: pointer;
  font-weight: 600;
  font-size: 13px;
  transition: all 0.2s;
  min-height: 44px;
  white-space: nowrap;
}

.header-btn:hover {
  background: #f8fafc;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.header-btn.primary {
  background: #667eea;
  color: white;
  border-color: #667eea;
}

.header-btn.primary:hover {
  background: #5a67d8;
  border-color: #5a67d8;
}

.header-btn.active {
  background: #10b981;
  color: white;
  border-color: #10b981;
}

.header-btn.active:hover {
  background: #059669;
  border-color: #059669;
}

.header-btn svg {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
}

/* Metrics Grid */
.metrics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

.metric-card {
  background: white;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  border: 1px solid #f1f5f9;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  display: flex;
  gap: 14px;
  cursor: pointer;
}

.metric-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
  border-color: #e2e8f0;
}

.metric-icon {
  width: 48px;
  height: 48px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  flex-shrink: 0;
}

.metric-card.health .metric-icon { background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); }
.metric-card.payment .metric-icon { background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); }
.metric-card.schedule .metric-icon { background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); }
.metric-card.patients .metric-icon { background: linear-gradient(135deg, #e9d5ff 0%, #d8b4fe 100%); }
.metric-card.time .metric-icon { background: linear-gradient(135deg, #cffafe 0%, #a5f3fc 100%); }
.metric-card.performance .metric-icon { background: linear-gradient(135deg, #fed7aa 0%, #fdba74 100%); }
.metric-card.users .metric-icon { background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%); }
.metric-card.revenue .metric-icon { background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); }
.metric-card.incidents .metric-icon { background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); }

.metric-content {
  flex: 1;
  min-width: 0;
}

.metric-label {
  font-size: 12px;
  color: #64748b;
  font-weight: 600;
  margin-bottom: 6px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.metric-value {
  font-size: 28px;
  font-weight: 800;
  color: #0f172a;
  line-height: 1;
  margin-bottom: 4px;
  letter-spacing: -0.8px;
}

.metric-sublabel {
  font-size: 13px;
  color: #475569;
  font-weight: 500;
  margin-bottom: 10px;
}

.metric-footer {
  padding-top: 10px;
  border-top: 1px solid #f1f5f9;
}

.metric-detail {
  font-size: 12px;
  color: #64748b;
  font-weight: 500;
}

.metric-detail.alert {
  color: #ef4444;
  font-weight: 600;
}

.metric-detail.success {
  color: #10b981;
  font-weight: 600;
}

/* Content Grid */
.content-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 16px;
}

.left-column,
.right-column {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

/* Cards */
.card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  border: 1px solid #f1f5f9;
  overflow: hidden;
}

.card-header {
  padding: 20px 20px 14px 20px;
  border-bottom: 1px solid #f1f5f9;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
}

.card-header h3 {
  font-size: 16px;
  font-weight: 700;
  margin: 0;
  color: #0f172a;
  letter-spacing: -0.3px;
}

.card-subtitle {
  font-size: 12px;
  color: #64748b;
  margin: 4px 0 0 0;
  font-weight: 500;
}

.card-content {
  padding: 20px;
}

.view-all-btn {
  background: none;
  border: none;
  color: #667eea;
  cursor: pointer;
  font-size: 12px;
  padding: 6px 10px;
  border-radius: 6px;
  transition: all 0.2s;
  font-weight: 600;
  flex-shrink: 0;
}

.view-all-btn:hover {
  background: #f0f4ff;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 40px 20px;
  color: #94a3b8;
}

.empty-state svg {
  width: 40px;
  height: 40px;
  margin: 0 auto 12px;
  opacity: 0.5;
}

.empty-state p {
  margin: 0;
  font-size: 13px;
  font-weight: 500;
}

/* Appointment Cards */
.appointments-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 12px;
}

.appointment-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 14px;
  transition: all 0.2s;
}

.appointment-card:hover {
  border-color: #cbd5e1;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.appointment-header {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  margin-bottom: 12px;
}

.appointment-avatar {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  overflow: hidden;
  flex-shrink: 0;
  border: 2px solid white;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.appointment-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.appointment-info {
  flex: 1;
  min-width: 0;
}

.appointment-info h4 {
  font-size: 14px;
  font-weight: 600;
  margin: 0 0 4px 0;
  color: #0f172a;
}

.appointment-time {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  color: #64748b;
  margin: 0;
  flex-wrap: wrap;
}

.appointment-time svg {
  width: 12px;
  height: 12px;
  flex-shrink: 0;
}

.appointment-status {
  padding: 4px 8px;
  border-radius: 6px;
  font-size: 10px;
  font-weight: 600;
  text-transform: capitalize;
  white-space: nowrap;
  flex-shrink: 0;
}

.appointment-status.confirmed {
  background: #dbeafe;
  color: #1e40af;
}

.appointment-status.pending,
.appointment-status.scheduled {
  background: #fef3c7;
  color: #d97706;
}

.appointment-status.urgent,
.appointment-status.critical {
  background: #fee2e2;
  color: #dc2626;
}

.appointment-status.completed {
  background: #d1fae5;
  color: #065f46;
}

.appointment-status.in_progress {
  background: #dbeafe;
  color: #1e40af;
}

.appointment-body {
  display: flex;
  flex-direction: column;
  gap: 6px;
  margin-bottom: 12px;
  padding: 10px;
  background: white;
  border-radius: 8px;
}

.appointment-detail {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
}

.detail-label {
  font-size: 11px;
  color: #64748b;
  font-weight: 500;
}

.detail-value {
  font-size: 12px;
  color: #0f172a;
  font-weight: 600;
  word-break: break-word;
}

.priority-badge {
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 10px;
  font-weight: 600;
  text-transform: capitalize;
}

.priority-badge.high,
.priority-badge.critical {
  background: #fee2e2;
  color: #dc2626;
}

.priority-badge.medium {
  background: #fef3c7;
  color: #d97706;
}

.priority-badge.low {
  background: #d1fae5;
  color: #065f46;
}

.appointment-footer {
  display: flex;
  gap: 8px;
}

/* Buttons */
.btn-primary {
  background: #667eea;
  color: white;
  border: none;
  padding: 10px 14px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  min-height: 40px;
}

.btn-primary:hover {
  background: #5a67d8;
  transform: translateY(-1px);
}

.btn-secondary {
  background: white;
  color: #334155;
  border: 1px solid #e2e8f0;
  padding: 10px 14px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  min-height: 40px;
}

.btn-secondary:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
}

.btn-sm {
  padding: 8px 12px;
  font-size: 11px;
  min-height: 36px;
}

/* Upcoming List */
.upcoming-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.upcoming-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  transition: all 0.2s;
}

.upcoming-item:hover {
  border-color: #cbd5e1;
  background: #f1f5f9;
  transform: translateX(2px);
}

.upcoming-date {
  text-align: center;
  padding: 10px;
  background: white;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  min-width: 52px;
  flex-shrink: 0;
}

.date-day {
  font-size: 20px;
  font-weight: 800;
  color: #0f172a;
  line-height: 1;
}

.date-month {
  font-size: 11px;
  color: #64748b;
  font-weight: 600;
  text-transform: uppercase;
  margin-top: 4px;
}

.upcoming-info {
  flex: 1;
  min-width: 0;
}

.upcoming-info h4 {
  font-size: 14px;
  font-weight: 600;
  margin: 0 0 4px 0;
  color: #0f172a;
}

.upcoming-info p {
  font-size: 12px;
  color: #64748b;
  margin: 0;
}

.upcoming-status {
  padding: 4px 8px;
  border-radius: 6px;
  font-size: 10px;
  font-weight: 600;
  text-transform: capitalize;
  flex-shrink: 0;
}

.upcoming-status.confirmed {
  background: #d1fae5;
  color: #065f46;
}

.upcoming-status.pending,
.upcoming-status.scheduled {
  background: #fef3c7;
  color: #d97706;
}

/* Activity List */
.activity-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
  max-height: 400px;
  overflow-y: auto;
}

.activity-item {
  display: flex;
  gap: 10px;
  padding: 14px;
  border-radius: 10px;
  border: 1px solid #f1f5f9;
  transition: all 0.2s;
}

.activity-item:hover {
  background: #f8fafc;
  border-color: #e2e8f0;
}

.activity-icon {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  font-size: 16px;
}

.activity-icon.vitals {
  background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
}

.activity-icon.assignment {
  background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
}

.activity-icon.payment {
  background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
}

.activity-icon.incident {
  background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
}

.activity-content {
  flex: 1;
  min-width: 0;
}

.activity-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 4px;
  gap: 8px;
}

.activity-header h4 {
  font-size: 13px;
  font-weight: 600;
  margin: 0;
  color: #0f172a;
}

.activity-time {
  font-size: 11px;
  color: #94a3b8;
  flex-shrink: 0;
  font-weight: 500;
}

.activity-content p {
  font-size: 12px;
  color: #64748b;
  margin: 0;
  line-height: 1.4;
}

/* Alerts */
.alert-badge {
  background: #ef4444;
  color: white;
  padding: 3px 7px;
  border-radius: 50%;
  font-size: 10px;
  font-weight: 700;
  min-width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

/* Care Requests */
.care-requests-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.care-request-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 14px;
  transition: all 0.2s;
}

.care-request-card:hover {
  border-color: #cbd5e1;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.care-request-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 10px;
  margin-bottom: 12px;
}

.care-request-info h4 {
  font-size: 14px;
  font-weight: 600;
  margin: 0 0 4px 0;
  color: #0f172a;
}

.care-request-type {
  font-size: 12px;
  color: #64748b;
  margin: 0;
  font-weight: 500;
}

.care-request-status {
  padding: 4px 8px;
  border-radius: 6px;
  font-size: 10px;
  font-weight: 600;
  text-transform: capitalize;
  white-space: nowrap;
  flex-shrink: 0;
}

.care-request-status.pending {
  background: #fef3c7;
  color: #d97706;
}

.care-request-status.confirmed {
  background: #dbeafe;
  color: #1e40af;
}

.care-request-status.scheduled {
  background: #e0e7ff;
  color: #4f46e5;
}

.care-request-status.completed {
  background: #d1fae5;
  color: #065f46;
}

.care-request-status.cancelled {
  background: #fee2e2;
  color: #dc2626;
}

.care-request-body {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 12px;
  padding: 10px;
  background: white;
  border-radius: 8px;
}

.care-request-detail {
  display: flex;
  align-items: center;
  gap: 8px;
}

.detail-icon {
  font-size: 14px;
  flex-shrink: 0;
}

.detail-text {
  font-size: 12px;
  color: #475569;
  font-weight: 500;
}

.urgency-badge {
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 10px;
  font-weight: 600;
  text-transform: capitalize;
}

.urgency-badge.routine {
  background: #d1fae5;
  color: #065f46;
}

.urgency-badge.urgent {
  background: #fef3c7;
  color: #d97706;
}

.urgency-badge.emergency {
  background: #fee2e2;
  color: #dc2626;
}

.care-request-footer {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.alerts-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
  max-height: 400px;
  overflow-y: auto;
}

.alert-item {
  padding: 14px;
  border-radius: 10px;
  border-left: 3px solid;
  transition: all 0.2s;
}

.alert-item.critical {
  background: #fef2f2;
  border-left-color: #dc2626;
}

.alert-item.warning {
  background: #fffbeb;
  border-left-color: #f59e0b;
}

.alert-item.info {
  background: #eff6ff;
  border-left-color: #3b82f6;
}

.alert-item.success {
  background: #f0fdf4;
  border-left-color: #10b981;
}

.alert-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 4px;
  gap: 8px;
}

.alert-item h4 {
  font-size: 13px;
  font-weight: 600;
  margin: 0;
  color: #0f172a;
}

.alert-time {
  font-size: 11px;
  color: #64748b;
  flex-shrink: 0;
  font-weight: 500;
}

.alert-item p {
  font-size: 12px;
  color: #475569;
  margin: 0;
  line-height: 1.4;
}

/* Responsive Breakpoints */
@media (max-width: 768px) {
  .dashboard-container {
    padding: 0 12px;
  }

  .dashboard-header {
    flex-direction: column;
    align-items: stretch;
  }
  
  .header-actions {
    flex-direction: column;
    width: 100%;
  }

  .header-time,
  .header-btn {
    width: 100%;
    justify-content: center;
  }
  
  .metrics-grid {
    grid-template-columns: 1fr;
  }

  .card-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .care-request-footer {
    flex-direction: column;
  }

  .care-request-footer .btn-primary,
  .care-request-footer .btn-secondary {
    width: 100%;
    justify-content: center;
  }
}

@media (min-width: 1024px) {
  .content-grid {
    grid-template-columns: 2fr 1fr;
  }

  .metrics-grid {
    grid-template-columns: repeat(3, 1fr);
  }

  .appointments-grid {
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  }
}
</style>