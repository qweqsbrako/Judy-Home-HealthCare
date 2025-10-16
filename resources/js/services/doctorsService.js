/**
 * Doctors Service
 * Handles all doctor management API calls
 */
import { apiGet, apiPost, apiPut, apiDelete } from '../utils/api';

/**
 * Get all doctors
 * @param {Object} filters - Optional filters (status, specialization, experience_level, search, page, per_page)
 */
export async function getDoctors(filters = {}) {
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
  
  if (filters.specialization && filters.specialization !== 'all') {
    params.append('specialization', filters.specialization);
  }
  
  if (filters.experience_level && filters.experience_level !== 'all') {
    params.append('experience_level', filters.experience_level);
  }
  
  if (filters.search) {
    params.append('search', filters.search);
  }
  
  const queryString = params.toString();
  const url = `/doctors${queryString ? '?' + queryString : ''}`;
  
  console.log('getDoctors - URL:', url);
  console.log('getDoctors - Filters:', filters);
  
  return apiGet(url);
}

/**
 * Get a single doctor by ID
 * @param {number} doctorId - Doctor ID
 */
export async function getDoctor(doctorId) {
  return apiGet(`/doctors/${doctorId}`);
}

/**
 * Create a new doctor
 * @param {Object} doctorData - Doctor data
 */
export async function createDoctor(doctorData) {
  return apiPost('/doctors', doctorData);
}

/**
 * Update an existing doctor
 * @param {number} doctorId - Doctor ID
 * @param {Object} doctorData - Updated doctor data
 */
export async function updateDoctor(doctorId, doctorData) {
  return apiPut(`/doctors/${doctorId}`, doctorData);
}

/**
 * Delete a doctor
 * @param {number} doctorId - Doctor ID
 */
export async function deleteDoctor(doctorId) {
  return apiDelete(`/doctors/${doctorId}`);
}

/**
 * Verify a doctor
 * @param {number} doctorId - Doctor ID
 */
export async function verifyDoctor(doctorId) {
  return apiPost(`/doctors/${doctorId}/verify`);
}

/**
 * Suspend a doctor
 * @param {number} doctorId - Doctor ID
 */
export async function suspendDoctor(doctorId) {
  return apiPost(`/doctors/${doctorId}/suspend`);
}

/**
 * Activate a doctor
 * @param {number} doctorId - Doctor ID
 */
export async function activateDoctor(doctorId) {
  return apiPost(`/doctors/${doctorId}/activate`);
}

/**
 * Change doctor password
 * @param {number} doctorId - Doctor ID
 * @param {Object} passwordData - Password data (new_password)
 */
export async function changeDoctorPassword(doctorId, passwordData) {
  return apiPost(`/doctors/${doctorId}/change-password`, passwordData);
}

/**
 * Send password reset email to doctor
 * @param {number} doctorId - Doctor ID
 */
export async function sendPasswordResetEmail(doctorId) {
  return apiPost(`/doctors/${doctorId}/send-password-reset`);
}

/**
 * Create doctor with photo
 * @param {FormData} formData - Doctor data including photo file
 */
export async function createDoctorWithPhoto(formData) {
  console.log('createDoctorWithPhoto called with FormData')
  
  if (!(formData instanceof FormData)) {
    console.error('ERROR: Not FormData!', formData)
    throw new Error('Expected FormData but got something else')
  }
  
  return apiPost('/doctors', formData)
}

/**
 * Update doctor with photo
 * @param {number} doctorId - Doctor ID
 * @param {FormData} formData - Updated doctor data including photo file
 */
export async function updateDoctorWithPhoto(doctorId, formData) {
  console.log('updateDoctorWithPhoto called with FormData')
  
  if (!(formData instanceof FormData)) {
    console.error('ERROR: Not FormData!', formData)
    throw new Error('Expected FormData but got something else')
  }
  
  return apiPost(`/doctors/${doctorId}`, formData)
}

/**
 * Delete doctor photo
 * @param {number} doctorId - Doctor ID
 */
export async function deleteDoctorPhoto(doctorId) {
  return apiDelete(`/doctors/${doctorId}/photo`);
}

/**
 * Export doctors to CSV
 * @param {Object} filters - Optional filters (status, specialization, experience_level, search)
 * @returns {Promise<Object>} - Returns {blob, filename}
 */
export async function exportDoctors(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.status && filters.status !== 'all') {
    params.append('status', filters.status);
  }
  
  if (filters.specialization && filters.specialization !== 'all') {
    params.append('specialization', filters.specialization);
  }
  
  if (filters.experience_level && filters.experience_level !== 'all') {
    params.append('experience_level', filters.experience_level);
  }
  
  if (filters.search) {
    params.append('search', filters.search);
  }
  
  const queryString = params.toString();
  const url = `/doctors/export${queryString ? '?' + queryString : ''}`;
  
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
    filename: response.headers.get('Content-Disposition')?.match(/filename=(.+)/)?.[1] || 'doctors_export.csv'
  };
}