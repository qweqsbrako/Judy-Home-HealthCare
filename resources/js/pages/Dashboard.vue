<template>
  <MainLayout>
    <!-- Loading State -->
    <div v-if="loading" class="dashboard-loading">
      <div class="loading-spinner">
        
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
            <span>{{ currentTime }} • {{ currentDate }}</span>
          </div>
          <button v-if="user.role === 'patient'" @click="requestCare" class="header-btn primary">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
            </svg>
            Request Care
          </button>
          <button v-if="user.role === 'nurse'" @click="toggleClockInOut" class="header-btn" :class="{ 'active': isWorking }">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8z"/>
              <path d="M12.5 7H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>
            </svg>
            {{ isWorking ? 'Clock Out' : 'Clock In' }}
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
              <div class="metric-value">${{ patientStats.totalSpent.toLocaleString() }}</div>
              <div class="metric-sublabel">Total Spent</div>
              <div class="metric-footer">
                <span class="metric-detail alert">${{ patientStats.pendingBills }} pending</span>
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
              <div class="metric-value">${{ (adminStats.monthlyRevenue / 1000).toFixed(0) }}K</div>
              <div class="metric-sublabel">Monthly</div>
              <div class="metric-footer">
                <span class="metric-detail success">+{{ adminStats.revenueGrowth }}% growth</span>
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
        <!-- Left Column - Tabbed Content -->
        <div class="left-column">
          <!-- Tabbed Section -->
          <div class="card">
            <div class="card-header-tabs">
              <div class="tabs-header">
                <h3>Today's Schedule</h3>
                <p class="tabs-subtitle">You have {{ getTotalAppointments() }} appointments scheduled for today</p>
              </div>
              <div class="tabs-nav">
                <button 
                  v-for="tab in tabs" 
                  :key="tab.id"
                  @click="activeTab = tab.id"
                  class="tab-btn"
                  :class="{ 'active': activeTab === tab.id }"
                >
                  {{ tab.label }}
                </button>
              </div>
            </div>
            
            <div class="card-content">
              <!-- Schedule Tab -->
              <div v-show="activeTab === 'schedule'" class="tab-content">
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
                        <img :src="getAvatarUrl(appointment.patient_name)" :alt="appointment.patient_name">
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
                        {{ appointment.status }}
                      </span>
                    </div>
                    <div class="appointment-body">
                      <div class="appointment-detail">
                        <span class="detail-label">Care Type</span>
                        <span class="detail-value">{{ appointment.care_type }}</span>
                      </div>
                      <div class="appointment-detail">
                        <span class="detail-label">Location</span>
                        <span class="detail-value">📍 {{ appointment.location }}</span>
                      </div>
                    </div>
                    <div class="appointment-footer">
                      <button class="btn-secondary btn-sm">Check-in</button>
                      <button class="btn-primary btn-sm">View</button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Patients Tab -->
              <div v-show="activeTab === 'patients'" class="tab-content">
                <div class="patients-list">
                  <div v-for="patient in upcomingAppointments" :key="patient.id" class="patient-card">
                    <div class="patient-header">
                      <div class="patient-avatar">
                        <img :src="getAvatarUrl(patient.name)" :alt="patient.name">
                        <div class="patient-status" :class="patient.status"></div>
                      </div>
                      <div class="patient-info">
                        <h4>{{ patient.name }}</h4>
                        <p>{{ patient.condition }}</p>
                      </div>
                      <span class="patient-badge" :class="patient.priority">
                        {{ patient.priority }}
                      </span>
                    </div>
                    <div class="patient-details">
                      <div class="detail-item">
                        <span class="detail-icon">📅</span>
                        <span>{{ patient.nextVisit }}</span>
                      </div>
                      <div class="detail-item">
                        <span class="detail-icon">💊</span>
                        <span>{{ patient.medications }} medications</span>
                      </div>
                      <div class="detail-item">
                        <span class="detail-icon">📋</span>
                        <span>Last visit: {{ patient.lastVisit }}</span>
                      </div>
                    </div>
                    <div class="patient-actions">
                      <button class="btn-ghost btn-sm">View Records</button>
                      <button class="btn-primary btn-sm">Contact</button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Tasks Tab -->
              <div v-show="activeTab === 'tasks'" class="tab-content">
                <div class="tasks-list">
                  <div v-for="task in pendingTasks" :key="task.id" class="task-item">
                    <div class="task-checkbox">
                      <input type="checkbox" :id="'task-' + task.id" v-model="task.completed">
                      <label :for="'task-' + task.id"></label>
                    </div>
                    <div class="task-content">
                      <h4>{{ task.title }}</h4>
                      <p>{{ task.description }}</p>
                      <div class="task-meta">
                        <span class="task-priority" :class="task.priority">{{ task.priority }}</span>
                        <span class="task-due">Due: {{ task.dueDate }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Stats Tab -->
              <div v-show="activeTab === 'stats'" class="tab-content">
                <div class="stats-overview">
                  <div class="stat-box">
                    <div class="stat-icon">📊</div>
                    <div class="stat-info">
                      <h4>Total Appointments</h4>
                      <p class="stat-number">{{ todaysSchedule.length }}</p>
                      <span class="stat-change positive">+12% from yesterday</span>
                    </div>
                  </div>
                  <div class="stat-box">
                    <div class="stat-icon">✅</div>
                    <div class="stat-info">
                      <h4>Completed</h4>
                      <p class="stat-number">{{ getCompletedCount() }}</p>
                      <span class="stat-change positive">On track</span>
                    </div>
                  </div>
                  <div class="stat-box">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-info">
                      <h4>Pending</h4>
                      <p class="stat-number">{{ getPendingCount() }}</p>
                      <span class="stat-change neutral">Review needed</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Upcoming Appointments -->
          <div class="card">
            <div class="card-header">
              <div>
                <h3>Upcoming Appointments</h3>
                <p class="card-subtitle">Your upcoming appointments for the week</p>
              </div>
              <button class="view-all-btn">View All</button>
            </div>
            <div class="card-content">
              <div class="upcoming-list">
                <div v-for="appointment in upcomingAppointments" :key="appointment.id" class="upcoming-item">
                  <div class="upcoming-date">
                    <div class="date-day">{{ appointment.day }}</div>
                    <div class="date-month">{{ appointment.month }}</div>
                  </div>
                  <div class="upcoming-info">
                    <h4>{{ appointment.name }}</h4>
                    <p>{{ appointment.time }} • {{ appointment.type }}</p>
                  </div>
                  <span class="upcoming-status" :class="appointment.status">{{ appointment.status }}</span>
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
const pendingTasks = ref([])

// Tabs
const activeTab = ref('schedule')
const tabs = [
  { id: 'schedule', label: 'Schedule' },
  { id: 'patients', label: 'Patients' },
  { id: 'tasks', label: 'Tasks' },
  { id: 'stats', label: 'Stats' }
]

// Computed properties
const today = computed(() => {
  return new Date().toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
})

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
    
    if (user.value.role === 'nurse') {
      todaysSchedule.value = data.todaysSchedule || []
    }

    // Load upcoming appointments
    upcomingAppointments.value = data.upcomingAppointments || [
      {
        id: 1,
        name: 'John Doe',
        day: '24',
        month: 'Apr',
        time: '10:30 AM',
        type: 'Follow-up',
        status: 'confirmed',
        condition: 'Post-surgery care',
        priority: 'high',
        nextVisit: 'Sunday, April 28 at 10:00 AM',
        medications: 3,
        lastVisit: 'April 20, 2025'
      },
      {
        id: 2,
        name: 'Jane Smith',
        day: '25',
        month: 'Apr',
        time: '02:00 PM',
        type: 'Consultation',
        status: 'pending',
        condition: 'Elderly care',
        priority: 'medium',
        nextVisit: 'Monday, April 29 at 02:00 PM',
        medications: 5,
        lastVisit: 'April 18, 2025'
      },
      {
        id: 3,
        name: 'Robert Johnson',
        day: '26',
        month: 'Apr',
        time: '11:00 AM',
        type: 'Check-up',
        status: 'confirmed',
        condition: 'Diabetes management',
        priority: 'low',
        nextVisit: 'Tuesday, April 30 at 11:00 AM',
        medications: 2,
        lastVisit: 'April 19, 2025'
      }
    ]

    // Load pending tasks
    pendingTasks.value = data.pendingTasks || [
      {
        id: 1,
        title: 'Review patient medication list',
        description: 'Update and verify current medications for John Doe',
        priority: 'high',
        dueDate: 'Today, 3:00 PM',
        completed: false
      },
      {
        id: 2,
        title: 'Complete care plan documentation',
        description: 'Finalize care plan for new patient intake',
        priority: 'medium',
        dueDate: 'Tomorrow, 10:00 AM',
        completed: false
      },
      {
        id: 3,
        title: 'Schedule follow-up appointment',
        description: 'Contact patient for post-surgery follow-up',
        priority: 'high',
        dueDate: 'Today, 5:00 PM',
        completed: false
      }
    ]
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

const getActionText = (status) => {
  const actionMap = {
    'pending': 'Start Visit',
    'in_progress': 'Continue',
    'completed': 'View Report'
  }
  return actionMap[status] || 'View'
}

const requestCare = () => {
  router.push('/care/request')
}

const toggleClockInOut = () => {
  isWorking.value = !isWorking.value
  alert(`${isWorking.value ? 'Clocked In' : 'Clocked Out'}!`)
}

const getAvatarUrl = (name) => {
  return `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=667eea&color=fff&size=128`
}

const getTotalAppointments = () => {
  return todaysSchedule.value.length
}

const getCompletedCount = () => {
  return todaysSchedule.value.filter(a => a.status === 'completed').length
}

const getPendingCount = () => {
  return todaysSchedule.value.filter(a => a.status === 'pending').length
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
}

/* Loading and Error States */
.dashboard-loading {
  min-height: 60vh;
  display: flex;
  align-items: center;
  justify-content: center;
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
  padding: 32px;
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
  padding-bottom: 32px;
  margin-bottom: 32px;
  border-bottom: 1px solid #e2e8f0;
}

.dashboard-title {
  font-size: 32px;
  font-weight: 800;
  color: #0f172a;
  margin: 0 0 6px 0;
  letter-spacing: -0.8px;
}

.dashboard-subtitle {
  color: #64748b;
  font-size: 15px;
  margin: 0;
  font-weight: 400;
}

.header-actions {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  align-items: center;
}

.header-time {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  color: #475569;
  font-size: 13px;
  font-weight: 600;
}

.header-time svg {
  width: 18px;
  height: 18px;
  color: #64748b;
}

.header-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 20px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: white;
  color: #334155;
  cursor: pointer;
  font-weight: 600;
  font-size: 14px;
  transition: all 0.2s;
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
  width: 18px;
  height: 18px;
}

