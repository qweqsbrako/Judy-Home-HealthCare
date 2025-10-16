/**
 * Drivers Service
 * Handles all driver management API calls
 */
import { apiGet, apiPost, apiPut, apiDelete } from '../utils/api';

/**
 * Get all drivers
 * @param {Object} filters - Optional filters (active, suspended, vehicle_assigned, search, page, per_page)
 */
export async function getDrivers(filters = {}) {
  const params = new URLSearchParams();
  
  // Add pagination parameters
  if (filters.page) {
    params.append('page', filters.page);
  }
  
  if (filters.per_page) {
    params.append('per_page', filters.per_page);
  }
  
  // Add filter parameters
  if (filters.active && filters.active !== 'all') {
    params.append('active', filters.active);
  }
  
  if (filters.suspended && filters.suspended !== 'all') {
    params.append('suspended', filters.suspended);
  }
  
  if (filters.vehicle_assigned && filters.vehicle_assigned !== 'all') {
    params.append('vehicle_assigned', filters.vehicle_assigned);
  }
  
  if (filters.search) {
    params.append('search', filters.search);
  }
  
  const queryString = params.toString();
  const url = `/drivers${queryString ? '?' + queryString : ''}`;
  
  console.log('getDrivers - URL:', url);
  console.log('getDrivers - Filters:', filters);
  
  return apiGet(url);
}

/**
 * Get a single driver by ID
 * @param {number} driverId - Driver ID
 */
export async function getDriver(driverId) {
  return apiGet(`/drivers/${driverId}`);
}

/**
 * Create a new driver
 * @param {FormData} driverData - Driver form data
 */
export async function createDriver(driverData) {
  // Use apiPost - it already handles FormData correctly
  return apiPost('/drivers', driverData);
}

/**
 * Update an existing driver
 * @param {number} driverId - Driver ID
 * @param {FormData} driverData - Updated driver form data
 */
export async function updateDriver(driverId, driverData) {
  // Add _method field for Laravel to recognize this as PUT
  driverData.append('_method', 'PUT');
  // Use apiPost (not apiPut) because Laravel expects POST with _method for FormData
  return apiPost(`/drivers/${driverId}`, driverData);
}

/**
 * Suspend a driver
 * @param {number} driverId - Driver ID
 * @param {Object} data - Suspension data (reason)
 */
export async function suspendDriver(driverId, data) {
  return apiPost(`/drivers/${driverId}/suspend`, data);
}

/**
 * Reactivate a suspended driver
 * @param {number} driverId - Driver ID
 */
export async function reactivateDriver(driverId) {
  return apiPost(`/drivers/${driverId}/reactivate`);
}

/**
 * Get available drivers (not assigned to any vehicle)
 */
export async function getAvailableDrivers() {
  return apiGet('/drivers/available');
}

/**
 * Assign a vehicle to a driver
 * @param {number} driverId - Driver ID
 * @param {Object} data - Assignment data (vehicle_id, notes)
 */
export async function assignVehicle(driverId, data) {
  return apiPost(`/drivers/${driverId}/assign-vehicle`, data);
}

/**
 * Unassign vehicle from driver
 * @param {number} driverId - Driver ID
 * @param {Object} data - Unassignment data (reason)
 */
export async function unassignVehicle(driverId, data = {}) {
  return apiPost(`/drivers/${driverId}/unassign-vehicle`, data);
}

/**
 * Get driver's transport history
 * @param {number} driverId - Driver ID
 * @param {Object} filters - Optional filters (status, date_from, date_to, page)
 */
export async function getDriverTransportHistory(driverId, filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.status) {
    params.append('status', filters.status);
  }
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  if (filters.page) {
    params.append('page', filters.page);
  }
  
  const queryString = params.toString();
  return apiGet(`/drivers/${driverId}/transport-history${queryString ? '?' + queryString : ''}`);
}

/**
 * Get dashboard statistics
 */
export async function getDashboardStats() {
  return apiGet('/drivers/dashboard');
}

/**
 * Export drivers to CSV
 * @param {Object} filters - Optional filters (active, suspended, search)
 * @returns {Promise<Object>} - Returns {blob, filename}
 */
export async function exportDrivers(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.active && filters.active !== 'all') {
    params.append('active', filters.active);
  }
  
  if (filters.suspended && filters.suspended !== 'all') {
    params.append('suspended', filters.suspended);
  }
  
  if (filters.search) {
    params.append('search', filters.search);
  }
  
  const queryString = params.toString();
  const url = `/drivers/export${queryString ? '?' + queryString : ''}`;
  
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
  let filename = 'drivers_export.csv';
  
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
 * Upload driver avatar
 * @param {number} driverId - Driver ID
 * @param {File} avatarFile - Avatar image file
 */
export async function uploadAvatar(driverId, avatarFile) {
  const formData = new FormData();
  formData.append('avatar', avatarFile);
  // Use apiPost - it already handles FormData correctly
  return apiPost(`/drivers/${driverId}/avatar`, formData);
}

/**
 * Get available vehicles for assignment
 */
export async function getAvailableVehicles() {
  return apiGet('/vehicles/available');
}