/**
 * Quality Reports Service
 * Handles all quality assurance and reporting API calls
 */
import { apiGet, apiPost, apiPut, apiDelete } from '../utils/api';

/**
 * Get quality assurance dashboard overview
 * @param {number} timeframe - Days to look back (default: 30)
 */
export async function getQualityOverview(timeframe = 30) {
  return apiGet(`/quality-reports?timeframe=${timeframe}`);
}

/**
 * Get patient feedback data
 * @param {Object} filters - Optional filters (nurse_id, rating, date_from, date_to, search, page, per_page)
 */
export async function getPatientFeedback(filters = {}) {
  const params = new URLSearchParams();
  
  // Add pagination parameters
  if (filters.page) {
    params.append('page', filters.page);
  }
  
  if (filters.per_page) {
    params.append('per_page', filters.per_page);
  }
  
  // Add filter parameters
  if (filters.nurse_id) {
    params.append('nurse_id', filters.nurse_id);
  }
  
  if (filters.rating && filters.rating !== 'all') {
    params.append('rating', filters.rating);
  }
  
  if (filters.status && filters.status !== 'all') {
    params.append('status', filters.status);
  }
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  if (filters.search) {
    params.append('search', filters.search);
  }
  
  const queryString = params.toString();
  return apiGet(`/quality-reports/patient-feedback${queryString ? '?' + queryString : ''}`);
}

/**
 * Get nurse performance data
 * @param {number} timeframe - Days to look back (default: 30)
 */
export async function getNursePerformance(timeframe = 30) {
  return apiGet(`/quality-reports/nurse-performance?timeframe=${timeframe}`);
}

/**
 * Get incident reports data
 * @param {Object} filters - Optional filters (severity, status, category, date_from, date_to, search, page, per_page)
 */
export async function getIncidentReports(filters = {}) {
  const params = new URLSearchParams();
  
  // Add pagination parameters
  if (filters.page) {
    params.append('page', filters.page);
  }
  
  if (filters.per_page) {
    params.append('per_page', filters.per_page);
  }
  
  // Add filter parameters
  if (filters.severity && filters.severity !== 'all') {
    params.append('severity', filters.severity);
  }
  
  if (filters.status && filters.status !== 'all') {
    params.append('status', filters.status);
  }
  
  if (filters.incident_type && filters.incident_type !== 'all') {
    params.append('incident_type', filters.incident_type);
  }
  
  if (filters.nurse_name) {
    params.append('nurse_name', filters.nurse_name);
  }
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  if (filters.search) {
    params.append('search', filters.search);
  }
  
  const queryString = params.toString();
  return apiGet(`/quality-reports/incident-reports${queryString ? '?' + queryString : ''}`);
}

/**
 * Get single incident report for editing
 * @param {number} incidentId - Incident ID
 */
export async function getIncidentReport(incidentId) {
  return apiGet(`/quality-reports/incident-reports/${incidentId}`);
}

/**
 * Create a new incident report
 * @param {Object} incidentData - Incident report data
 */
export async function createIncidentReport(incidentData) {
  return apiPost('/quality-reports/add-incident-report', incidentData);
}

/**
 * Update an existing incident report
 * @param {number} incidentId - Incident ID
 * @param {Object} incidentData - Updated incident data
 */
export async function updateIncidentReport(incidentId, incidentData) {
  return apiPut(`/quality-reports/incident-reports/${incidentId}`, incidentData);
}

/**
 * Delete an incident report
 * @param {number} incidentId - Incident ID
 */
export async function deleteIncidentReport(incidentId) {
  return apiDelete(`/quality-reports/incident-reports/${incidentId}`);
}

/**
 * Update incident report status
 * @param {number} incidentId - Incident ID
 * @param {Object} statusData - Status update data (status, actions_taken, follow_up_required, follow_up_date)
 */
export async function updateIncidentStatus(incidentId, statusData) {
  return apiPost(`/quality-reports/incidents/${incidentId}/update-status`, statusData);
}

/**
 * Respond to patient feedback
 * @param {number} feedbackId - Feedback ID
 * @param {Object} responseData - Response data (response_text)
 */
export async function respondToFeedback(feedbackId, responseData) {
  return apiPost(`/quality-reports/feedback/${feedbackId}/respond`, responseData);
}

/**
 * Get quality metrics data
 * @param {number} timeframe - Days to look back (default: 30)
 */
export async function getQualityMetrics(timeframe = 30) {
  return apiGet(`/quality-reports/quality-metrics?timeframe=${timeframe}`);
}

/**
 * Get users for dropdowns (patients, nurses, admins)
 */
export async function getUsers(role = null) {
  const params = new URLSearchParams();
  
  if (role) {
    params.append('role', role);
  }
  
  params.append('verified', 'true');
  
  const queryString = params.toString();
  return apiGet(`/users${queryString ? '?' + queryString : ''}`);
}

/**
 * Export quality report
 * @param {string} reportType - Type of report (patient-feedback, nurse-performance, incident-reports, quality-metrics)
 * @returns {Promise<Object>} - Returns {blob, filename}
 */
export async function exportReport(reportType) {
  const response = await fetch(`/quality-reports/export/${reportType}`, {
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
  let filename = `${reportType}_export.csv`;
  
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
 * Refresh all quality data
 */
export async function refreshData() {
  return Promise.all([
    getQualityOverview(),
    getPatientFeedback(),
    getNursePerformance(),
    getIncidentReports(),
    getQualityMetrics()
  ]);
}