/* Metrics Grid */
.metrics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 20px;
  margin-bottom: 32px;
}

.metric-card {
  background: white;
  border-radius: 16px;
  padding: 24px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  border: 1px solid #f1f5f9;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  display: flex;
  gap: 16px;
  cursor: pointer;
}

.metric-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 24px rgba(0, 0, 0, 0.08);
  border-color: #e2e8f0;
}

.metric-icon {
  width: 56px;
  height: 56px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
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
  font-size: 13px;
  color: #64748b;
  font-weight: 600;
  margin-bottom: 8px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.metric-value {
  font-size: 32px;
  font-weight: 800;
  color: #0f172a;
  line-height: 1;
  margin-bottom: 4px;
  letter-spacing: -1px;
}

.metric-sublabel {
  font-size: 14px;
  color: #475569;
  font-weight: 500;
  margin-bottom: 12px;
}

.metric-footer {
  padding-top: 12px;
  border-top: 1px solid #f1f5f9;
}

.metric-detail {
  font-size: 13px;
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
  grid-template-columns: 2fr 1fr;
  gap: 20px;
}

.left-column,
.right-column {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

/* Cards */
.card {
  background: white;
  border-radius: 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  border: 1px solid #f1f5f9;
  overflow: hidden;
}

.card-header {
  padding: 24px 24px 16px 24px;
  border-bottom: 1px solid #f1f5f9;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-header h3 {
  font-size: 18px;
  font-weight: 700;
  margin: 0;
  color: #0f172a;
  letter-spacing: -0.3px;
}

.card-subtitle {
  font-size: 13px;
  color: #64748b;
  margin: 4px 0 0 0;
  font-weight: 500;
}

/* Tabs */
.card-header-tabs {
  padding: 24px 24px 0 24px;
  border-bottom: 1px solid #f1f5f9;
}

.tabs-header {
  margin-bottom: 20px;
}

.tabs-header h3 {
  font-size: 20px;
  font-weight: 700;
  margin: 0 0 4px 0;
  color: #0f172a;
  letter-spacing: -0.4px;
}

.tabs-subtitle {
  font-size: 14px;
  color: #64748b;
  margin: 0;
  font-weight: 500;
}

.tabs-nav {
  display: flex;
  gap: 8px;
  margin-bottom: -1px;
}

.tab-btn {
  padding: 12px 20px;
  background: none;
  border: none;
  border-bottom: 3px solid transparent;
  color: #64748b;
  cursor: pointer;
  font-size: 14px;
  font-weight: 600;
  transition: all 0.2s;
  position: relative;
}

.tab-btn:hover {
  color: #334155;
  background: #f8fafc;
}

.tab-btn.active {
  color: #667eea;
  border-bottom-color: #667eea;
}

.tab-content {
  animation: fadeIn 0.3s ease;
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

/* Appointment Cards */
.appointments-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 16px;
}

.appointment-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 16px;
  transition: all 0.2s;
}

.appointment-card:hover {
  border-color: #cbd5e1;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.appointment-header {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  margin-bottom: 16px;
}

.appointment-avatar {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  overflow: hidden;
  flex-shrink: 0;
  border: 2px solid white;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
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
  font-size: 15px;
  font-weight: 600;
  margin: 0 0 4px 0;
  color: #0f172a;
}

.appointment-time {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  color: #64748b;
  margin: 0;
}

.appointment-time svg {
  width: 14px;
  height: 14px;
}

.appointment-status {
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 600;
  text-transform: capitalize;
}

.appointment-status.confirmed {
  background: #dbeafe;
  color: #1e40af;
}

.appointment-status.pending {
  background: #fef3c7;
  color: #d97706;
}

.appointment-status.urgent {
  background: #fee2e2;
  color: #dc2626;
}

.appointment-status.completed {
  background: #d1fae5;
  color: #065f46;
}

.appointment-body {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 16px;
  padding: 12px;
  background: white;
  border-radius: 8px;
}

.appointment-detail {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.detail-label {
  font-size: 12px;
  color: #64748b;
  font-weight: 500;
}

.detail-value {
  font-size: 13px;
  color: #0f172a;
  font-weight: 600;
}

.appointment-footer {
  display: flex;
  gap: 8px;
}

/* Patients List */
.patients-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.patient-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 16px;
  transition: all 0.2s;
}

.patient-card:hover {
  border-color: #cbd5e1;
  background: #f1f5f9;
}

.patient-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
}

