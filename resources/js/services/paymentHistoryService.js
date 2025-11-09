/**
 * Payment History Service
 * Handles all payment history API calls
 */
import { apiGet, apiPost, apiPut, apiDelete } from '../utils/api';

/**
 * Get all payments
 * @param {Object} filters - Optional filters
 */
export async function getPayments(filters = {}) {
  const params = new URLSearchParams();
  
  // Pagination
  if (filters.page) {
    params.append('page', filters.page);
  }
  
  if (filters.per_page) {
    params.append('per_page', filters.per_page);
  }
  
  // Filters
  if (filters.status && filters.status !== 'all') {
    params.append('status', filters.status);
  }
  
  if (filters.payment_type && filters.payment_type !== 'all') {
    params.append('payment_type', filters.payment_type);
  }
  
  if (filters.payment_method && filters.payment_method !== 'all') {
    params.append('payment_method', filters.payment_method);
  }
  
  if (filters.patient_id && filters.patient_id !== 'all') {
    params.append('patient_id', filters.patient_id);
  }
  
  if (filters.start_date) {
    params.append('start_date', filters.start_date);
  }
  
  if (filters.end_date) {
    params.append('end_date', filters.end_date);
  }
  
  if (filters.search) {
    params.append('search', filters.search);
  }
  
  if (filters.min_amount) {
    params.append('min_amount', filters.min_amount);
  }
  
  if (filters.max_amount) {
    params.append('max_amount', filters.max_amount);
  }
  
  const queryString = params.toString();
  return apiGet(`/payments${queryString ? '?' + queryString : ''}`);
}

/**
 * Get a single payment by ID
 * @param {number} paymentId - Payment ID
 */
export async function getPayment(paymentId) {
  return apiGet(`/payments/${paymentId}`);
}

/**
 * Get payment statistics
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
  
  if (filters.payment_type && filters.payment_type !== 'all') {
    params.append('payment_type', filters.payment_type);
  }
  
  const queryString = params.toString();
  const url = `/payments/statistics${queryString ? '?' + queryString : ''}`;
  
  console.log('Fetching payment statistics from:', url); // Debug log
  
  return apiGet(url);
}

/**
 * Export payments to CSV
 * @param {Object} filters - Optional filters
 * @returns {Promise<Object>} - Returns {blob, filename}
 */
export async function exportPayments(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.status && filters.status !== 'all') {
    params.append('status', filters.status);
  }
  
  if (filters.payment_type && filters.payment_type !== 'all') {
    params.append('payment_type', filters.payment_type);
  }
  
  if (filters.payment_method && filters.payment_method !== 'all') {
    params.append('payment_method', filters.payment_method);
  }
  
  if (filters.patient_id && filters.patient_id !== 'all') {
    params.append('patient_id', filters.patient_id);
  }
  
  if (filters.start_date) {
    params.append('start_date', filters.start_date);
  }
  
  if (filters.end_date) {
    params.append('end_date', filters.end_date);
  }
  
  if (filters.search) {
    params.append('search', filters.search);
  }
  
  if (filters.min_amount) {
    params.append('min_amount', filters.min_amount);
  }
  
  if (filters.max_amount) {
    params.append('max_amount', filters.max_amount);
  }
  
  const queryString = params.toString();
  const url = `/payments/export${queryString ? '?' + queryString : ''}`;
  
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
  let filename = 'payments_export.csv';
  
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
 * Get payments for a specific patient
 * @param {number} patientId - Patient ID
 * @param {Object} filters - Optional filters
 */
export async function getPatientPayments(patientId, filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.page) {
    params.append('page', filters.page);
  }
  
  if (filters.per_page) {
    params.append('per_page', filters.per_page);
  }
  
  if (filters.status && filters.status !== 'all') {
    params.append('status', filters.status);
  }
  
  if (filters.payment_type && filters.payment_type !== 'all') {
    params.append('payment_type', filters.payment_type);
  }
  
  const queryString = params.toString();
  return apiGet(`/payments/patient/${patientId}${queryString ? '?' + queryString : ''}`);
}

/**
 * Get payments for a specific care request
 * @param {number} careRequestId - Care Request ID
 */
export async function getCareRequestPayments(careRequestId) {
  return apiGet(`/payments/care-request/${careRequestId}`);
}

/**
 * Get payment by reference number
 * @param {string} referenceNumber - Payment reference number
 */
export async function getPaymentByReference(referenceNumber) {
  return apiGet(`/payments/reference/${referenceNumber}`);
}

/**
 * Get payment method breakdown
 * @param {Object} filters - Optional filters (start_date, end_date)
 */
export async function getPaymentMethodBreakdown(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.start_date) {
    params.append('start_date', filters.start_date);
  }
  
  if (filters.end_date) {
    params.append('end_date', filters.end_date);
  }
  
  const queryString = params.toString();
  return apiGet(`/payments/breakdown/methods${queryString ? '?' + queryString : ''}`);
}

/**
 * Get payment type breakdown (assessment vs care fees)
 * @param {Object} filters - Optional filters (start_date, end_date)
 */
export async function getPaymentTypeBreakdown(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.start_date) {
    params.append('start_date', filters.start_date);
  }
  
  if (filters.end_date) {
    params.append('end_date', filters.end_date);
  }
  
  const queryString = params.toString();
  return apiGet(`/payments/breakdown/types${queryString ? '?' + queryString : ''}`);
}

