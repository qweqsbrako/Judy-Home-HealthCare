/**
 * Transport Service
 * Handles all transport management API calls
 */
import { apiGet, apiPost, apiPut, apiDelete } from '../utils/api';

/**
 * Get all transport requests
 * @param {Object} filters - Optional filters (status, priority, type, search, page, per_page)
 */
export async function getTransports(filters = {}) {
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
  
  if (filters.priority && filters.priority !== 'all') {
    params.append('priority', filters.priority);
  }
  
  if (filters.type && filters.type !== 'all') {
    params.append('type', filters.type);
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
  
  const queryString = params.toString();
  const url = `/transports${queryString ? '?' + queryString : ''}`;
  
  console.log('getTransports - URL:', url);
  console.log('getTransports - Filters:', filters);
  
  return apiGet(url);
}

/**
 * Get a single transport request by ID
 * @param {number} transportId - Transport ID
 */
export async function getTransport(transportId) {
  return apiGet(`/transports/${transportId}`);
}

/**
 * Create a new transport request
 * @param {Object} transportData - Transport request data
 */
export async function createTransport(transportData) {
  return apiPost('/transports', transportData);
}

/**
 * Update an existing transport request
 * @param {number} transportId - Transport ID
 * @param {Object} transportData - Updated transport data
 */
export async function updateTransport(transportId, transportData) {
  return apiPut(`/transports/${transportId}`, transportData);
}

/**
 * Cancel a transport request
 * @param {number} transportId - Transport ID
 * @param {Object} data - Cancellation data (reason)
 */
export async function cancelTransport(transportId, data = {}) {
  return apiPost(`/transports/${transportId}/cancel`, data);
}

/**
 * Assign driver to transport request
 * @param {number} transportId - Transport ID
 * @param {Object} data - Assignment data (driver_id, notes)
 */
export async function assignDriver(transportId, data) {
  return apiPost(`/transports/${transportId}/assign-driver`, data);
}

/**
 * Start transport
 * @param {number} transportId - Transport ID
 */
export async function startTransport(transportId) {
  return apiPost(`/transports/${transportId}/start`);
}

/**
 * Complete transport
 * @param {number} transportId - Transport ID
 * @param {Object} data - Completion data (actual_cost, feedback, rating)
 */
export async function completeTransport(transportId, data = {}) {
  return apiPost(`/transports/${transportId}/complete`, data);
}

/**
 * Get dashboard statistics
 */
export async function getDashboardStats() {
  console.log('Fetching dashboard stats from /transports/dashboard...');
  const response = await apiGet('/transports/dashboard');
  console.log('Dashboard stats response:', response);
  return response;
}

/**
 * Export transport requests to CSV
 * @param {Object} filters - Optional filters (status, priority, type, search)
 * @returns {Promise<Object>} - Returns {blob, filename}
 */
export async function exportTransports(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.status && filters.status !== 'all') {
    params.append('status', filters.status);
  }
  
  if (filters.priority && filters.priority !== 'all') {
    params.append('priority', filters.priority);
  }
  
  if (filters.type && filters.type !== 'all') {
    params.append('type', filters.type);
  }
  
  if (filters.search) {
    params.append('search', filters.search);
  }
  
  const queryString = params.toString();
  const url = `/transports/export${queryString ? '?' + queryString : ''}`;
  
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
  let filename = 'transports_export.csv';
  
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
 * Get available drivers for assignment
 */
export async function getAvailableDrivers() {
  return apiGet('/drivers/available');
}

/**
 * Get patients for dropdown
 */
export async function getPatients() {
  return apiGet('/users?role=patient');
}

/**
 * Get my transport requests (for patients and nurses)
 */
export async function getMyRequests(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.page) {
    params.append('page', filters.page);
  }
  
  if (filters.per_page) {
    params.append('per_page', filters.per_page);
  }
  
  const queryString = params.toString();
  const url = `/transports/my-requests${queryString ? '?' + queryString : ''}`;
  
  return apiGet(url);
}