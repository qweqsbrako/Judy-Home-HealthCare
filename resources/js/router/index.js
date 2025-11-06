import { createRouter, createWebHistory } from 'vue-router';
import { checkAuth } from '../utils/api'; // Import the checkAuth function

const routes = [
  {
    path: '/',
    redirect: '/login'
  },
  
  // Authentication Routes (No Layout)
  {
    path: '/login',
    name: 'login',
    component: () => import('../pages/auth/Login.vue'),
    meta: { requiresGuest: true }
  },
  {
    path: '/two-factor',
    name: 'two-factor',
    component: () => import('../pages/auth/TwoFactor.vue'),
    meta: { requiresGuest: true }
  },
  {
    path: '/forgot-password',
    name: 'forgot-password',
    component: () => import('../pages/auth/ForgotPassword.vue'),
    meta: { requiresGuest: true }
  },
  {
    path: '/reset-password',
    name: 'reset-password',
    component: () => import('../pages/auth/PasswordReset.vue'),
    meta: { requiresGuest: true }
  },

  // Protected Routes (With Layout)
  {
    path: '/dashboard',
    name: 'dashboard',
    component: () => import('../pages/Dashboard.vue'),
    meta: { 
      requiresAuth: true,
      roles: ['patient', 'nurse', 'doctor', 'admin', 'superadmin']
    }
  },

  // User Management Routes
  {
    path: '/all/users/',
    name: 'users',
    component: () => import('../pages/UserManagement/Users.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'superadmin'] }
  },
  {
    path: '/all/nurses',
    name: 'users-nurses',
    component: () => import('../pages/UserManagement/Nurses.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'superadmin'] }
  },
  {
    path: '/all/patients',
    name: 'users-patients',
    component: () => import('../pages/UserManagement/Patients.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'superadmin','nurse','doctor'] }
  },
  {
    path: '/all/doctors',
    name: 'users-doctors',
    component: () => import('../pages/UserManagement/Doctors.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'superadmin'] }
  },
  {
    path: '/all/pending-verification',
    name: 'users-pending',
    component: () => import('../pages/UserManagement/PendingVerification.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'superadmin'] }
  },

  // Care Management Routes
  {
    path: '/care/plans',
    name: 'CarePlans',
    component: () => import('../pages/CareManagement/CarePlan.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'doctor', 'superadmin','nurse','patient'] }
  },
  {
    path: '/care/assignments', 
    name: 'CareAssignments',
    component: () => import('../pages/CareManagement/CareAssignment.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'nurse', 'superadmin'] }
  },
  {
    path: '/care/schedules',
    name: 'CareSchedules', 
    component: () => import('../pages/CareManagement/Schedule.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'nurse', 'doctor' ,'patient', 'superadmin'] }
  },

  //Time Tracking Routes
  {
    path: '/schedule/time-tracking',
    name: 'time-tracking',
    component: () => import('../pages/TimeTracking.vue'),
    meta: { requiresAuth: true, roles: ['nurse', 'admin', 'superadmin'] }
  },

  // Health Data Routes
  {
    path: '/care/patient/daily-progress',
    name: 'Dialy Progress',
    component: () => import('../pages/CareManagement/DailyProgress.vue'),
    meta: { requiresAuth: true, roles: ['nurse', 'doctor', 'patient', 'admin', 'superadmin'] }
  },
  {
    path: '/care/patient/assessment',
    name: 'Patient Initial Assessment',
    component: () => import('../pages/CareManagement/MedicalAssessment.vue'),
    meta: { requiresAuth: true, roles: ['nurse', 'doctor', 'patient', 'admin', 'superadmin'] }
  },

  {
    path: '/care/patient/requests',
    name: 'Care Request',
    component: () => import('../pages/CareManagement/CareRequests.vue'),
    meta: { requiresAuth: true, roles: ['nurse', 'doctor', 'patient', 'admin', 'superadmin'] }
  },

  // Billing & Payment Routes
  {
    path: '/billing/my-bills',
    name: 'my-bills',
    //component: () => import('../pages/billing/MyBills.vue'),
    meta: { requiresAuth: true, roles: ['patient'] }
  },
  {
    path: '/billing/payment-methods',
    name: 'payment-methods',
    //component: () => import('../pages/billing/PaymentMethods.vue'),
    meta: { requiresAuth: true, roles: ['patient'] }
  },
  {
    path: '/billing/invoices',
    name: 'billing-invoices',
    //component: () => import('../pages/billing/Invoices.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'superadmin'] }
  },
  {
    path: '/billing/payments',
    name: 'billing-payments',
    //component: () => import('../pages/billing/PaymentHistory.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'superadmin'] }
  },
  {
    path: '/billing/medication-store',
    name: 'medication-store',
    //component: () => import('../pages/billing/MedicationStore.vue'),
    meta: { requiresAuth: true, roles: ['patient', 'admin', 'superadmin'] }
  },

  //Transportation Routes
  {
    path: '/transport/requests',
    name: 'transport-requests',
    component: () => import('../pages/Transportation/Transport.vue'),
    meta: { requiresAuth: true, roles: ['patient', 'admin', 'superadmin','nurse'] }
  },
  {
    path: '/transport/vehicles',
    name: 'transport-vehicles',
    component: () => import('../pages/Transportation/Vehicles.vue'),
    meta: { requiresAuth: true, roles: ['patient', 'admin', 'superadmin','nurse'] }
  },
  {
    path: '/transport/drivers',
    name: 'transport-drivers',
    component: () => import('../pages/Transportation/Drivers.vue'),
    meta: { requiresAuth: true, roles: ['patient', 'admin', 'superadmin','nurse'] }
  },

  {
    path: '/transport/assignments',
    name: 'transport-driver-vehicle-assignments',
    component: () => import('../pages/Transportation/DriverVehicleAssignments.vue'),
    meta: { requiresAuth: true, roles: ['patient', 'admin', 'superadmin','nurse'] }
  },
  
  // Quality Assurance Routes
  {
    path: '/quality/assurance',
    name: 'quality-assurance',
    component: () => import('../pages/QualityAssurance/QualityReporting.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'superadmin'] }
  },
  // Reports & Analytics Routes
  {
    path: '/reports/users',
    name: 'reports-users',
    component: () => import('../pages/Reports/UserManagementReports.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'superadmin'] }
  },
  {
    path: '/reports/health-transport',
    name: 'reports-health-transport',
    component: () => import('../pages/Reports/HealthAndTransportReports.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'superadmin'] }
  },
  {
    path: '/reports/care-nurse',
    name: 'reports-care-nurse',
    component: () => import('../pages/Reports/CareAndNurseReports.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'superadmin'] }
  },
  {
    path: '/reports/financial',
    name: 'reports-financial',
    component: () => import('../pages/Reports/FinancialReports.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'superadmin'] }
  },
  {
    path: '/reports/transport',
    name: 'reports-transport',
    component: () => import('../pages/Reports/TransportReports.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'superadmin'] }
  },

  // Settings Routes
   {
    path: '/settings/roles-permissions',
    name: 'roles-permissions',
    component: () => import('../pages/Settings/Roles&Permissions.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/settings/profile',
    name: 'settings-profile',
    component: () => import('../pages/Settings/ProfileSettings.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/settings/security',
    name: 'settings-security',
    //component: () => import('../pages/settings/SecuritySettings.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/settings/notifications',
    name: 'settings-notifications',
    //component: () => import('../pages/settings/NotificationSettings.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/settings/system',
    name: 'settings-system',
    //component: () => import('../pages/settings/SystemSettings.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'superadmin'] }
  },

  // Catch-all route for 404 errors
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    //component: () => import('../pages/NotFound.vue')
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) {
      return savedPosition;
    } else {
      return { top: 0 };
    }
  }
});

// Store for cached user data
let cachedUser = null;
let authCheckInProgress = false;

/**
 * Get current authenticated user via session
 * This checks the session cookie, not localStorage
 */
async function getCurrentUser(forceRefresh = false) {
  // Return cached user if available and not forcing refresh
  if (cachedUser && !forceRefresh) {
    return cachedUser;
  }

  // Prevent multiple simultaneous auth checks
  if (authCheckInProgress) {
    // Wait a bit and return cached user
    await new Promise(resolve => setTimeout(resolve, 100));
    return cachedUser;
  }

  try {
    authCheckInProgress = true;
    const response = await checkAuth(); // Uses session cookies
    
    if (response && response.success && response.data) {
      cachedUser = response.data;
      return cachedUser;
    }
    
    cachedUser = null;
    return null;
  } catch (error) {
    console.error('Error checking authentication:', error);
    cachedUser = null;
    return null;
  } finally {
    authCheckInProgress = false;
  }
}

/**
 * Clear cached user data (call this on logout)
 */
export function clearAuthCache() {
  cachedUser = null;
}

// Make clearAuthCache available globally for api.js logout
if (typeof window !== 'undefined') {
  window.clearRouterAuthCache = clearAuthCache;
}

// Navigation guard to check authentication
router.beforeEach(async (to, from, next) => {
  console.log('Router guard: Checking route:', to.path);

  // Skip auth check for guest routes
  if (to.meta.requiresGuest && from.meta.requiresGuest) {
    console.log('👥 Guest-to-guest navigation, skipping auth check entirely');
    next();
    return;
  }

  // Check authentication for protected routes
  if (to.meta.requiresAuth) {
    console.log('Route requires auth, checking session...');
    const user = await getCurrentUser();
    
    if (!user) {
      console.log('No authenticated user, redirecting to login');
      next('/login');
      return;
    }

    console.log('User authenticated:', user.email, 'Role:', user.role);

    // Check role-based access
    if (to.meta.roles && to.meta.roles.length > 0) {
      const hasRequiredRole = to.meta.roles.includes(user.role);
      
      if (!hasRequiredRole) {
        console.log('User does not have required role, redirecting to dashboard');
        next('/dashboard');
        return;
      }
    }

    console.log('Auth check passed, proceeding to route');
  }

  // Proceed normally
  next();
});

// Error handling for navigation failures
router.onError((error) => {
  console.error('Router error:', error);
});

export default router;