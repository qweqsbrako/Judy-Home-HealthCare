/**
 * Progress Notes Service
 * Handles all progress notes API calls
 */
import { apiGet, apiPost, apiPut, apiDelete } from '../utils/api';

/**
 * Get all progress notes
 * @param {Object} filters - Optional filters
 */
export async function getProgressNotes(filters = {}) {
  const params = new URLSearchParams();
  
  // Pagination
  if (filters.page) {
    params.append('page', filters.page);
  }
  
  if (filters.per_page) {
    params.append('per_page', filters.per_page);
  }
  
  // Filters
  if (filters.patient_id && filters.patient_id !== 'all') {
    params.append('patient_id', filters.patient_id);
  }
  
  if (filters.nurse_id && filters.nurse_id !== 'all') {
    params.append('nurse_id', filters.nurse_id);
  }
  
  if (filters.condition && filters.condition !== 'all') {
    params.append('condition', filters.condition);
  }
  
  if (filters.date) {
    params.append('date', filters.date);
  }
  
  if (filters.date_type) {
    params.append('date_type', filters.date_type);
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
  
  if (filters.recent_days) {
    params.append('recent_days', filters.recent_days);
  }
  
  const queryString = params.toString();
  return apiGet(`/progress-notes${queryString ? '?' + queryString : ''}`);
}

/**
 * Get a single progress note by ID
 * @param {number} noteId - Note ID
 */
export async function getProgressNote(noteId) {
  return apiGet(`/progress-notes/${noteId}`);
}

/**
 * Create a new progress note
 * @param {Object} noteData - Note data
 */
export async function createProgressNote(noteData) {
  return apiPost('/progress-notes', noteData);
}

/**
 * Update a progress note
 * @param {number} noteId - Note ID
 * @param {Object} noteData - Updated note data
 */
export async function updateProgressNote(noteId, noteData) {
  return apiPut(`/progress-notes/${noteId}`, noteData);
}

/**
 * Delete a progress note
 * @param {number} noteId - Note ID
 */
export async function deleteProgressNote(noteId) {
  return apiDelete(`/progress-notes/${noteId}`);
}

/**
 * Duplicate a progress note
 * @param {number} noteId - Note ID to duplicate
 */
export async function duplicateProgressNote(noteId) {
  return apiPost(`/progress-notes/${noteId}/duplicate`);
}

/**
 * Get progress notes for a specific patient
 * @param {number} patientId - Patient ID
 * @param {Object} filters - Optional filters
 */
export async function getPatientNotes(patientId, filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.limit) {
    params.append('limit', filters.limit);
  }
  
  const queryString = params.toString();
  return apiGet(`/progress-notes/patient/${patientId}${queryString ? '?' + queryString : ''}`);
}

/**
 * Get progress notes created by a specific nurse
 * @param {number} nurseId - Nurse ID
 * @param {Object} filters - Optional filters
 */
export async function getNurseNotes(nurseId, filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.limit) {
    params.append('limit', filters.limit);
  }
  
  const queryString = params.toString();
  return apiGet(`/progress-notes/nurse/${nurseId}${queryString ? '?' + queryString : ''}`);
}

/**
 * Get progress notes statistics
 * @param {Object} filters - Optional filters (start_date, end_date)
 */
export async function getStatistics(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.start_date) {
    params.append('start_date', filters.start_date);
  }
  
  if (filters.end_date) {
    params.append('end_date', filters.end_date);
  }
  
  const queryString = params.toString();
  const url = `/progress-notes/statistics${queryString ? '?' + queryString : ''}`;
  
  console.log('Fetching statistics from:', url); // Debug log
  
  return apiGet(url);
}

/**
 * Export progress notes to CSV
 * @param {Object} filters - Optional filters
 * @returns {Promise<Object>} - Returns {blob, filename}
 */
export async function exportProgressNotes(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.patient_id && filters.patient_id !== 'all') {
    params.append('patient_id', filters.patient_id);
  }
  
  if (filters.nurse_id && filters.nurse_id !== 'all') {
    params.append('nurse_id', filters.nurse_id);
  }
  
  if (filters.condition && filters.condition !== 'all') {
    params.append('condition', filters.condition);
  }
  
  if (filters.date) {
    params.append('date', filters.date);
  }
  
  if (filters.date_type) {
    params.append('date_type', filters.date_type);
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
  const url = `/progress-notes/export${queryString ? '?' + queryString : ''}`;
  
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
  let filename = 'progress_notes_export.csv';
  
  if (contentDisposition) {
    const filenameMatch = contentDisposition.match(/filename="?(.+?)"?$/);
    if (filenameMatch) {
      filename = filenameMatch[1];
    }
  }
  
  return {
    blob: await response.blob(),
    filename: filename
  };
}

/**
 * Get available patients (ALL patients, no pagination)
 * @param {Object} options - Optional filters
 */
export async function getAvailablePatients(options = {}) {
  const params = new URLSearchParams();
  params.append('role', 'patient');
  
  // Optional: only get active patients
  if (options.active_only !== false) {
    params.append('active_only', 'true');
  }
  
  // Optional: only get verified patients
  if (options.verified_only !== false) {
    params.append('verified_only', 'true');
  }
  
  // Optional: search
  if (options.search) {
    params.append('search', options.search);
  }
  
  const queryString = params.toString();
  return apiGet(`/users/list?${queryString}`);
}

/**
 * Get available nurses (ALL nurses, no pagination)
 * @param {Object} options - Optional filters
 */
export async function getAvailableNurses(options = {}) {
  const params = new URLSearchParams();
  params.append('role', 'nurse');
  
  // Optional: only get active nurses
  if (options.active_only !== false) {
    params.append('active_only', 'true');
  }
  
  // Optional: only get verified nurses
  if (options.verified_only !== false) {
    params.append('verified_only', 'true');
  }
  
  // Optional: search
  if (options.search) {
    params.append('search', options.search);
  }
  
  const queryString = params.toString();
  return apiGet(`/users/list?${queryString}`);
}

/**
 * Get available doctors (ALL doctors, no pagination)
 * @param {Object} options - Optional filters
 */
export async function getAvailableDoctors(options = {}) {
  const params = new URLSearchParams();
  params.append('role', 'doctor');
  
  if (options.active_only !== false) {
    params.append('active_only', 'true');
  }
  
  if (options.verified_only !== false) {
    params.append('verified_only', 'true');
  }
  
  if (options.search) {
    params.append('search', options.search);
  }
  
  const queryString = params.toString();
  return apiGet(`/users/list?${queryString}`);
}

/**
 * Get users list by role (generic method)
 * @param {string} role - User role
 * @param {Object} options - Optional filters
 */
export async function getUsersByRole(role, options = {}) {
  const params = new URLSearchParams();
  params.append('role', role);
  
  if (options.active_only !== false) {
    params.append('active_only', 'true');
  }
  
  if (options.verified_only !== false) {
    params.append('verified_only', 'true');
  }
  
  if (options.search) {
    params.append('search', options.search);
  }
  
  const queryString = params.toString();
  return apiGet(`/users/list?${queryString}`);
}

/**
 * Debug endpoint
 * @param {Object} filters - Test filters
 */
export async function debugProgressNotes(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.test_date) {
    params.append('test_date', filters.test_date);
  }
  
  const queryString = params.toString();
  return apiGet(`/progress-notes/debug${queryString ? '?' + queryString : ''}`);
}