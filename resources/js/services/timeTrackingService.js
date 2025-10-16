/**
 * Time Tracking Service
 * Handles all time tracking API calls
 */
import { apiGet, apiPost, apiPut, apiDelete } from '../utils/api';

/**
 * Get all time tracking sessions
 * @param {Object} filters - Optional filters (status, session_type, nurse_id, view, search, page, per_page)
 */
export async function getTimeTrackingSessions(filters = {}) {
  const params = new URLSearchParams();
  
  // Add pagination parameters
  if (filters.page) {
    params.append('page', filters.page);
  }
  
  if (filters.per_page) {
    params.append('per_page', filters.per_page);
  }
  
  // Add filter parameters
  if (filters.status && filters.status !== 'all') {
    params.append('status', filters.status);
  }
  
  if (filters.session_type && filters.session_type !== 'all') {
    params.append('session_type', filters.session_type);
  }
  
  if (filters.nurse_id && filters.nurse_id !== 'all') {
    params.append('nurse_id', filters.nurse_id);
  }
  
  if (filters.view && filters.view !== 'all') {
    params.append('view', filters.view);
  }
  
  if (filters.search) {
    params.append('search', filters.search);
  }
  
  // Date range filters
  if (filters.start_date) {
    params.append('start_date', filters.start_date);
  }
  
  if (filters.end_date) {
    params.append('end_date', filters.end_date);
  }
  
  const queryString = params.toString();
  const url = `/time-tracking${queryString ? '?' + queryString : ''}`;
  
  return apiGet(url);
}

/**
 * Get a single time tracking session by ID
 * @param {number} sessionId - Session ID
 */
export async function getTimeTrackingSession(sessionId) {
  return apiGet(`/time-tracking/${sessionId}`);
}

/**
 * Create a new time tracking session
 * @param {Object} sessionData - Session data
 */
export async function createTimeTrackingSession(sessionData) {
  return apiPost('/time-tracking', sessionData);
}

/**
 * Clock in to a session
 * @param {number} sessionId - Session ID
 * @param {Object} data - Clock in data (location, latitude, longitude, device_info)
 */
export async function clockIn(sessionId, data = {}) {
  return apiPost(`/time-tracking/${sessionId}/clock-in`, data);
}

/**
 * Clock out from a session
 * @param {number} sessionId - Session ID
 * @param {Object} data - Clock out data (location, work_notes, activities_performed)
 */
export async function clockOut(sessionId, data = {}) {
  return apiPost(`/time-tracking/${sessionId}/clock-out`, data);
}

/**
 * Pause a session
 * @param {number} sessionId - Session ID
 * @param {Object} data - Pause data (reason)
 */
export async function pauseSession(sessionId, data = {}) {
  return apiPost(`/time-tracking/${sessionId}/pause`, data);
}

/**
 * Resume a paused session
 * @param {number} sessionId - Session ID
 */
export async function resumeSession(sessionId) {
  return apiPost(`/time-tracking/${sessionId}/resume`);
}

/**
 * Cancel a session
 * @param {number} sessionId - Session ID
 * @param {Object} data - Cancellation data (reason)
 */
export async function cancelSession(sessionId, data) {
  return apiPost(`/time-tracking/${sessionId}/cancel`, data);
}

/**
 * Add break time to a session
 * @param {number} sessionId - Session ID
 * @param {Object} data - Break data (minutes)
 */
export async function addBreak(sessionId, data) {
  return apiPost(`/time-tracking/${sessionId}/break`, data);
}

/**
 * Get current active session
 */
export async function getCurrentSession() {
  return apiGet('/time-tracking/current');
}

/**
 * Get today's summary
 */
export async function getTodaysSummary() {
  return apiGet('/time-tracking/summary/today');
}

/**
 * Get weekly summary
 */
export async function getWeeklySummary() {
  return apiGet('/time-tracking/summary/weekly');
}

/**
 * Get time tracking statistics
 * @param {Object} filters - Optional filters (nurse_id)
 */
export async function getStatistics(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.nurse_id) {
    params.append('nurse_id', filters.nurse_id);
  }
  
  const queryString = params.toString();
  return apiGet(`/time-tracking/statistics${queryString ? '?' + queryString : ''}`);
}

/**
 * Export time tracking sessions to CSV
 * @param {Object} filters - Optional filters
 * @returns {Promise<Object>} - Returns {blob, filename}
 */
export async function exportTimeTracking(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.status && filters.status !== 'all') {
    params.append('status', filters.status);
  }
  
  if (filters.session_type && filters.session_type !== 'all') {
    params.append('session_type', filters.session_type);
  }
  
  if (filters.nurse_id && filters.nurse_id !== 'all') {
    params.append('nurse_id', filters.nurse_id);
  }
  
  if (filters.view && filters.view !== 'all') {
    params.append('view', filters.view);
  }
  
  if (filters.search) {
    params.append('search', filters.search);
  }
  
  if (filters.start_date) {
    params.append('start_date', filters.start_date);
  }
  
  if (filters.end_date) {
    params.append('end_date', filters.end_date);
  }
  
  const queryString = params.toString();
  const url = `/time-tracking/export${queryString ? '?' + queryString : ''}`;
  
  const token = localStorage.getItem('auth_token');
  
  const response = await fetch(url, {
    method: 'GET',
    credentials: 'include',
    headers: {
      'Authorization': `Bearer ${token}`,
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
  let filename = 'time_tracking_export.csv';
  
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
 * Get available nurses
 */
export async function getAvailableNurses() {
  return apiGet('/schedules/nurses');
}



/**
 * Get schedules for a nurse
 * @param {number} nurseId - Nurse ID
 */
export async function getSchedulesForNurse(nurseId) {
  const params = new URLSearchParams({
    nurse_id: nurseId,
    view: 'today'
  });
  
  return apiGet(`/schedules?${params}`);
}