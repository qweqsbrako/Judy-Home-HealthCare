/**
 * Transportation Reports Service
 * Handles all transportation and driver performance reports API calls
 */
import { apiGet } from '../utils/api';

// ==================== TRANSPORTATION REPORTS ====================

/**
 * Get transport utilization report
 * @param {Object} filters - Optional filters (date_from, date_to)
 */
export async function getTransportUtilization(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/transport-utilization-report${queryString ? '?' + queryString : ''}`);
}

/**
 * Get driver performance report
 * @param {Object} filters - Optional filters (date_from, date_to, driver_id)
 */
export async function getDriverPerformance(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  if (filters.driver_id) {
    params.append('driver_id', filters.driver_id);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/driver-performance-report${queryString ? '?' + queryString : ''}`);
}

/**
 * Get vehicle management report
 * @param {Object} filters - Optional filters (date_from, date_to)
 */
export async function getVehicleManagement(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/vehicle-management-report${queryString ? '?' + queryString : ''}`);
}

/**
 * Get transport efficiency report
 * @param {Object} filters - Optional filters (date_from, date_to)
 */
export async function getTransportEfficiency(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/transport-efficiency-report${queryString ? '?' + queryString : ''}`);
}

// ==================== EXPORT FUNCTIONS ====================

/**
 * Export a specific transport report
 * @param {string} reportType - Type of report
 * @param {Object} filters - Optional filters (date_from, date_to, driver_id)
 * @returns {Promise<Object>} - Returns {blob, filename}
 */
export async function exportTransportReport(reportType, filters = {}) {
  const params = new URLSearchParams();
  
  params.append('report_type', reportType);
  params.append('format', 'csv');
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  if (filters.driver_id) {
    params.append('driver_id', filters.driver_id);
  }
  
  const queryString = params.toString();
  const url = `/reports/export-transport-reports${queryString ? '?' + queryString : ''}`;
  
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
  let filename = `${reportType}_${new Date().toISOString().split('T')[0]}.csv`;
  
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
 * Export all transport reports
 * @param {Object} filters - Optional filters (date_from, date_to)
 * @returns {Promise<Array>} - Returns array of {blob, filename} objects
 */
export async function exportAllTransportReports(filters = {}) {
  const reportTypes = [
    'transport_utilization',
    'driver_performance',
    'vehicle_management',
    'transport_efficiency'
  ];
  
  const exportPromises = reportTypes.map((reportType, index) => {
    return new Promise((resolve) => {
      setTimeout(async () => {
        try {
          const result = await exportTransportReport(reportType, filters);
          resolve({ success: true, reportType, ...result });
        } catch (error) {
          resolve({ success: false, reportType, error: error.message });
        }
      }, index * 1000);
    });
  });
  
  return Promise.all(exportPromises);
}

// ==================== REFRESH FUNCTIONS ====================

/**
 * Refresh all transport reports
 * @param {Object} filters - Optional filters to apply to all reports
 */
export async function refreshAllTransportReports(filters = {}) {
  return Promise.all([
    getTransportUtilization(filters),
    getDriverPerformance(filters),
    getVehicleManagement(filters),
    getTransportEfficiency(filters)
  ]);
}

// ==================== ADDITIONAL HELPER FUNCTIONS ====================

/**
 * Get available drivers list
 */
export async function getAvailableDrivers() {
  return apiGet('/users?role=driver&is_active=1');
}

/**
 * Get available vehicles list
 */
export async function getAvailableVehicles() {
  return apiGet('/vehicles?is_active=1');
}

/**
 * Get transport request statistics
 * @param {Object} filters - Optional filters (date_from, date_to)
 */
export async function getTransportRequestStats(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/transport-request-stats${queryString ? '?' + queryString : ''}`);
}

/**
 * Get driver availability metrics
 * @param {Object} filters - Optional filters (date_from, date_to)
 */
export async function getDriverAvailability(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/driver-availability${queryString ? '?' + queryString : ''}`);
}

/**
 * Get vehicle utilization metrics
 * @param {Object} filters - Optional filters (date_from, date_to, vehicle_id)
 */
export async function getVehicleUtilization(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  if (filters.vehicle_id) {
    params.append('vehicle_id', filters.vehicle_id);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/vehicle-utilization${queryString ? '?' + queryString : ''}`);
}

/**
 * Get route efficiency analysis
 * @param {Object} filters - Optional filters (date_from, date_to)
 */
export async function getRouteEfficiency(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/route-efficiency${queryString ? '?' + queryString : ''}`);
}

/**
 * Get peak hours analysis
 * @param {Object} filters - Optional filters (date_from, date_to)
 */
export async function getPeakHoursAnalysis(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/peak-hours-analysis${queryString ? '?' + queryString : ''}`);
}

/**
 * Get transport cost analysis
 * @param {Object} filters - Optional filters (date_from, date_to)
 */
export async function getTransportCostAnalysis(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/transport-cost-analysis${queryString ? '?' + queryString : ''}`);
}

/**
 * Get response time metrics
 * @param {Object} filters - Optional filters (date_from, date_to, priority)
 */
export async function getResponseTimeMetrics(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  if (filters.priority) {
    params.append('priority', filters.priority);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/response-time-metrics${queryString ? '?' + queryString : ''}`);
}

/**
 * Get maintenance alerts
 */
export async function getMaintenanceAlerts() {
  return apiGet('/reports/maintenance-alerts');
}

/**
 * Get driver rating trends
 * @param {Object} filters - Optional filters (date_from, date_to, driver_id)
 */
export async function getDriverRatingTrends(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  if (filters.driver_id) {
    params.append('driver_id', filters.driver_id);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/driver-rating-trends${queryString ? '?' + queryString : ''}`);
}