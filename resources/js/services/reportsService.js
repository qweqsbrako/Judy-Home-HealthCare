/**
 * Reports Service
 * Handles all user management reports API calls
 */
import { apiGet, apiPost } from '../utils/api';

/**
 * Get user activity report
 * @param {Object} filters - Optional filters (date_from, date_to, role)
 */
export async function getUserActivityReport(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  if (filters.role && filters.role !== 'all') {
    params.append('role', filters.role);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/user-activity${queryString ? '?' + queryString : ''}`);
}

/**
 * Get role distribution report
 */
export async function getRoleDistributionReport() {
  return apiGet('/reports/role-distribution');
}

/**
 * Get verification status report
 * @param {Object} filters - Optional filters (date_from, date_to)
 */
export async function getVerificationStatusReport(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/verification-status${queryString ? '?' + queryString : ''}`);
}

/**
 * Export a specific report
 * @param {string} reportType - Type of report (user_activity, role_distribution, verification_status)
 * @param {Object} filters - Optional filters (date_from, date_to, role)
 * @returns {Promise<Object>} - Returns {blob, filename}
 */
export async function exportReport(reportType, filters = {}) {
  const params = new URLSearchParams();
  
  params.append('report_type', reportType);
  params.append('format', 'csv');
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  if (filters.role && filters.role !== 'all') {
    params.append('role', filters.role);
  }
  
  const queryString = params.toString();
  const url = `/reports/export${queryString ? '?' + queryString : ''}`;
  
  const response = await fetch(url, {
    method: 'GET',
    credentials: 'include',
    headers: {
      'Accept': 'text/csv',
      'X-Requested-With': 'XMLHttpRequest',
      'X-Client-Type': 'web'
    }
  });
  
  if (!response.ok) {
    const error = await response.json().catch(() => ({
      message: `HTTP ${response.status}: ${response.statusText}`
    }));
    throw new Error(error.message || 'Export failed');
  }
  
  const contentDisposition = response.headers.get('Content-Disposition');
  let filename = `${reportType}_${new Date().toISOString().split('T')[0]}.csv`;
  
  if (contentDisposition) {
    const filenameMatch = contentDisposition.match(/filename=(.+)/);
    if (filenameMatch) {
      filename = filenameMatch[1].replace(/['"]/g, '');
    }
  }
  
  return {
    blob: await response.blob(),
    filename: filename
  };
}

/**
 * Export all reports
 * @param {Object} filters - Optional filters (date_from, date_to, role)
 * @returns {Promise<Array>} - Returns array of {blob, filename} objects
 */
export async function exportAllReports(filters = {}) {
  const reportTypes = ['user_activity', 'role_distribution', 'verification_status'];
  
  const exportPromises = reportTypes.map((reportType, index) => {
    // Stagger exports to avoid overwhelming the server
    return new Promise((resolve) => {
      setTimeout(async () => {
        try {
          const result = await exportReport(reportType, filters);
          resolve({ success: true, reportType, ...result });
        } catch (error) {
          resolve({ success: false, reportType, error: error.message });
        }
      }, index * 1000); // 1 second delay between each export
    });
  });
  
  return Promise.all(exportPromises);
}

/**
 * Verify a user
 * @param {number} userId - User ID
 */
export async function verifyUser(userId) {
  return apiPost(`/users/${userId}/verify`);
}

/**
 * Reject a user
 * @param {number} userId - User ID
 * @param {Object} data - Optional rejection data (reason)
 */
export async function rejectUser(userId, data = {}) {
  return apiPost(`/users/${userId}/reject`, data);
}

/**
 * Get report summary/overview
 */
export async function getReportsSummary() {
  return apiGet('/reports/summary');
}

/**
 * Get user growth metrics
 * @param {Object} filters - Optional filters (date_from, date_to, interval)
 */
export async function getUserGrowthMetrics(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  if (filters.interval) {
    params.append('interval', filters.interval); // daily, weekly, monthly
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/user-growth${queryString ? '?' + queryString : ''}`);
}

/**
 * Get login analytics
 * @param {Object} filters - Optional filters (date_from, date_to)
 */
export async function getLoginAnalytics(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/login-analytics${queryString ? '?' + queryString : ''}`);
}

/**
 * Get verification statistics
 * @param {Object} filters - Optional filters (date_from, date_to, role)
 */
export async function getVerificationStatistics(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  if (filters.role && filters.role !== 'all') {
    params.append('role', filters.role);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/verification-statistics${queryString ? '?' + queryString : ''}`);
}

/**
 * Get professional statistics (for doctors and nurses)
 */
export async function getProfessionalStatistics() {
  return apiGet('/reports/professional-statistics');
}

/**
 * Get pending verifications list
 * @param {Object} filters - Optional filters (role, limit)
 */
export async function getPendingVerifications(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.role && filters.role !== 'all') {
    params.append('role', filters.role);
  }
  
  if (filters.limit) {
    params.append('limit', filters.limit);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/pending-verifications${queryString ? '?' + queryString : ''}`);
}

/**
 * Get user engagement metrics
 * @param {Object} filters - Optional filters (date_from, date_to, role)
 */
export async function getUserEngagementMetrics(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  if (filters.role && filters.role !== 'all') {
    params.append('role', filters.role);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/user-engagement${queryString ? '?' + queryString : ''}`);
}

/**
 * Get retention metrics
 * @param {Object} filters - Optional filters (date_from, date_to, cohort)
 */
export async function getRetentionMetrics(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  if (filters.cohort) {
    params.append('cohort', filters.cohort);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/retention-metrics${queryString ? '?' + queryString : ''}`);
}

/**
 * Refresh all reports data
 * @param {Object} filters - Optional filters to apply to all reports
 */
export async function refreshAllReports(filters = {}) {
  return Promise.all([
    getUserActivityReport(filters),
    getRoleDistributionReport(),
    getVerificationStatusReport(filters)
  ]);
}