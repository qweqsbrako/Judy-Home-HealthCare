<template>
  <div class="main-layout">
    <!-- Modern Sidebar -->
    <aside class="sidebar" :class="{ 'sidebar-collapsed': sidebarCollapsed }">
      <!-- Logo Section -->
      <div class="sidebar-header">
        <div class="logo-container" v-show="!sidebarCollapsed">
          <div class="logo-text">Judy HomeCare</div>
        </div>
        <button @click="toggleSidebar" class="sidebar-collapse-btn">
          <svg v-if="sidebarCollapsed" viewBox="0 0 24 24" fill="currentColor">
            <path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/>
          </svg>
          <svg v-else viewBox="0 0 24 24" fill="currentColor">
            <path d="M3 18h13v-2H3v2zm0-5h10v-2H3v2zm0-7v2h13V6H3zm18 9.59L17.42 12 21 8.41 19.59 7l-5 5 5 5L21 15.59z"/>
          </svg>
        </button>
      </div>

      <!-- User Profile Section -->
      <div class="user-profile" v-show="!sidebarCollapsed">
        <div class="profile-avatar">
          <img :src="user.avatar_url" :alt="user.full_name" />
          <div class="status-indicator" :class="user.is_active ? 'status-online' : 'status-offline'"></div>
        </div>
        <div class="profile-info">
          <h3 class="profile-name">{{ user.full_name }}</h3>
          <span class="badge role-badge" :class="getRoleBadgeClass(user.role)">{{ formatRole(user.role) }}</span>
          <p class="last-login">Last login: {{ formatDate(user.last_login_at) }}</p>
        </div>
      </div>

      <!-- Navigation Menu -->
      <nav class="sidebar-nav">
        <ul class="nav-menu">
          <!-- Dashboard -->
          <li class="nav-item">
            <router-link to="/dashboard" class="nav-link" active-class="nav-link-active">
              <svg class="nav-icon" viewBox="0 0 24 24" fill="currentColor">
                <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/>
              </svg>
              <span v-show="!sidebarCollapsed">Dashboard</span>
            </router-link>
          </li>

          <!-- User Management (Admin/Super Admin) -->
          <li v-if="canAccess(['admin', 'superadmin'])" class="nav-item nav-dropdown">
            <a @click="toggleDropdown('users')" class="nav-link nav-dropdown-toggle">
              <svg class="nav-icon" viewBox="0 0 24 24" fill="currentColor">
                <path d="M16 7c0-2.21-1.79-4-4-4s-4 1.79-4 4 1.79 4 4 4 4-1.79 4-4zM12 14c-3.31 0-6 2.69-6 6v1h12v-1c0-3.31-2.69-6-6-6z"/>
              </svg>
              <span v-show="!sidebarCollapsed">User Management</span>
              <svg v-show="!sidebarCollapsed" class="nav-dropdown-arrow" :class="{ 'nav-dropdown-arrow-rotated': dropdowns.users }" viewBox="0 0 24 24" fill="currentColor">
                <path d="M7.41 8.84L12 13.42l4.59-4.58L18 10.25l-6 6-6-6z"/>
              </svg>
            </a>
            <ul v-show="dropdowns.users && !sidebarCollapsed" class="nav-dropdown-menu">
              <li><router-link to="/all/users" class="nav-dropdown-link">All Users</router-link></li>
              <li><router-link to="/all/nurses" class="nav-dropdown-link">Nurses</router-link></li>
              <li><router-link to="/all/patients" class="nav-dropdown-link">Patients</router-link></li>
              <li><router-link to="/all/doctors" class="nav-dropdown-link">Doctors</router-link></li>
              <li><router-link to="/all/pending-verification" class="nav-dropdown-link">Pending Verification</router-link></li>
            </ul>
          </li>

          <!-- Care Management -->
          <li v-if="canAccess(['nurse', 'doctor', 'admin', 'superadmin','patient'])" class="nav-item nav-dropdown">
            <a @click="toggleDropdown('care')" class="nav-link nav-dropdown-toggle">
              <svg class="nav-icon" viewBox="0 0 24 24" fill="currentColor">
                <path d="M19 8l-4 4h3c0 3.31-2.69 6-6 6-1.01 0-1.97-.25-2.8-.7l-1.46 1.46C8.97 19.54 10.43 20 12 20c4.42 0 8-3.58 8-8h3l-4-4zM6 12c0-3.31 2.69-6 6-6 1.01 0 1.97.25 2.8.7l1.46-1.46C15.03 4.46 13.57 4 12 4c-4.42 0-8 3.58-8 8H1l4 4 4-4H6z"/>
              </svg>
              <span v-show="!sidebarCollapsed">Care Management</span>
              <svg v-show="!sidebarCollapsed" class="nav-dropdown-arrow" :class="{ 'nav-dropdown-arrow-rotated': dropdowns.care }" viewBox="0 0 24 24" fill="currentColor">
                <path d="M7.41 8.84L12 13.42l4.59-4.58L18 10.25l-6 6-6-6z"/>
              </svg>
            </a>
            <ul v-show="dropdowns.care && !sidebarCollapsed" class="nav-dropdown-menu">
              <li v-if="canAccess(['nurse','doctor'])"><router-link to="/users/patients" class="nav-dropdown-link">My Patients</router-link></li>
              <li v-if="hasPermission('care_management.view')"><router-link to="/care/plans" class="nav-dropdown-link">Care Plans</router-link></li>
              <li v-if="hasPermission('care_management.schedules.view')"><router-link to="/care/schedules" class="nav-dropdown-link">Schedules</router-link></li>
              <li v-if="hasPermission('time_tracking.view')">
                <router-link to="/schedule/time-tracking" class="nav-dropdown-link">Time Tracking</router-link>
              </li>
              <li><router-link to="/care/patient/daily-progress" class="nav-dropdown-link">Daily Progress</router-link></li>
              <li><router-link to="/care/patient/assessment" class="nav-dropdown-link">Patient Initial Assessment</router-link></li>
            </ul>
          </li>

          <!-- Transportation Management -->
          <li v-if="canAccess(['admin', 'superadmin','doctor','nurse'])" class="nav-item nav-dropdown">
            <a @click="toggleDropdown('transport')" class="nav-link nav-dropdown-toggle">
              <svg class="nav-icon" viewBox="0 0 24 24" fill="currentColor">
                <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11C5.84 5 5.28 5.42 5.08 6.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-1.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/>
              </svg>
              <span v-show="!sidebarCollapsed">Transportation</span>
              <svg v-show="!sidebarCollapsed" class="nav-dropdown-arrow" :class="{ 'nav-dropdown-arrow-rotated': dropdowns.transport }" viewBox="0 0 24 24" fill="currentColor">
                <path d="M7.41 8.84L12 13.42l4.59-4.58L18 10.25l-6 6-6-6z"/>
              </svg>
            </a>
            <ul v-show="dropdowns.transport && !sidebarCollapsed" class="nav-dropdown-menu">
              <li v-if="hasPermission('transportation.drivers.manage')"><router-link to="/transport/drivers" class="nav-dropdown-link">Drivers</router-link></li>
              <li v-if="hasPermission('transportation.vehicles.manage')"><router-link to="/transport/vehicles" class="nav-dropdown-link">Vehicles</router-link></li>
              <li v-if="hasPermission('transportation.requests')"><router-link to="/transport/requests" class="nav-dropdown-link">Transport Requests</router-link></li>
            </ul>
          </li>

          <!-- Payments & Billing -->
          <li v-if="canAccess(['patient', 'admin', 'superadmin'])" class="nav-item nav-dropdown">
            <a @click="toggleDropdown('billing')" class="nav-link nav-dropdown-toggle">
              <svg class="nav-icon" viewBox="0 0 24 24" fill="currentColor">
                <path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/>
              </svg>
              <span v-show="!sidebarCollapsed">Payments & Billing</span>
              <svg v-show="!sidebarCollapsed" class="nav-dropdown-arrow" :class="{ 'nav-dropdown-arrow-rotated': dropdowns.billing }" viewBox="0 0 24 24" fill="currentColor">
                <path d="M7.41 8.84L12 13.42l4.59-4.58L18 10.25l-6 6-6-6z"/>
              </svg>
            </a>
            <ul v-show="dropdowns.billing && !sidebarCollapsed" class="nav-dropdown-menu">
              <li v-if="canAccess(['patient'])"><router-link to="/billing/my-bills" class="nav-dropdown-link">My Bills</router-link></li>
              <li v-if="canAccess(['patient'])"><router-link to="/billing/payment-methods" class="nav-dropdown-link">Payment Methods</router-link></li>
              <li v-if="canAccess(['admin', 'superadmin'])"><router-link to="/billing/invoices" class="nav-dropdown-link">All Invoices</router-link></li>
              <li v-if="canAccess(['admin', 'superadmin'])"><router-link to="/billing/payments" class="nav-dropdown-link">Payment History</router-link></li>
              <li><router-link to="/billing/medication-store" class="nav-dropdown-link">Medication Store</router-link></li>
            </ul>
          </li>
        
          <!-- Reports & Analytics -->
          <li v-if="canAccess(['admin', 'superadmin'])" class="nav-item nav-dropdown">
            <a @click="toggleDropdown('reports')" class="nav-link nav-dropdown-toggle">
              <svg class="nav-icon" viewBox="0 0 24 24" fill="currentColor">
                <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/>
              </svg>
              <span v-show="!sidebarCollapsed">Reports & Analytics</span>
              <svg v-show="!sidebarCollapsed" class="nav-dropdown-arrow" :class="{ 'nav-dropdown-arrow-rotated': dropdowns.reports }" viewBox="0 0 24 24" fill="currentColor">
                <path d="M7.41 8.84L12 13.42l4.59-4.58L18 10.25l-6 6-6-6z"/>
              </svg>
            </a>
            <ul v-show="dropdowns.reports && !sidebarCollapsed" class="nav-dropdown-menu">
              <li><router-link to="/quality/assurance" class="nav-dropdown-link">Quality Assurance</router-link></li>
              <li><router-link to="/reports/users" class="nav-dropdown-link">Users Reports</router-link></li>
              <li><router-link to="/reports/health-transport" class="nav-dropdown-link">Health Reports</router-link></li>
              <li><router-link to="/reports/care-nurse" class="nav-dropdown-link">Care & Nurse Reports</router-link></li>
              <li><router-link to="/reports/financial" class="nav-dropdown-link">Financial Report</router-link></li>
              <li><router-link to="/reports/transport" class="nav-dropdown-link">Transport Reports</router-link></li>
            </ul>
          </li>

          <!-- Settings -->
          <li class="nav-item nav-dropdown">
            <a @click="toggleDropdown('settings')" class="nav-link nav-dropdown-toggle">
              <svg class="nav-icon" viewBox="0 0 24 24" fill="currentColor">
                <path d="M19.14,12.94c0.04-0.3,0.06-0.61,0.06-0.94c0-0.32-0.02-0.64-0.07-0.94l2.03-1.58c0.18-0.14,0.23-0.41,0.12-0.61 l-1.92-3.32c-0.12-0.22-0.37-0.29-0.59-0.22l-2.39,0.96c-0.5-0.38-1.03-0.7-1.62-0.94L14.4,2.81c-0.04-0.24-0.24-0.41-0.48-0.41 h-3.84c-0.24,0-0.43,0.17-0.47,0.41L9.25,5.35C8.66,5.59,8.12,5.92,7.63,6.29L5.24,5.33c-0.22-0.08-0.47,0-0.59,0.22L2.74,8.87 C2.62,9.08,2.66,9.34,2.86,9.48l2.03,1.58C4.84,11.36,4.82,11.69,4.82,12s0.02,0.64,0.07,0.94l-2.03,1.58 c-0.18,0.14-0.23,0.41-0.12,0.61l1.92,3.32c0.12,0.22,0.37,0.29,0.59,0.22l2.39-0.96c0.5,0.38,1.03,0.7,1.62,0.94l0.36,2.54 c0.05,0.24,0.24,0.41,0.48,0.41h3.84c0.24,0,0.44-0.17,0.47-0.41l0.36-2.54c0.59-0.24,1.13-0.56,1.62-0.94l2.39,0.96 c0.22,0.08,0.47,0,0.59-0.22l1.92-3.32c0.12-0.22,0.07-0.47-0.12-0.61L19.14,12.94z M12,15.6c-1.98,0-3.6-1.62-3.6-3.6 s1.62-3.6,3.6-3.6s3.6,1.62,3.6,3.6S13.98,15.6,12,15.6z"/>
              </svg>
              <span v-show="!sidebarCollapsed">Settings</span>
              <svg v-show="!sidebarCollapsed" class="nav-dropdown-arrow" :class="{ 'nav-dropdown-arrow-rotated': dropdowns.settings }" viewBox="0 0 24 24" fill="currentColor">
                <path d="M7.41 8.84L12 13.42l4.59-4.58L18 10.25l-6 6-6-6z"/>
              </svg>
            </a>
            <ul v-show="dropdowns.settings && !sidebarCollapsed" class="nav-dropdown-menu">
              <li v-if="canAccess(['admin', 'superadmin'])"><router-link to="/settings/roles-permissions" class="nav-dropdown-link">Roles & Permissions</router-link></li>
              <li><router-link to="/settings/profile" class="nav-dropdown-link">Profile Settings</router-link></li>
            </ul>
          </li>
        </ul>
      </nav>

      <!-- Logout Section -->
      <div class="sidebar-footer">
        <button @click="logout" class="sidebar-logout-btn">
          <svg viewBox="0 0 24 24" fill="currentColor">
            <path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.59L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/>
          </svg>
          <span v-show="!sidebarCollapsed">Logout</span>
        </button>
      </div>
    </aside>

    <!-- Main Content Area -->
    <main class="main-content" :class="{ 'main-content-expanded': sidebarCollapsed }">
      <!-- Top Header -->
      <header class="top-header">
        <div class="header-left">
          <button @click="toggleSidebar" class="header-menu-btn">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/>
            </svg>
          </button>
          <h1 class="page-title">{{ pageTitle }}</h1>
        </div>
        
        <div class="header-right">
          <!-- Notifications -->
          <div class="header-notification">
            <button @click="toggleNotifications" class="header-notification-btn">
              <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/>
              </svg>
              <span v-if="unreadNotifications > 0" class="notification-badge">{{ unreadNotifications }}</span>
            </button>
          </div>

          <!-- User Menu -->
          <div class="header-user-menu">
            <button @click="toggleUserMenu" class="header-user-btn">
              <img :src="user.avatar_url" :alt="user.full_name" class="user-avatar">
              <span class="user-name">{{ user.first_name }}</span>
              <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M7.41 8.84L12 13.42l4.59-4.58L18 10.25l-6 6-6-6z"/>
              </svg>
            </button>
            
            <!-- User Dropdown Menu -->
            <div v-show="showUserMenu" class="user-dropdown-menu">
              <div class="user-dropdown-header">
                <img :src="user.avatar_url" :alt="user.full_name" class="dropdown-user-avatar">
                <div class="dropdown-user-info">
                  <h4 class="dropdown-user-name">{{ user.full_name }}</h4>
                  <p class="dropdown-user-email">{{ user.email }}</p>
                  <span class="badge dropdown-user-role" :class="getRoleBadgeClass(user.role)">
                    {{ formatRole(user.role) }}
                  </span>
                </div>
              </div>
              
              <div class="user-dropdown-divider"></div>
              
              <div class="user-dropdown-body">
                <router-link to="/settings/profile" class="user-dropdown-item" @click="showUserMenu = false">
                  <svg class="dropdown-item-icon" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                  </svg>
                  <span>My Profile</span>
                </router-link>
                
                <div class="user-dropdown-divider"></div>
                
                <button @click="logout" class="user-dropdown-item user-dropdown-logout">
                  <svg class="dropdown-item-icon" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.59L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/>
                  </svg>
                  <span>Sign Out</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </header>

      <!-- Page Content -->
      <div class="page-content">
        <slot />
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { logout as apiLogout, checkAuth } from '@/utils/api'