.patient-avatar {
  position: relative;
  width: 48px;
  height: 48px;
  border-radius: 12px;
  overflow: hidden;
  flex-shrink: 0;
  border: 2px solid white;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.patient-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.patient-status {
  position: absolute;
  bottom: -2px;
  right: -2px;
  width: 14px;
  height: 14px;
  border-radius: 50%;
  border: 2px solid #f8fafc;
}

.patient-status.active {
  background: #10b981;
}

.patient-status.inactive {
  background: #6b7280;
}

.patient-info {
  flex: 1;
  min-width: 0;
}

.patient-info h4 {
  font-size: 15px;
  font-weight: 600;
  margin: 0 0 4px 0;
  color: #0f172a;
}

.patient-info p {
  font-size: 13px;
  color: #64748b;
  margin: 0;
}

.patient-badge {
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 600;
  text-transform: capitalize;
}

.patient-badge.high {
  background: #fee2e2;
  color: #dc2626;
}

.patient-badge.medium {
  background: #fef3c7;
  color: #d97706;
}

.patient-badge.low {
  background: #d1fae5;
  color: #065f46;
}

.patient-details {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 12px;
}

.detail-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: #475569;
}

.detail-icon {
  font-size: 16px;
}

.patient-actions {
  display: flex;
  gap: 8px;
}

