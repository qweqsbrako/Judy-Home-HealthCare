/**
 * Medical Assessments Service
 * Handles all medical assessments API calls
 */
import { apiGet, apiPost, apiPut, apiDelete } from '../utils/api';

/**
 * Get all medical assessments
 * @param {Object} filters - Optional filters
 */
export async function getMedicalAssessments(filters = {}) {
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
  
  if (filters.status && filters.status !== 'all') {
    params.append('status', filters.status);
  }
  
  if (filters.condition && filters.condition !== 'all') {
    params.append('condition', filters.condition);
  }
  
  if (filters.search) {
    params.append('search', filters.search);
  }
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  if (filters.recent_days) {
    params.append('recent_days', filters.recent_days);
  }
  
  const queryString = params.toString();
  return apiGet(`/medical-assessments${queryString ? '?' + queryString : ''}`);
}

/**
 * Get a single medical assessment by ID
 * @param {number} assessmentId - Assessment ID
 */
export async function getMedicalAssessment(assessmentId) {
  return apiGet(`/medical-assessments/${assessmentId}`);
}

/**
 * Create a new medical assessment
 * @param {Object} assessmentData - Assessment data
 */
export async function createMedicalAssessment(assessmentData) {
  return apiPost('/medical-assessments', assessmentData);
}

/**
 * Update a medical assessment
 * @param {number} assessmentId - Assessment ID
 * @param {Object} assessmentData - Updated assessment data
 */
export async function updateMedicalAssessment(assessmentId, assessmentData) {
  return apiPut(`/medical-assessments/${assessmentId}`, assessmentData);
}

/**
 * Delete a medical assessment
 * @param {number} assessmentId - Assessment ID
 */
export async function deleteMedicalAssessment(assessmentId) {
  return apiDelete(`/medical-assessments/${assessmentId}`);
}

/**
 * Get medical assessments for a specific patient
 * @param {number} patientId - Patient ID
 * @param {Object} filters - Optional filters
 */
export async function getPatientAssessments(patientId, filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.limit) {
    params.append('limit', filters.limit);
  }
  
  const queryString = params.toString();
  return apiGet(`/medical-assessments/patient/${patientId}${queryString ? '?' + queryString : ''}`);
}

/**
 * Get medical assessments statistics
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
  const url = `/medical-assessments/statistics${queryString ? '?' + queryString : ''}`;
  
  console.log('Fetching statistics from:', url);
  
  return apiGet(url);
}

/**
 * Mark assessment as reviewed
 * @param {number} assessmentId - Assessment ID
 */
export async function markAssessmentReviewed(assessmentId) {
  return apiPost(`/medical-assessments/${assessmentId}/mark-reviewed`);
}

/**
 * Export medical assessments to CSV
 * @param {Object} filters - Optional filters
 * @returns {Promise<Object>} - Returns {blob, filename}
 */
export async function exportMedicalAssessments(filters = {}) {
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
  const url = `/medical-assessments/export${queryString ? '?' + queryString : ''}`;
  
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
  let filename = 'medical_assessments_export.csv';
  
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
 * Calculate risk level for an assessment
 * @param {Object} assessment - Assessment object
 */
export function calculateRiskLevel(assessment) {
  let riskFactors = 0;

  if (assessment.general_condition === 'unstable') riskFactors += 3;
  if (assessment.hydration_status === 'dehydrated') riskFactors += 2;
  if (assessment.nutrition_status === 'malnourished') riskFactors += 2;
  if (assessment.mobility_status === 'bedridden') riskFactors += 2;
  if (assessment.has_wounds) riskFactors += 1;
  if (assessment.pain_level >= 7) riskFactors += 2;

  if (riskFactors >= 6) return 'high';
  if (riskFactors >= 3) return 'medium';
  return 'low';
}