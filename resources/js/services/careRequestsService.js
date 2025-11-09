/**
 * Care Requests Service
 * Handles all care request management API calls
 */
import { apiGet, apiPost } from '../utils/api';

// ==================== CARE REQUESTS MANAGEMENT ====================

/**
 * Get all care requests with pagination and filters
 * @param {Object} params - Optional parameters (page, per_page, search, status, care_type, urgency_level)
 */
export async function getCareRequests(params = {}) {
  const queryParams = new URLSearchParams();
  
  if (params.page) {
    queryParams.append('page', params.page);
  }
  
  if (params.per_page) {
    queryParams.append('per_page', params.per_page);
  }
  
  if (params.search) {
    queryParams.append('search', params.search);
  }
  
  if (params.status && params.status !== 'all') {
    queryParams.append('status', params.status);
  }
  
  if (params.care_type && params.care_type !== 'all') {
    queryParams.append('care_type', params.care_type);
  }
  
  if (params.urgency_level && params.urgency_level !== 'all') {
    queryParams.append('urgency_level', params.urgency_level);
  }
  
  const queryString = queryParams.toString();
  return apiGet(`/admin/care-requests${queryString ? '?' + queryString : ''}`);
}

/**
 * Update care request status manually
 * @param {number} requestId - Care request ID
 * @param {Object} data - Status update data {new_status, reason}
 */
export async function updateStatus(requestId, data) {
  return apiPost(`/admin/care-requests/${requestId}/update-status`, data);
}

/**
 * Get a specific care request by ID
 * @param {number} id - Care request ID
 */
export async function getCareRequest(id) {
  return apiGet(`/admin/care-requests/${id}`);
}

/**
 * Get care requests statistics
 */
export async function getStatistics() {
  return apiGet('/admin/care-requests/statistics');
}

/**
 * Get all patients for care request creation
 */
export async function getAllPatients() {
  return apiGet('/admin/care-requests/patients');
}

/**
 * Get available nurses for assignment
 * @param {Object} params - Optional parameters (care_type, region)
 */
export async function getAvailableNurses(params = {}) {
  const queryParams = new URLSearchParams();
  
  if (params.care_type) {
    queryParams.append('care_type', params.care_type);
  }
  
  if (params.region) {
    queryParams.append('region', params.region);
  }
  
  const queryString = queryParams.toString();
  return apiGet(`/admin/care-requests/nurses${queryString ? '?' + queryString : ''}`);
}

// ==================== CARE REQUEST ACTIONS ====================

/**
 * Create a new care request (admin)
 * @param {Object} data - Care request data
 */
export async function createCareRequest(data) {
  return apiPost('/admin/care-requests/create', data);
}

/**
 * Assign a nurse to a care request
 * @param {number} requestId - Care request ID
 * @param {Object} data - Assignment data {nurse_id, scheduled_at}
 */
export async function assignNurse(requestId, data) {
  return apiPost(`/admin/care-requests/${requestId}/assign-nurse`, data);
}

/**
 * Schedule an assessment for a care request
 * @param {number} requestId - Care request ID
 * @param {Object} data - Schedule data {scheduled_at}
 */
export async function scheduleAssessment(requestId, data) {
  return apiPost(`/admin/care-requests/${requestId}/schedule-assessment`, data);
}

/**
 * Mark assessment as completed
 * @param {number} requestId - Care request ID
 * @param {Object} data - Completion data {assessment_id}
 */
export async function completeAssessment(requestId, data) {
  return apiPost(`/admin/care-requests/${requestId}/complete-assessment`, data);
}

/**
 * Issue care cost to patient
 * @param {number} requestId - Care request ID
 * @param {Object} data - Care cost data {amount, care_plan_details, duration_days, sessions_per_week}
 */
export async function issueCareCost(requestId, data) {
  return apiPost(`/admin/care-requests/${requestId}/issue-care-cost`, data);
}

/**
 * Start care services
 * @param {number} requestId - Care request ID
 */
export async function startCare(requestId) {
  return apiPost(`/admin/care-requests/${requestId}/start-care`);
}

/**
 * Reject a care request
 * @param {number} requestId - Care request ID
 * @param {Object} data - Rejection data {reason}
 */
export async function rejectRequest(requestId, data) {
  return apiPost(`/admin/care-requests/${requestId}/reject`, data);
}

// ==================== EXPORT FUNCTIONS ====================

/**
 * Export care requests to Excel/CSV
 * @param {Object} filters - Optional filters (status, care_type, urgency_level, search)
 * @returns {Promise<Object>} - Returns {blob, filename}
 */