const router = useRouter()
const route = useRoute()

// Reactive data
const sidebarCollapsed = ref(false)
const showUserMenu = ref(false)
const dropdowns = ref({
  users: false,
  care: false,
  transport: false,
  billing: false,
  reports: false,
  settings: false
})
const unreadNotifications = ref(5)

// User data
const user = ref({
  permissions: []
})

// Computed properties
const pageTitle = computed(() => {
  const routeName = route.name
  const titleMap = {
    'dashboard': 'Dashboard Overview',
    'users': 'User Overview',
    'users-nurses': 'Nurse Overview',
    'transport-drivers': 'Drivers Management',
    'transport-vehicles': 'Vehicles Management', 
    'transport-requests': 'Transport Requests',
    'care-plans': 'Care Plans',
    'time-tracking': 'Time Tracking',
    'billing': 'Payments & Billing',
    'quality': 'Quality Assurance',
    'settings-roles-permissions': 'Roles & Permissions'
  }
  return titleMap[routeName] || 'MediCare Portal'
})

// Methods
const toggleSidebar = () => {
  sidebarCollapsed.value = !sidebarCollapsed.value
}

const toggleDropdown = (dropdown) => {
  dropdowns.value[dropdown] = !dropdowns.value[dropdown]
}

const toggleNotifications = () => {
  console.log('Toggle notifications')
}