/* Tasks List */
.tasks-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.task-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 16px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  transition: all 0.2s;
}

.task-item:hover {
  border-color: #cbd5e1;
  background: #f1f5f9;
}

.task-checkbox {
  position: relative;
  flex-shrink: 0;
}

.task-checkbox input {
  width: 20px;
  height: 20px;
  cursor: pointer;
}

.task-content {
  flex: 1;
  min-width: 0;
}

.task-content h4 {
  font-size: 15px;
  font-weight: 600;
  margin: 0 0 4px 0;
  color: #0f172a;
}

.task-content p {
  font-size: 13px;
  color: #64748b;
  margin: 0 0 8px 0;
}

.task-meta {
  display: flex;
  gap: 12px;
  align-items: center;
}

.task-priority {
  padding: 4px 8px;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 600;
  text-transform: capitalize;
}

.task-priority.high {
  background: #fee2e2;
  color: #dc2626;
}

.task-priority.medium {
  background: #fef3c7;
  color: #d97706;
}

.task-priority.low {
  background: #d1fae5;
  color: #065f46;
}

.task-due {
  font-size: 12px;
  color: #64748b;
}

/* Stats Overview */
.stats-overview {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
}

.stat-box {
  display: flex;
  gap: 16px;
  padding: 20px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  transition: all 0.2s;
}