/**
 * Get revenue trends (daily/weekly/monthly)
 * @param {Object} filters - Required filters
 * @param {string} filters.period - Period type: 'daily', 'weekly', 'monthly'
 * @param {string} filters.start_date - Start date
 * @param {string} filters.end_date - End date
 */
export async function getRevenueTrends(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.period) {
    params.append('period', filters.period);
  }
  
  if (filters.start_date) {
    params.append('start_date', filters.start_date);
  }
  
  if (filters.end_date) {
    params.append('end_date', filters.end_date);
  }
  
  const queryString = params.toString();
  return apiGet(`/payments/trends/revenue${queryString ? '?' + queryString : ''}`);
}

/**
 * Get pending payments (payments awaiting completion)
 * @param {Object} filters - Optional filters
 */
export async function getPendingPayments(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.page) {
    params.append('page', filters.page);
  }
  
  if (filters.per_page) {
    params.append('per_page', filters.per_page);
  }
  
  if (filters.payment_type && filters.payment_type !== 'all') {
    params.append('payment_type', filters.payment_type);
  }
  
  const queryString = params.toString();
  return apiGet(`/payments/pending${queryString ? '?' + queryString : ''}`);
}

/**
 * Get failed payments
 * @param {Object} filters - Optional filters
 */
export async function getFailedPayments(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.page) {
    params.append('page', filters.page);
  }
  
  if (filters.per_page) {
    params.append('per_page', filters.per_page);
  }
  
  if (filters.start_date) {
    params.append('start_date', filters.start_date);
  }
  
  if (filters.end_date) {
    params.append('end_date', filters.end_date);
  }
  
  const queryString = params.toString();
  return apiGet(`/payments/failed${queryString ? '?' + queryString : ''}`);
}

/**
 * Get recent payments (last N days)
 * @param {number} days - Number of days (default: 7)
 */
export async function getRecentPayments(days = 7) {
  return apiGet(`/payments/recent?days=${days}`);
}

/**
 * Verify payment status (force check with payment provider)
 * @param {number} paymentId - Payment ID
 */
export async function verifyPaymentStatus(paymentId) {
  return apiPost(`/payments/${paymentId}/verify`);
}

/**
 * Refund a payment (admin only)
 * @param {number} paymentId - Payment ID
 * @param {Object} refundData - Refund details
 */
export async function refundPayment(paymentId, refundData) {
  return apiPost(`/payments/${paymentId}/refund`, refundData);
}

/**
 * Get available patients for filtering
 * @param {Object} options - Optional filters
 */
export async function getAvailablePatients(options = {}) {
  const params = new URLSearchParams();
  params.append('role', 'patient');
  
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
 * Get payment summary for dashboard
 * @param {Object} filters - Optional filters (start_date, end_date)
 */
export async function getPaymentSummary(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.start_date) {
    params.append('start_date', filters.start_date);
  }
  
  if (filters.end_date) {
    params.append('end_date', filters.end_date);
  }
  
  const queryString = params.toString();
  return apiGet(`/payments/summary${queryString ? '?' + queryString : ''}`);
}

/**
 * Search payments by multiple criteria
 * @param {Object} searchParams - Search parameters
 */
export async function searchPayments(searchParams = {}) {
  const params = new URLSearchParams();
  
  if (searchParams.query) {
    params.append('query', searchParams.query);
  }
  
  if (searchParams.status) {
    params.append('status', searchParams.status);
  }
  
  if (searchParams.payment_type) {
    params.append('payment_type', searchParams.payment_type);
  }
  
  if (searchParams.date_from) {
    params.append('date_from', searchParams.date_from);
  }
  
  if (searchParams.date_to) {
    params.append('date_to', searchParams.date_to);
  }
  
  const queryString = params.toString();
  return apiGet(`/payments/search${queryString ? '?' + queryString : ''}`);
}

/**
 * Get payment analytics
 * @param {Object} filters - Optional filters
 */
export async function getPaymentAnalytics(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.start_date) {
    params.append('start_date', filters.start_date);
  }
  
  if (filters.end_date) {
    params.append('end_date', filters.end_date);
  }
  
  if (filters.group_by) {
    params.append('group_by', filters.group_by);
  }
  
  const queryString = params.toString();
  return apiGet(`/payments/analytics${queryString ? '?' + queryString : ''}`);
}

/**
 * Download payment receipt
 * @param {number} paymentId - Payment ID
 * @returns {Promise<Object>} - Returns {blob, filename}
 */
export async function downloadReceipt(paymentId) {
  const token = localStorage.getItem('auth_token');
  
  const response = await fetch(`/payments/${paymentId}/receipt`, {
    method: 'GET',
    credentials: 'include',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/pdf',
      'X-Requested-With': 'XMLHttpRequest',
      'X-Client-Type': 'web'
    }
  });
  
  if (!response.ok) {
    const error = await response.json().catch(() => ({
      message: `HTTP ${response.status}: ${response.statusText}`
    }));
    throw new Error(error.message || 'Receipt download failed');
  }
  
  const contentDisposition = response.headers.get('Content-Disposition');
  let filename = `receipt_${paymentId}.pdf`;
  
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
 * Debug endpoint
 * @param {Object} filters - Test filters
 */
export async function debugPayments(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.test_param) {
    params.append('test_param', filters.test_param);
  }
  
  const queryString = params.toString();
  return apiGet(`/payments/debug${queryString ? '?' + queryString : ''}`);
}