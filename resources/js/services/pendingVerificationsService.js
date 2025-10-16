/**
 * Pending Verifications Service
 * Handles all pending verification API calls
 */
import { apiGet, apiPost, apiDelete } from '../utils/api';

/**
 * Get all pending verifications
 * @param {Object} filters - Optional filters (role, search, sort_by, sort_direction)
 */
export async function getPendingVerifications(filters = {}) {
  const params = new URLSearchParams();
  
  // Add filter parameters
  if (filters.role && filters.role !== 'all') {
    params.append('role', filters.role);
  }
  
  if (filters.search) {
    params.append('search', filters.search);
  }
  
  if (filters.sort_by) {
    params.append('sort_by', filters.sort_by);
  }
  
  if (filters.sort_direction) {
    params.append('sort_direction', filters.sort_direction);
  }
  
  const queryString = params.toString();
  const url = `/pending-verifications${queryString ? '?' + queryString : ''}`;
  
  console.log('getPendingVerifications - URL:', url);
  console.log('getPendingVerifications - Filters:', filters);
  
  return apiGet(url);
}

/**
 * Get a single pending user by ID
 * @param {number} userId - User ID
 */
export async function getPendingUser(userId) {
  return apiGet(`/pending-verifications/${userId}`);
}

/**
 * Verify a pending user
 * @param {number} userId - User ID
 * @param {Object} data - Verification data (verification_notes)
 */
export async function verifyUser(userId, data = {}) {
  return apiPost(`/pending-verifications/${userId}/verify`, data);
}

/**
 * Reject a pending user
 * @param {number} userId - User ID
 * @param {Object} data - Rejection data (rejection_reason)
 */
export async function rejectUser(userId, data) {
  return apiPost(`/pending-verifications/${userId}/reject`, data);
}

/**
 * Bulk verify multiple users
 * @param {Object} data - Bulk verification data (user_ids, verification_notes)
 */
export async function bulkVerifyUsers(data) {
  return apiPost('/pending-verifications/bulk-verify', data);
}

/**
 * Bulk reject multiple users
 * @param {Object} data - Bulk rejection data (user_ids, rejection_reason)
 */
export async function bulkRejectUsers(data) {
  return apiPost('/pending-verifications/bulk-reject', data);
}

/**
 * Get pending verification statistics
 */
export async function getStatistics() {
  return apiGet('/pending-verifications/statistics');
}

/**
 * Export pending verifications to CSV
 * @param {Object} filters - Optional filters (role, search)
 * @returns {Promise<Object>} - Returns {blob, filename}
 */
export async function exportPendingVerifications(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.role && filters.role !== 'all') {
    params.append('role', filters.role);
  }
  
  if (filters.search) {
    params.append('search', filters.search);
  }
  
  const queryString = params.toString();
  const url = `/pending-verifications/export${queryString ? '?' + queryString : ''}`;
  
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
    filename: response.headers.get('Content-Disposition')?.match(/filename=(.+)/)?.[1] || 'pending_verifications_export.csv'
  };
}