/**
 * Care Plans Service
 * Handles all care plan management API calls
 */
import { apiGet, apiPost, apiPut, apiDelete } from '../utils/api';

/**
 * Get all care plans
 * @param {Object} filters - Optional filters (status, care_type, priority, search, page, per_page)
 */
export async function getCarePlans(filters = {}) {
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
  
  if (filters.care_type && filters.care_type !== 'all') {
    params.append('care_type', filters.care_type);
  }
  
  if (filters.priority && filters.priority !== 'all') {
    params.append('priority', filters.priority);
  }
  
  if (filters.search) {
    params.append('search', filters.search);
  }
  
  const queryString = params.toString();
  const url = `/care-plans${queryString ? '?' + queryString : ''}`;
  
  console.log('getCarePlans - URL:', url);
  console.log('getCarePlans - Filters:', filters);
  
  return apiGet(url);
}

/**
 * Get a single care plan by ID
 * @param {number} planId - Care Plan ID
 */
export async function getCarePlan(planId) {
  return apiGet(`/care-plans/${planId}`);
}

/**
 * Create a new care plan
 * @param {Object} planData - Care plan data
 */
export async function createCarePlan(planData) {
  return apiPost('/care-plans', planData);
}

/**
 * Update an existing care plan
 * @param {number} planId - Care Plan ID
 * @param {Object} planData - Updated care plan data
 */
export async function updateCarePlan(planId, planData) {
  return apiPut(`/care-plans/${planId}`, planData);
}

/**
 * Delete a care plan
 * @param {number} planId - Care Plan ID
 */
export async function deleteCarePlan(planId) {
  return apiDelete(`/care-plans/${planId}`);
}

/**
 * Submit care plan for approval
 * @param {number} planId - Care Plan ID
 */
export async function submitForApproval(planId) {
  return apiPost(`/care-plans/${planId}/submit-for-approval`);
}

/**
 * Approve a care plan
 * @param {number} planId - Care Plan ID
 */
export async function approveCarePlan(planId) {
  return apiPost(`/care-plans/${planId}/approve`);
}

/**
 * Complete a care plan
 * @param {number} planId - Care Plan ID
 */
export async function completeCarePlan(planId) {
  return apiPost(`/care-plans/${planId}/complete`);
}

/**
 * Activate a care plan
 * @param {number} planId - Care Plan ID
 */
export async function activateCarePlan(planId) {
  return apiPost(`/care-plans/${planId}/activate`);
}

/**
 * Put care plan on hold
 * @param {number} planId - Care Plan ID
 */
export async function holdCarePlan(planId) {
  return apiPost(`/care-plans/${planId}/hold`);
}

/**
 * Resume a care plan from hold
 * @param {number} planId - Care Plan ID
 */
export async function resumeCarePlan(planId) {
  return apiPost(`/care-plans/${planId}/resume`);
}

/**
 * Cancel a care plan
 * @param {number} planId - Care Plan ID
 */
export async function cancelCarePlan(planId) {
  return apiPost(`/care-plans/${planId}/cancel`);
}

/**
 * Get care plan statistics
 */
export async function getStatistics() {
  return apiGet('/care-plans/data/statistics');
}

/**
 * Get patients list for care plan creation
 */
export async function getPatients() {
  return apiGet('/care-plans/data/patients');
}

/**
 * Get doctors list for care plan creation
 */
export async function getDoctors() {
  return apiGet('/care-plans/data/doctors');
}

/**
 * Get nurses list for care plan creation
 */
export async function getNurses() {
  return apiGet('/care-plans/data/nurses');
}

/**
 * Find suitable nurses for a care plan
 * @param {Object} criteria - Search criteria (care_plan_id, min_match_score)
 */
export async function findSuitableNurses(criteria = {}) {
  const params = new URLSearchParams();
  
  if (criteria.care_plan_id) {
    params.append('care_plan_id', criteria.care_plan_id);
  }
  
  if (criteria.min_match_score) {
    params.append('min_match_score', criteria.min_match_score);
  }
  
  const queryString = params.toString();
  const url = `/care-plans/find-suitable-nurses${queryString ? '?' + queryString : ''}`;
  
  return apiGet(url);
}

/**
 * Export care plans to CSV
 * @param {Object} filters - Optional filters (status, care_type, priority, search)
 * @returns {Promise<Object>} - Returns {blob, filename}
 */
export async function exportCarePlans(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.status && filters.status !== 'all') {
    params.append('status', filters.status);
  }
  
  if (filters.care_type && filters.care_type !== 'all') {
    params.append('care_type', filters.care_type);
  }
  
  if (filters.priority && filters.priority !== 'all') {
    params.append('priority', filters.priority);
  }
  
  if (filters.search) {
    params.append('search', filters.search);
  }
  
  const queryString = params.toString();
  const url = `/care-plans/export${queryString ? '?' + queryString : ''}`;
  
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
    filename: response.headers.get('Content-Disposition')?.match(/filename=(.+)/)?.[1] || 'care_plans_export.csv'
  };
}