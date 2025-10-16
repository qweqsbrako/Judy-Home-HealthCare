/**
 * Care Management & Nurse Performance Reports Service
 * Handles all care management and nurse performance reports API calls
 */
import { apiGet, apiPost } from '../utils/api';

/**
 * Get all available nurses for filtering
 */
export async function getAvailableNurses() {
  return apiGet('/users?role=nurse');
}

// ==================== CARE MANAGEMENT REPORTS ====================

/**
 * Get care plan analytics report
 * @param {Object} filters - Optional filters (date_from, date_to)
 */
export async function getCarePlanAnalytics(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/care-plan-analytics${queryString ? '?' + queryString : ''}`);
}

/**
 * Get patient care summary report
 */
export async function getPatientCareSummary() {
  return apiGet('/reports/patient-care-summary');
}

/**
 * Get care plan performance report
 * @param {Object} filters - Optional filters (date_from, date_to)
 */
export async function getCarePlanPerformance(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/care-plan-performance${queryString ? '?' + queryString : ''}`);
}

// ==================== NURSE PERFORMANCE REPORTS ====================

/**
 * Get nurse productivity report
 * @param {Object} filters - Optional filters (date_from, date_to, nurse_id)
 */
export async function getNurseProductivity(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  if (filters.nurse_id) {
    params.append('nurse_id', filters.nurse_id);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/nurse-productivity${queryString ? '?' + queryString : ''}`);
}

/**
 * Get schedule compliance report
 * @param {Object} filters - Optional filters (date_from, date_to, nurse_id)
 */
export async function getScheduleCompliance(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  if (filters.nurse_id) {
    params.append('nurse_id', filters.nurse_id);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/schedule-compliance${queryString ? '?' + queryString : ''}`);
}

/**
 * Get time tracking analytics report
 * @param {Object} filters - Optional filters (date_from, date_to, nurse_id)
 */
export async function getTimeTrackingAnalytics(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  if (filters.nurse_id) {
    params.append('nurse_id', filters.nurse_id);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/time-tracking-analytics${queryString ? '?' + queryString : ''}`);
}

// ==================== EXPORT FUNCTIONS ====================

/**
 * Export a specific care/nurse report
 * @param {string} reportType - Type of report
 * @param {Object} filters - Optional filters (date_from, date_to, nurse_id)
 * @returns {Promise<Object>} - Returns {blob, filename}
 */
export async function exportCareNurseReport(reportType, filters = {}) {
  const params = new URLSearchParams();
  
  params.append('report_type', reportType);
  params.append('format', 'csv');
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  if (filters.nurse_id) {
    params.append('nurse_id', filters.nurse_id);
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
 * Export all care management reports
 * @param {Object} filters - Optional filters (date_from, date_to)
 * @returns {Promise<Array>} - Returns array of {blob, filename} objects
 */
export async function exportAllCareReports(filters = {}) {
  const reportTypes = [
    'care_plan_analytics',
    'patient_care_summary',
    'care_plan_performance'
  ];
  
  const exportPromises = reportTypes.map((reportType, index) => {
    return new Promise((resolve) => {
      setTimeout(async () => {
        try {
          const result = await exportCareNurseReport(reportType, filters);
          resolve({ success: true, reportType, ...result });
        } catch (error) {
          resolve({ success: false, reportType, error: error.message });
        }
      }, index * 1000);
    });
  });
  
  return Promise.all(exportPromises);
}

/**
 * Export all nurse performance reports
 * @param {Object} filters - Optional filters (date_from, date_to, nurse_id)
 * @returns {Promise<Array>} - Returns array of {blob, filename} objects
 */
export async function exportAllNurseReports(filters = {}) {
  const reportTypes = [
    'nurse_productivity',
    'schedule_compliance',
    'time_tracking_analytics'
  ];
  
  const exportPromises = reportTypes.map((reportType, index) => {
    return new Promise((resolve) => {
      setTimeout(async () => {
        try {
          const result = await exportCareNurseReport(reportType, filters);
          resolve({ success: true, reportType, ...result });
        } catch (error) {
          resolve({ success: false, reportType, error: error.message });
        }
      }, index * 1000);
    });
  });
  
  return Promise.all(exportPromises);
}

// ==================== REFRESH FUNCTIONS ====================

/**
 * Refresh all care management reports
 * @param {Object} filters - Optional filters to apply to all reports
 */
export async function refreshAllCareReports(filters = {}) {
  return Promise.all([
    getCarePlanAnalytics(filters),
    getPatientCareSummary(),
    getCarePlanPerformance(filters)
  ]);
}

/**
 * Refresh all nurse performance reports
 * @param {Object} filters - Optional filters to apply to all reports
 */
export async function refreshAllNurseReports(filters = {}) {
  return Promise.all([
    getNurseProductivity(filters),
    getScheduleCompliance(filters),
    getTimeTrackingAnalytics(filters)
  ]);
}

// ==================== ADDITIONAL HELPER FUNCTIONS ====================

/**
 * Get nurse workload distribution
 * @param {Object} filters - Optional filters (date_from, date_to)
 */
export async function getNurseWorkloadDistribution(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/nurse-workload-distribution${queryString ? '?' + queryString : ''}`);
}

/**
 * Get care plan adherence metrics
 * @param {Object} filters - Optional filters (date_from, date_to, patient_id)
 */
export async function getCarePlanAdherence(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  if (filters.patient_id) {
    params.append('patient_id', filters.patient_id);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/care-plan-adherence${queryString ? '?' + queryString : ''}`);
}

/**
 * Get nurse attendance summary
 * @param {Object} filters - Optional filters (date_from, date_to, nurse_id)
 */
export async function getNurseAttendanceSummary(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  if (filters.nurse_id) {
    params.append('nurse_id', filters.nurse_id);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/nurse-attendance-summary${queryString ? '?' + queryString : ''}`);
}

/**
 * Get care quality metrics
 * @param {Object} filters - Optional filters (date_from, date_to)
 */
export async function getCareQualityMetrics(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/care-quality-metrics${queryString ? '?' + queryString : ''}`);
}

/**
 * Get nurse efficiency metrics
 * @param {Object} filters - Optional filters (date_from, date_to, nurse_id)
 */
export async function getNurseEfficiencyMetrics(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  if (filters.nurse_id) {
    params.append('nurse_id', filters.nurse_id);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/nurse-efficiency-metrics${queryString ? '?' + queryString : ''}`);
}