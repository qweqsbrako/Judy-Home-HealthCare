/**
 * Nurses Service
 * Handles all nurse management API calls
 */
import { apiGet, apiPost, apiPut, apiDelete } from '../utils/api';

/**
 * Get all nurses
 * @param {Object} filters - Optional filters (specialization, status, experience, search, page, per_page)
 */
export async function getNurses(filters = {}) {
  const params = new URLSearchParams();
  
  // Add pagination parameters
  if (filters.page) {
    params.append('page', filters.page);
  }
  
  if (filters.per_page) {
    params.append('per_page', filters.per_page);
  }
  
  // Add filter parameters
  if (filters.specialization && filters.specialization !== 'all') {
    params.append('specialization', filters.specialization);
  }
  
  if (filters.status && filters.status !== 'all') {
    params.append('status', filters.status);
  }
  
  if (filters.experience && filters.experience !== 'all') {
    params.append('experience', filters.experience);
  }
  
  if (filters.search) {
    params.append('search', filters.search);
  }
  
  const queryString = params.toString();
  const url = `/nurses${queryString ? '?' + queryString : ''}`;
  
  console.log('getNurses - URL:', url);
  console.log('getNurses - Filters:', filters);
  
  return apiGet(url);
}

/**
 * Get a single nurse by ID
 * @param {number} nurseId - Nurse ID
 */
export async function getNurse(nurseId) {
  return apiGet(`/nurses/${nurseId}`);
}

/**
 * Create a new nurse
 * @param {Object} nurseData - Nurse data
 */
export async function createNurse(nurseData) {
  return apiPost('/nurses', nurseData);
}

/**
 * Update an existing nurse
 * @param {number} nurseId - Nurse ID
 * @param {Object} nurseData - Updated nurse data
 */
export async function updateNurse(nurseId, nurseData) {
  return apiPut(`/nurses/${nurseId}`, nurseData);
}

/**
 * Delete a nurse
 * @param {number} nurseId - Nurse ID
 */
export async function deleteNurse(nurseId) {
  return apiDelete(`/nurses/${nurseId}`);
}

/**
 * Verify a nurse
 * @param {number} nurseId - Nurse ID
 */
export async function verifyNurse(nurseId) {
  return apiPost(`/nurses/${nurseId}/verify`);
}

/**
 * Suspend a nurse
 * @param {number} nurseId - Nurse ID
 */
export async function suspendNurse(nurseId) {
  return apiPost(`/nurses/${nurseId}/suspend`);
}

/**
 * Activate a nurse
 * @param {number} nurseId - Nurse ID
 */
export async function activateNurse(nurseId) {
  return apiPost(`/nurses/${nurseId}/activate`);
}

/**
 * Change nurse password
 * @param {number} nurseId - Nurse ID
 * @param {Object} passwordData - Password data (new_password)
 */
export async function changeNursePassword(nurseId, passwordData) {
  return apiPost(`/nurses/${nurseId}/change-password`, passwordData);
}

/**
 * Send password reset email to nurse
 * @param {number} nurseId - Nurse ID
 */
export async function sendPasswordResetEmail(nurseId) {
  return apiPost(`/nurses/${nurseId}/send-password-reset`);
}

/**
 * Create nurse with photo
 * @param {FormData} formData - Nurse data including photo file
 */
export async function createNurseWithPhoto(formData) {
  console.log('createNurseWithPhoto called with FormData')
  
  if (!(formData instanceof FormData)) {
    console.error('ERROR: Not FormData!', formData)
    throw new Error('Expected FormData but got something else')
  }
  
  return apiPost('/nurses', formData)
}

/**
 * Update nurse with photo
 * @param {number} nurseId - Nurse ID
 * @param {FormData} formData - Updated nurse data including photo file
 */
export async function updateNurseWithPhoto(nurseId, formData) {
  console.log('updateNurseWithPhoto called with FormData')
  
  if (!(formData instanceof FormData)) {
    console.error('ERROR: Not FormData!', formData)
    throw new Error('Expected FormData but got something else')
  }
  
  // Note: We're using POST with _method=PUT (already added in FormData)
  return apiPost(`/nurses/${nurseId}`, formData)
}

/**
 * Delete nurse photo
 * @param {number} nurseId - Nurse ID
 */
export async function deleteNursePhoto(nurseId) {
  return apiDelete(`/nurses/${nurseId}/photo`);
}

/**
 * Export nurses to CSV
 * @param {Object} filters - Optional filters (specialization, status, experience, search)
 * @returns {Promise<Object>} - Returns {blob, filename}
 */
export async function exportNurses(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.specialization && filters.specialization !== 'all') {
    params.append('specialization', filters.specialization);
  }
  
  if (filters.status && filters.status !== 'all') {
    params.append('status', filters.status);
  }
  
  if (filters.experience && filters.experience !== 'all') {
    params.append('experience', filters.experience);
  }
  
  if (filters.search) {
    params.append('search', filters.search);
  }
  
  const queryString = params.toString();
  const url = `/nurses/export${queryString ? '?' + queryString : ''}`;
  
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
  
  return {
    blob: await response.blob(),
    filename: response.headers.get('Content-Disposition')?.match(/filename=(.+)/)?.[1] || 'nurses_export.csv'
  };
}