.stat-box:hover {
  border-color: #cbd5e1;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.stat-icon {
  width: 48px;
  height: 48px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  flex-shrink: 0;
}

.stat-info h4 {
  font-size: 13px;
  color: #64748b;
  margin: 0 0 4px 0;
  font-weight: 600;
}

.stat-number {
  font-size: 28px;
  font-weight: 800;
  color: #0f172a;
  margin: 4px 0;
  line-height: 1;
}

.stat-change {
  font-size: 12px;
  font-weight: 600;
}

.stat-change.positive {
  color: #10b981;
}

.stat-change.neutral {
  color: #f59e0b;
}

/* Upcoming List */
.upcoming-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.upcoming-item {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  transition: all 0.2s;
}

.upcoming-item:hover {
  border-color: #cbd5e1;
  background: #f1f5f9;
  transform: translateX(4px);
}

.upcoming-date {
  text-align: center;
  padding: 12px;
  background: white;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  min-width: 60px;
}

.date-day {
  font-size: 24px;
  font-weight: 800;
  color: #0f172a;
  line-height: 1;
}

.date-month {
  font-size: 12px;
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
  font-size: 15px;
  font-weight: 600;
  margin: 0 0 4px 0;
  color: #0f172a;
}

.upcoming-info p {
  font-size: 13px;
  color: #64748b;
  margin: 0;
}

