/**
 * Patients Service
 * Handles all patient management API calls
 */
import { apiGet, apiPost, apiPut, apiDelete } from '../utils/api';

/**
 * Get all patients
 * @param {Object} filters - Optional filters (status, age_group, gender, search, page, per_page)
 */
export async function getPatients(filters = {}) {
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
  
  if (filters.age_group && filters.age_group !== 'all') {
    params.append('age_group', filters.age_group);
  }
  
  if (filters.gender && filters.gender !== 'all') {
    params.append('gender', filters.gender);
  }
  
  if (filters.search) {
    params.append('search', filters.search);
  }
  
  const queryString = params.toString();
  const url = `/patients${queryString ? '?' + queryString : ''}`;
  
  console.log('getPatients - URL:', url);
  console.log('getPatients - Filters:', filters);
  
  return apiGet(url);
}

/**
 * Get a single patient by ID
 * @param {number} patientId - Patient ID
 */
export async function getPatient(patientId) {
  return apiGet(`/patients/${patientId}`);
}

/**
 * Create a new patient
 * @param {Object} patientData - Patient data
 */
export async function createPatient(patientData) {
  return apiPost('/patients', patientData);
}

/**
 * Update an existing patient
 * @param {number} patientId - Patient ID
 * @param {Object} patientData - Updated patient data
 */
export async function updatePatient(patientId, patientData) {
  return apiPut(`/patients/${patientId}`, patientData);
}

/**
 * Delete a patient
 * @param {number} patientId - Patient ID
 */
export async function deletePatient(patientId) {
  return apiDelete(`/patients/${patientId}`);
}

/**
 * Verify a patient
 * @param {number} patientId - Patient ID
 */
export async function verifyPatient(patientId) {
  return apiPost(`/patients/${patientId}/verify`);
}

/**
 * Suspend a patient
 * @param {number} patientId - Patient ID
 */
export async function suspendPatient(patientId) {
  return apiPost(`/patients/${patientId}/suspend`);
}

/**
 * Activate a patient
 * @param {number} patientId - Patient ID
 */
export async function activatePatient(patientId) {
  return apiPost(`/patients/${patientId}/activate`);
}

/**
 * Change patient password
 * @param {number} patientId - Patient ID
 * @param {Object} passwordData - Password data (new_password)
 */
export async function changePatientPassword(patientId, passwordData) {
  return apiPost(`/patients/${patientId}/change-password`, passwordData);
}

/**
 * Send password reset email to patient
 * @param {number} patientId - Patient ID
 */
export async function sendPasswordResetEmail(patientId) {
  return apiPost(`/patients/${patientId}/send-password-reset`);
}

/**
 * Create patient with photo
 * @param {FormData} formData - Patient data including photo file
 */
export async function createPatientWithPhoto(formData) {
  console.log('createPatientWithPhoto called with FormData')
  
  if (!(formData instanceof FormData)) {
    console.error('ERROR: Not FormData!', formData)
    throw new Error('Expected FormData but got something else')
  }
  
  return apiPost('/patients', formData)
}

/**
 * Update patient with photo
 * @param {number} patientId - Patient ID
 * @param {FormData} formData - Updated patient data including photo file
 */
export async function updatePatientWithPhoto(patientId, formData) {
  console.log('updatePatientWithPhoto called with FormData')
  
  if (!(formData instanceof FormData)) {
    console.error('ERROR: Not FormData!', formData)
    throw new Error('Expected FormData but got something else')
  }
  
  return apiPost(`/patients/${patientId}`, formData)
}

/**
 * Delete patient photo
 * @param {number} patientId - Patient ID
 */
export async function deletePatientPhoto(patientId) {
  return apiDelete(`/patients/${patientId}/photo`);
}

/**
 * Export patients to CSV
 * @param {Object} filters - Optional filters (status, age_group, gender, search)
 * @returns {Promise<Object>} - Returns {blob, filename}
 */
export async function exportPatients(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.status && filters.status !== 'all') {
    params.append('status', filters.status);
  }
  
  if (filters.age_group && filters.age_group !== 'all') {
    params.append('age_group', filters.age_group);
  }
  
  if (filters.gender && filters.gender !== 'all') {
    params.append('gender', filters.gender);
  }
  
  if (filters.search) {
    params.append('search', filters.search);
  }
  
  const queryString = params.toString();
  const url = `/patients/export${queryString ? '?' + queryString : ''}`;
  
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
    filename: response.headers.get('Content-Disposition')?.match(/filename=(.+)/)?.[1] || 'patients_export.csv'
  };
}