const toggleUserMenu = () => {
  showUserMenu.value = !showUserMenu.value
}

const handleClickOutside = (event) => {
  if (!event.target.closest('.header-user-menu')) {
    showUserMenu.value = false
  }
  
  if (!event.target.closest('.nav-dropdown')) {
    Object.keys(dropdowns.value).forEach(key => {
      dropdowns.value[key] = false
    })
  }
}

const hasPermission = (permission) => {
  if (!user.value.permissions || !Array.isArray(user.value.permissions)) {
    return false
  }
  return user.value.permissions.includes(permission)
}

const canAccess = (roles) => {
  return roles.includes(user.value.role)
}

const formatRole = (role) => {
  const roleMap = {
    'patient': 'Patient',
    'nurse': 'Nurse',
    'doctor': 'Doctor',
    'admin': 'Administrator',
    'superadmin': 'Super Admin'
  }
  return roleMap[role] || role
}

const getRoleBadgeClass = (role) => {
  const badgeMap = {
    'patient': 'badge-warning',
    'nurse': 'badge-success',
    'doctor': 'badge-info',
    'admin': 'badge-primary',
    'superadmin': 'badge-danger'
  }
  return badgeMap[role] || 'badge-secondary'
}

const formatDate = (date) => {
  if (!date) return 'Never'
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const logout = async () => {
  try {
    await apiLogout()
  } catch (error) {
    console.error('Logout failed:', error)
    router.push('/login')
  }
}

const loadUserData = async () => {
  try {
    const response = await checkAuth()
    if (response && response.success && response.data) {
      user.value = {
        ...response.data,
        full_name: `${response.data.first_name} ${response.data.last_name}`,
        avatar_url: response.data.avatar_url || `https://ui-avatars.com/api/?name=${response.data.first_name}+${response.data.last_name}&color=667eea&background=f8f9fa&size=200`,
        permissions: response.data.permissions || []
      }
    } else {
      router.push('/login')
    }
  } catch (error) {
    console.error('Failed to load user data:', error)
    router.push('/login')
  }
}

// Lifecycle
onMounted(() => {
  loadUserData()
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

* {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

.main-layout {
  display: flex;
  height: 100vh;
  background: #f8fafc;
}

/* ================================================
   MODERN SIDEBAR
   ================================================ */

.sidebar {
  width: 280px;
  background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
  color: white;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: fixed;
  height: 100vh;
  overflow-y: auto;
  z-index: 1000;
  box-shadow: 4px 0 24px rgba(0,0,0,0.12);
  display: flex;
  flex-direction: column;
}

.sidebar-collapsed {
  width: 80px;
}

.sidebar::-webkit-scrollbar {
  width: 4px;
}

.sidebar::-webkit-scrollbar-thumb {
  background: rgba(255,255,255,0.2);
  border-radius: 4px;
}

/* Sidebar Header */
.sidebar-header {
  padding: 24px;
  border-bottom: 1px solid rgba(255,255,255,0.08);
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-shrink: 0;
}

.sidebar-collapsed .sidebar-header {
  padding: 16px;
  justify-content: center;
}

.logo-text {
  font-size: 24px;
  font-weight: 800;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  letter-spacing: -0.5px;
}

.sidebar-collapse-btn {
  background: rgba(255, 255, 255, 0.1);
  border: none;
  color: white;
  cursor: pointer;
  padding: 8px;
  border-radius: 8px;
  transition: all 0.2s;
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.sidebar-collapse-btn:hover {
  background: rgba(255,255,255,0.15);
  transform: scale(1.05);
}

.sidebar-collapse-btn svg {
  width: 20px;
  height: 20px;
}

/* User Profile */
.user-profile {
  padding: 24px;
  border-bottom: 1px solid rgba(255,255,255,0.08);
  text-align: center;
  flex-shrink: 0;
}

.profile-avatar {
  position: relative;
  margin-bottom: 12px;
  display: inline-block;
}

.profile-avatar img {
  width: 64px;
  height: 64px;
  border-radius: 16px;
  border: 3px solid rgba(102, 126, 234, 0.3);
}

.status-indicator {
  position: absolute;
  bottom: 2px;
  right: 2px;
  width: 14px;
  height: 14px;
  border-radius: 50%;
  border: 3px solid #1e293b;
}

.status-online {
  background: #10b981;
}

.status-offline {
  background: #6b7280;
}

.profile-name {
  font-size: 16px;
  font-weight: 600;
  margin: 0 0 8px 0;
  color: white;
  letter-spacing: -0.2px;
}

.role-badge {
  font-size: 11px;
  padding: 4px 10px;
  border-radius: 6px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.3px;
  display: inline-block;
  margin-bottom: 8px;
}

.last-login {
  font-size: 12px;
  color: rgba(255,255,255,0.5);
  margin: 0;
}

/* Navigation */
.sidebar-nav {
  flex: 1;
  padding: 16px 12px;
  overflow-y: auto;
}

.nav-menu {
  list-style: none;
  margin: 0;
  padding: 0;
}

.nav-item {
  margin-bottom: 4px;
}

.nav-link {
  display: flex;
  align-items: center;
  padding: 12px 16px;
  color: rgba(255,255,255,0.7);
  text-decoration: none;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  border-radius: 10px;
  font-weight: 500;
  font-size: 14px;
}

.nav-link:hover {
  background: rgba(255,255,255,0.08);
  color: white;
  transform: translateX(2px);
}

.nav-link-active {
  background: rgba(102, 126, 234, 0.15);
  color: white;
  position: relative;
}

.nav-link-active::before {
  content: '';
  position: absolute;
  left: 0;
  top: 50%;
  transform: translateY(-50%);
  width: 4px;
  height: 60%;
  background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
  border-radius: 0 4px 4px 0;
}

.nav-icon {
  width: 20px;
  height: 20px;
  margin-right: 12px;
  flex-shrink: 0;
}

.nav-dropdown-toggle {
  cursor: pointer;
}

.nav-dropdown-arrow {
  width: 16px;
  height: 16px;
  margin-left: auto;
  transition: transform 0.2s ease;
}

.nav-dropdown-arrow-rotated {
  transform: rotate(180deg);
}

.nav-dropdown-menu {
  list-style: none;
  margin: 4px 0 0 0;
  padding: 0;
  background: rgba(0,0,0,0.2);
  border-radius: 8px;
  overflow: hidden;
}

.nav-dropdown-link {
  display: block;
  padding: 10px 16px 10px 48px;
  color: rgba(255,255,255,0.6);
  text-decoration: none;
  font-size: 13px;
  transition: all 0.2s;
  font-weight: 500;
}

.nav-dropdown-link:hover {
  background: rgba(255,255,255,0.05);
  color: white;
}

/* Sidebar Footer */
.sidebar-footer {
  padding: 16px;
  border-top: 1px solid rgba(255,255,255,0.08);
  flex-shrink: 0;
}

.sidebar-logout-btn {
  width: 100%;
  padding: 12px;
  background: rgba(239, 68, 68, 0.1);
  border: 1px solid rgba(239, 68, 68, 0.2);
  border-radius: 10px;
  color: #f87171;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-size: 14px;
  font-weight: 600;
  transition: all 0.2s;
}

.sidebar-logout-btn:hover {
  background: rgba(239, 68, 68, 0.2);
  transform: translateY(-1px);
}

.sidebar-logout-btn svg {
  width: 18px;
  height: 18px;
}

/* ================================================
   MAIN CONTENT
   ================================================ */

.main-content {
  flex: 1;
  margin-left: 280px;
  transition: all 0.3s ease;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.main-content-expanded {
  margin-left: 80px;
}

/* Top Header */
.top-header {
  background: white;
  padding: 20px 32px;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  box-shadow: 0 1px 3px rgba(0,0,0,0.04);
  position: sticky;
  top: 0;
  z-index: 100;
  flex-shrink: 0;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 16px;
}

.header-menu-btn {
  display: none;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  padding: 10px;
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.2s;
}

.header-menu-btn:hover {
  background: #f1f5f9;
}

.header-menu-btn svg {
  width: 20px;
  height: 20px;
  color: #475569;
}

.page-title {
  font-size: 28px;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
  letter-spacing: -0.5px;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 12px;
}

.header-notification-btn {
  position: relative;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  padding: 10px;
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.2s;
}

.header-notification-btn:hover {
  background: #f1f5f9;
  transform: translateY(-1px);
}

.header-notification-btn svg {
  width: 20px;
  height: 20px;
  color: #475569;
}

.notification-badge {
  position: absolute;
  top: -4px;
  right: -4px;
  background: #ef4444;
  color: white;
  border-radius: 50%;
  width: 18px;
  height: 18px;
  font-size: 11px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  border: 2px solid white;
}

.header-user-menu {
  position: relative;
}

.header-user-btn {
  display: flex;
  align-items: center;
  gap: 10px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  padding: 6px 12px 6px 6px;
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.2s;
}

.header-user-btn:hover {
  background: #f1f5f9;
}

.user-avatar {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  border: 2px solid #e2e8f0;
}

.user-name {
  font-weight: 600;
  color: #334155;
  font-size: 14px;
}

.header-user-btn svg {
  width: 16px;
  height: 16px;
  color: #64748b;
}

/* User Dropdown */
.user-dropdown-menu {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  width: 280px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
  border: 1px solid #e2e8f0;
  overflow: hidden;
  z-index: 1000;
  animation: slideInFade 0.2s ease-out;
}

@keyframes slideInFade {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.user-dropdown-header {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 12px;
}

.dropdown-user-avatar {
  width: 56px;
  height: 56px;
  border-radius: 12px;
  border: 3px solid white;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.dropdown-user-name {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 4px 0;
  letter-spacing: -0.2px;
}

.dropdown-user-email {
  font-size: 13px;
  color: #64748b;
  margin: 0 0 8px 0;
  word-break: break-all;
}

.dropdown-user-role {
  font-size: 11px;
  padding: 4px 8px;
  font-weight: 600;
}

.user-dropdown-divider {
  height: 1px;
  background: #e2e8f0;
  margin: 8px 0;
}

.user-dropdown-body {
  padding: 8px;
}

.user-dropdown-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  color: #334155;
  text-decoration: none;
  transition: all 0.2s;
  border: none;
  background: none;
  width: 100%;
  text-align: left;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  border-radius: 8px;
}

.user-dropdown-item:hover {
  background: #f8fafc;
  color: #0f172a;
}

.user-dropdown-logout {
  color: #dc2626;
}

.user-dropdown-logout:hover {
  background: #fef2f2;
  color: #b91c1c;
}

.dropdown-item-icon {
  width: 20px;
  height: 20px;
  flex-shrink: 0;
}

/* Page Content */
.page-content {
  flex: 1;
  padding: 32px;
  background: #f8fafc;
  overflow-y: auto;
}

/* Badge Styles */
.badge {
  font-size: 11px;
  padding: 4px 10px;
  border-radius: 6px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

.badge-primary { background: #dbeafe; color: #1e40af; }
.badge-success { background: #d1fae5; color: #065f46; }
.badge-warning { background: #fef3c7; color: #92400e; }
.badge-danger { background: #fee2e2; color: #991b1b; }
.badge-info { background: #e0e7ff; color: #3730a3; }
.badge-secondary { background: #f1f5f9; color: #475569; }

/* Responsive */
@media (max-width: 1024px) {
  .sidebar {
    transform: translateX(-100%);
  }
  
  .main-content, .main-content-expanded {
    margin-left: 0;
  }
  
  .header-menu-btn {
    display: flex;
  }
}

@media (max-width: 640px) {
  .top-header {
    padding: 16px;
  }
  
  .page-content {
    padding: 16px;
  }
  
  .user-name {
    display: none;
  }
  
  .page-title {
    font-size: 20px;
  }
}
</style>