.upcoming-status {
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 600;
  text-transform: capitalize;
}

.upcoming-status.confirmed {
  background: #d1fae5;
  color: #065f46;
}

.upcoming-status.pending {
  background: #fef3c7;
  color: #d97706;
}

/* Buttons */
.btn-primary {
  background: #667eea;
  color: white;
  border: none;
  padding: 10px 16px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-primary:hover {
  background: #5a67d8;
  transform: translateY(-1px);
}

.btn-secondary {
  background: white;
  color: #334155;
  border: 1px solid #e2e8f0;
  padding: 10px 16px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-secondary:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
}

.btn-ghost {
  background: none;
  color: #667eea;
  border: none;
  padding: 10px 16px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-ghost:hover {
  background: #f0f4ff;
}

.btn-sm {
  padding: 8px 14px;
  font-size: 12px;
}

.card-content {
  padding: 24px;
}

.view-all-btn {
  background: none;
  border: none;
  color: #667eea;
  cursor: pointer;
  font-size: 13px;
  padding: 8px 12px;
  border-radius: 8px;
  transition: all 0.2s;
  font-weight: 600;
}

.view-all-btn:hover {
  background: #f0f4ff;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 48px 24px;
  color: #94a3b8;
}

.empty-state svg {
  width: 48px;
  height: 48px;
  margin: 0 auto 12px;
  opacity: 0.5;
}

.empty-state p {
  margin: 0;
  font-size: 14px;
  font-weight: 500;
}

/* Activity List */
.activity-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
  max-height: 400px;
  overflow-y: auto;
}

.activity-item {
  display: flex;
  gap: 12px;
  padding: 16px;
  border-radius: 12px;
  border: 1px solid #f1f5f9;
  transition: all 0.2s;
}

.activity-item:hover {
  background: #f8fafc;
  border-color: #e2e8f0;
}

.activity-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  font-size: 18px;
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
  margin-bottom: 6px;
}

.activity-header h4 {
  font-size: 14px;
  font-weight: 600;
  margin: 0;
  color: #0f172a;
}

.activity-time {
  font-size: 12px;
  color: #94a3b8;
  flex-shrink: 0;
  font-weight: 500;
}

.activity-content p {
  font-size: 13px;
  color: #64748b;
  margin: 0;
  line-height: 1.5;
}

/* Alerts */
.alert-badge {
  background: #ef4444;
  color: white;
  padding: 4px 8px;
  border-radius: 50%;
  font-size: 11px;
  font-weight: 700;
  min-width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.alerts-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
  max-height: 400px;
  overflow-y: auto;
}

.alert-item {
  padding: 16px;
  border-radius: 12px;
  border-left: 4px solid;
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
  margin-bottom: 6px;
}

.alert-item h4 {
  font-size: 14px;
  font-weight: 600;
  margin: 0;
  color: #0f172a;
}

.alert-time {
  font-size: 12px;
  color: #64748b;
  flex-shrink: 0;
  font-weight: 500;
}

.alert-item p {
  font-size: 13px;
  color: #475569;
  margin: 0;
  line-height: 1.5;
}

/* Responsive */
@media (max-width: 1200px) {
  .content-grid {
    grid-template-columns: 1fr;
  }
  
  .metrics-grid {
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  }

  .appointments-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .dashboard-header {
    flex-direction: column;
    gap: 20px;
  }
  
  .dashboard-title {
    font-size: 24px;
  }
  
  .metrics-grid {
    grid-template-columns: 1fr;
  }

  .tabs-nav {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }

  .tab-btn {
    white-space: nowrap;
  }

  .appointments-grid {
    grid-template-columns: 1fr;
  }

  .stats-overview {
    grid-template-columns: 1fr;
  }

  .appointment-footer,
  .patient-actions {
    flex-direction: column;
  }

  .btn-primary,
  .btn-secondary,
  .btn-ghost {
    width: 100%;
  }
}
</style>