export async function exportCareRequests(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.status && filters.status !== 'all') {
    params.append('status', filters.status);
  }
  
  if (filters.care_type && filters.care_type !== 'all') {
    params.append('care_type', filters.care_type);
  }
  
  if (filters.urgency_level && filters.urgency_level !== 'all') {
    params.append('urgency_level', filters.urgency_level);
  }
  
  if (filters.search) {
    params.append('search', filters.search);
  }
  
  const queryString = params.toString();
  const url = `/admin/care-requests/export${queryString ? '?' + queryString : ''}`;
  
  const response = await fetch(url, {
    method: 'GET',
    credentials: 'include',
    headers: {
      'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
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
  let filename = `care_requests_${new Date().toISOString().split('T')[0]}.xlsx`;
  
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

// ==================== ADDITIONAL HELPER FUNCTIONS ====================

/**
 * Get care request timeline
 * @param {number} requestId - Care request ID
 */
export async function getCareRequestTimeline(requestId) {
  return apiGet(`/admin/care-requests/${requestId}/timeline`);
}

/**
 * Get payment history for a care request
 * @param {number} requestId - Care request ID
 */
export async function getPaymentHistory(requestId) {
  return apiGet(`/admin/care-requests/${requestId}/payments`);
}

/**
 * Resend payment link to patient
 * @param {number} requestId - Care request ID
 * @param {string} paymentType - Payment type (assessment_fee or care_fee)
 */
export async function resendPaymentLink(requestId, paymentType) {
  return apiPost(`/admin/care-requests/${requestId}/resend-payment-link`, {
    payment_type: paymentType
  });
}

/**
 * Add admin notes to care request
 * @param {number} requestId - Care request ID
 * @param {Object} data - Notes data {notes}
 */
export async function addAdminNotes(requestId, data) {
  return apiPost(`/admin/care-requests/${requestId}/add-notes`, data);
}

/**
 * Get care requests by patient
 * @param {number} patientId - Patient ID
 */
export async function getCareRequestsByPatient(patientId) {
  return apiGet(`/admin/care-requests?patient_id=${patientId}`);
}

/**
 * Get care requests by nurse
 * @param {number} nurseId - Nurse ID
 */
export async function getCareRequestsByNurse(nurseId) {
  return apiGet(`/admin/care-requests?nurse_id=${nurseId}`);
}

/**
 * Get care requests requiring attention
 * Returns requests that need admin action
 */
export async function getRequestsRequiringAttention() {
  const statuses = ['payment_received', 'assessment_completed', 'care_payment_received'];
  return apiGet(`/admin/care-requests?status=${statuses.join(',')}`);
}

/**
 * Get pending payments
 */
export async function getPendingPayments() {
  return apiGet('/admin/care-requests?status=pending_payment,awaiting_care_payment');
}

/**
 * Get active care requests
 */
export async function getActiveCareRequests() {
  return apiGet('/admin/care-requests?status=care_active');
}

/**
 * Bulk assign nurse to multiple requests
 * @param {Array} requestIds - Array of care request IDs
 * @param {number} nurseId - Nurse ID to assign
 */
export async function bulkAssignNurse(requestIds, nurseId) {
  return apiPost('/admin/care-requests/bulk-assign-nurse', {
    request_ids: requestIds,
    nurse_id: nurseId
  });
}

/**
 * Get care request metrics for dashboard
 * @param {Object} filters - Optional filters (date_from, date_to)
 */
export async function getCareRequestMetrics(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  const queryString = params.toString();
  return apiGet(`/admin/care-requests/metrics${queryString ? '?' + queryString : ''}`);
}

/**
 * Get revenue report for care requests
 * @param {Object} filters - Optional filters (date_from, date_to)
 */
export async function getRevenueReport(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  const queryString = params.toString();
  return apiGet(`/admin/care-requests/revenue-report${queryString ? '?' + queryString : ''}`);
}

/**
 * Get nurse workload report
 */
export async function getNurseWorkloadReport() {
  return apiGet('/admin/care-requests/nurse-workload');
}

/**
 * Get care type distribution
 * @param {Object} filters - Optional filters (date_from, date_to)
 */
export async function getCareTypeDistribution(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  const queryString = params.toString();
  return apiGet(`/admin/care-requests/care-type-distribution${queryString ? '?' + queryString : ''}`);
}

/**
 * Get assessment completion rate
 * @param {Object} filters - Optional filters (date_from, date_to)
 */
export async function getAssessmentCompletionRate(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  const queryString = params.toString();
  return apiGet(`/admin/care-requests/assessment-completion-rate${queryString ? '?' + queryString : ''}`);
}

/**
 * Refresh care request data
 * @param {number} requestId - Care request ID
 */
export async function refreshCareRequest(requestId) {
  return apiGet(`/admin/care-requests/${requestId}?refresh=true`);
}