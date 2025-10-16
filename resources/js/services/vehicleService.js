/**
 * Vehicles Service
 * Handles all vehicle management API calls
 */
import { apiGet, apiPost, apiPut, apiDelete } from '../utils/api';

/**
 * Get all vehicles
 * @param {Object} filters - Optional filters (vehicle_type, status, assigned, search, page, per_page)
 */
export async function getVehicles(filters = {}) {
  const params = new URLSearchParams();
  
  // Add pagination parameters
  if (filters.page) {
    params.append('page', filters.page);
  }
  
  if (filters.per_page) {
    params.append('per_page', filters.per_page);
  }
  
  // Add filter parameters
  if (filters.vehicle_type && filters.vehicle_type !== 'all') {
    params.append('vehicle_type', filters.vehicle_type);
  }
  
  if (filters.status && filters.status !== 'all') {
    params.append('status', filters.status);
  }
  
  if (filters.active && filters.active !== 'all') {
    params.append('active', filters.active);
  }
  
  if (filters.assigned && filters.assigned !== 'all') {
    params.append('assigned', filters.assigned);
  }
  
  if (filters.search) {
    params.append('search', filters.search);
  }
  
  const queryString = params.toString();
  const url = `/vehicles${queryString ? '?' + queryString : ''}`;
  
  console.log('getVehicles - URL:', url);
  console.log('getVehicles - Filters:', filters);
  
  return apiGet(url);
}

/**
 * Get a single vehicle by ID
 * @param {number} vehicleId - Vehicle ID
 */
export async function getVehicle(vehicleId) {
  return apiGet(`/vehicles/${vehicleId}`);
}

/**
 * Create a new vehicle
 * @param {Object} vehicleData - Vehicle data
 */
export async function createVehicle(vehicleData) {
  return apiPost('/vehicles', vehicleData);
}

/**
 * Update an existing vehicle
 * @param {number} vehicleId - Vehicle ID
 * @param {Object} vehicleData - Updated vehicle data
 */
export async function updateVehicle(vehicleId, vehicleData) {
  return apiPut(`/vehicles/${vehicleId}`, vehicleData);
}

/**
 * Update vehicle status
 * @param {number} vehicleId - Vehicle ID
 * @param {string} status - New status (available, in_use, maintenance, out_of_service)
 */
export async function updateVehicleStatus(vehicleId, status) {
  return apiPost(`/vehicles/${vehicleId}/status`, { status });
}

/**
 * Get available vehicles (not assigned to any driver)
 */
export async function getAvailableVehicles() {
  return apiGet('/vehicles/available');
}

/**
 * Assign a driver to a vehicle
 * @param {number} vehicleId - Vehicle ID
 * @param {Object} data - Assignment data (driver_id, notes)
 */
export async function assignDriver(vehicleId, data) {
  return apiPost(`/vehicles/${vehicleId}/assign-driver`, data);
}

/**
 * Unassign driver from vehicle
 * @param {number} vehicleId - Vehicle ID
 * @param {Object} data - Unassignment data (reason)
 */
export async function unassignDriver(vehicleId, data = {}) {
  return apiPost(`/vehicles/${vehicleId}/unassign-driver`, data);
}

/**
 * Get vehicle's transport history
 * @param {number} vehicleId - Vehicle ID
 * @param {Object} filters - Optional filters (status, date_from, date_to, page)
 */
export async function getVehicleTransportHistory(vehicleId, filters = {}) {
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
  return apiGet(`/vehicles/${vehicleId}/transport-history${queryString ? '?' + queryString : ''}`);
}

/**
 * Get dashboard statistics
 */
export async function getDashboardStats() {
  return apiGet('/vehicles/dashboard/stats');
}

/**
 * Export vehicles to CSV
 * @param {Object} filters - Optional filters (vehicle_type, status, search)
 * @returns {Promise<Object>} - Returns {blob, filename}
 */
export async function exportVehicles(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.vehicle_type && filters.vehicle_type !== 'all') {
    params.append('vehicle_type', filters.vehicle_type);
  }
  
  if (filters.status && filters.status !== 'all') {
    params.append('status', filters.status);
  }
  
  if (filters.search) {
    params.append('search', filters.search);
  }
  
  const queryString = params.toString();
  const url = `/vehicles/export${queryString ? '?' + queryString : ''}`;
  
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
  let filename = 'vehicles_export.csv';
  
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
 * Get vehicles with expiring insurance
 * @param {number} days - Number of days to check for expiring insurance
 */
export async function getExpiringInsurance(days = 30) {
  return apiGet(`/vehicles/expiring-insurance?days=${days}`);
}

/**
 * Get vehicles with expiring registration
 * @param {number} days - Number of days to check for expiring registration
 */
export async function getExpiringRegistration(days = 30) {
  return apiGet(`/vehicles/expiring-registration?days=${days}`);
}