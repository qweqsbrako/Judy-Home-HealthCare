/**
 * Schedules Service
 * Handles all schedule management API calls
 */
import { apiGet, apiPost, apiPut, apiDelete } from '../utils/api';

/**
 * Get all schedules
 * @param {Object} filters - Optional filters (status, shift_type, nurse_id, view, search, page, per_page)
 */
export async function getSchedules(filters = {}) {
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
  
  if (filters.shift_type && filters.shift_type !== 'all') {
    params.append('shift_type', filters.shift_type);
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
  const url = `/schedules${queryString ? '?' + queryString : ''}`;
  
  console.log('getSchedules - URL:', url);
  console.log('getSchedules - Filters:', filters);
  
  return apiGet(url);
}

/**
 * Get a single schedule by ID
 * @param {number} scheduleId - Schedule ID
 */
export async function getSchedule(scheduleId) {
  return apiGet(`/schedules/${scheduleId}`);
}

/**
 * Create a new schedule
 * @param {Object} scheduleData - Schedule data
 */
export async function createSchedule(scheduleData) {
  return apiPost('/schedules', scheduleData);
}

/**
 * Update an existing schedule
 * @param {number} scheduleId - Schedule ID
 * @param {Object} scheduleData - Updated schedule data
 */
export async function updateSchedule(scheduleId, scheduleData) {
  return apiPut(`/schedules/${scheduleId}`, scheduleData);
}

/**
 * Delete a schedule
 * @param {number} scheduleId - Schedule ID
 */
export async function deleteSchedule(scheduleId) {
  return apiDelete(`/schedules/${scheduleId}`);
}

/**
 * Confirm a schedule (by nurse)
 * @param {number} scheduleId - Schedule ID
 */
export async function confirmSchedule(scheduleId) {
  return apiPost(`/schedules/${scheduleId}/confirm`);
}

/**
 * Start a shift
 * @param {number} scheduleId - Schedule ID
 */
export async function startShift(scheduleId) {
  return apiPost(`/schedules/${scheduleId}/start`);
}

/**
 * End a shift
 * @param {number} scheduleId - Schedule ID
 * @param {Object} data - End shift data (shift_notes)
 */
export async function endSchedule(scheduleId, data) {
  return apiPost(`/schedules/${scheduleId}/end`, data);
}

/**
 * Cancel a schedule
 * @param {number} scheduleId - Schedule ID
 * @param {Object} data - Cancellation data (cancellation_reason)
 */
export async function cancelSchedule(scheduleId, data) {
  return apiPost(`/schedules/${scheduleId}/cancel`, data);
}

/**
 * Send reminder for a schedule
 * @param {number} scheduleId - Schedule ID
 */
export async function sendReminder(scheduleId) {
  return apiPost(`/schedules/${scheduleId}/reminder`);
}

/**
 * Send bulk reminders
 * @param {Object} data - Bulk reminder data (schedule_ids, hours_before, nurse_id)
 */
export async function sendBulkReminders(data) {
  return apiPost('/schedules/reminders/bulk', data);
}

/**
 * Get pending reminders
 * @param {number} hoursAhead - Hours to look ahead (default: 24)
 */
export async function getPendingReminders(hoursAhead = 24) {
  return apiGet(`/schedules/reminders/pending?hours_ahead=${hoursAhead}`);
}

/**
 * Get nurses list for schedules
 */
export async function getNurses() {
  return apiGet('/schedules/nurses');
}

/**
 * Get care plans for a specific nurse
 * @param {number} nurseId - Nurse ID
 */
export async function getCarePlansForNurse(nurseId) {
  return apiGet(`/schedules/care-plans?nurse_id=${nurseId}`);
}

/**
 * Get calendar view of schedules
 * @param {Object} params - Calendar parameters (start_date, end_date, nurse_id)
 */
export async function getCalendarView(params) {
  const queryParams = new URLSearchParams();
  
  if (params.start_date) {
    queryParams.append('start_date', params.start_date);
  }
  
  if (params.end_date) {
    queryParams.append('end_date', params.end_date);
  }
  
  if (params.nurse_id) {
    queryParams.append('nurse_id', params.nurse_id);
  }
  
  const queryString = queryParams.toString();
  return apiGet(`/schedules/calendar${queryString ? '?' + queryString : ''}`);
}

/**
 * Get schedule statistics
 */
export async function getStatistics() {
  return apiGet('/schedules/statistics');
}

/**
 * Export schedules to CSV
 * @param {Object} filters - Optional filters (status, shift_type, nurse_id, view, search, start_date, end_date)
 * @returns {Promise<Object>} - Returns {blob, filename}
 */
export async function exportSchedules(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.status && filters.status !== 'all') {
    params.append('status', filters.status);
  }
  
  if (filters.shift_type && filters.shift_type !== 'all') {
    params.append('shift_type', filters.shift_type);
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
  const url = `/schedules/export${queryString ? '?' + queryString : ''}`;
  
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
  let filename = 'schedules_export.csv';
  
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
 * Get shift types
 */
export async function getShiftTypes() {
  const response = await apiGet('/schedules');
  return response.filters?.shift_types || {};
}

/**
 * Get schedule statuses
 */
export async function getStatuses() {
  const response = await apiGet('/schedules');
  return response.filters?.statuses || {};
}