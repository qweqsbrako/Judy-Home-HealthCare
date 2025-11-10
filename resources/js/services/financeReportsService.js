/**
 * Finance Reports Service
 * Handles all financial and revenue reports API calls
 */
import { apiGet } from '../utils/api';

// ==================== FINANCIAL REPORTS ====================

/**
 * Get payment statistics report
 * @param {Object} filters - Optional filters (date_from, date_to)
 */
export async function getPaymentStatistics(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/payment-statistics-report${queryString ? '?' + queryString : ''}`);
}

/**
 * Get service utilization report
 * @param {Object} filters - Optional filters (date_from, date_to)
 */
export async function getServiceUtilization(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/service-utilization-report${queryString ? '?' + queryString : ''}`);
}

/**
 * Get revenue analytics report
 * @param {Object} filters - Optional filters (date_from, date_to)
 */
export async function getRevenueAnalytics(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/revenue-analytics${queryString ? '?' + queryString : ''}`);
}

// ==================== EXPORT FUNCTIONS ====================

/**
 * Export a specific financial report
 * @param {string} reportType - Type of report (payment_statistics, service_utilization, revenue_analytics)
 * @param {Object} filters - Optional filters (date_from, date_to)
 * @returns {Promise<Object>} - Returns {blob, filename}
 */
export async function exportFinancialReport(reportType, filters = {}) {
  const params = new URLSearchParams();
  
  params.append('report_type', reportType);
  params.append('format', 'csv');
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  const queryString = params.toString();
  const url = `/reports/export-financial-reports${queryString ? '?' + queryString : ''}`;
  
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
 * Export all financial reports
 * @param {Object} filters - Optional filters (date_from, date_to)
 * @returns {Promise<Array>} - Returns array of {blob, filename} objects
 */
export async function exportAllFinancialReports(filters = {}) {
  const reportTypes = [
    'payment_statistics',
    'service_utilization',
    'revenue_analytics'
  ];
  
  const exportPromises = reportTypes.map((reportType, index) => {
    return new Promise((resolve) => {
      setTimeout(async () => {
        try {
          const result = await exportFinancialReport(reportType, filters);
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
 * Refresh all financial reports
 * @param {Object} filters - Optional filters to apply to all reports
 */
export async function refreshAllFinancialReports(filters = {}) {
  return Promise.all([
    getPaymentStatistics(filters),
    getServiceUtilization(filters),
    getRevenueAnalytics(filters)
  ]);
}

// ==================== ADDITIONAL HELPER FUNCTIONS ====================

/**
 * Get payment method breakdown
 * @param {Object} filters - Optional filters (date_from, date_to)
 */
export async function getPaymentMethodBreakdown(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/payment-method-breakdown${queryString ? '?' + queryString : ''}`);
}

/**
 * Get payment type distribution
 * @param {Object} filters - Optional filters (date_from, date_to)
 */
export async function getPaymentTypeDistribution(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/payment-type-distribution${queryString ? '?' + queryString : ''}`);
}

/**
 * Get revenue trends
 * @param {Object} filters - Optional filters (date_from, date_to, payment_type)
 */
export async function getRevenueTrends(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  if (filters.payment_type) {
    params.append('payment_type', filters.payment_type);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/revenue-trends${queryString ? '?' + queryString : ''}`);
}

/**
 * Get top paying patients
 * @param {Object} filters - Optional filters (date_from, date_to, limit)
 */
export async function getTopPayingPatients(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  if (filters.limit) {
    params.append('limit', filters.limit);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/top-paying-patients${queryString ? '?' + queryString : ''}`);
}

/**
 * Get revenue by care type
 * @param {Object} filters - Optional filters (date_from, date_to)
 */
export async function getRevenueByCareType(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/revenue-by-care-type${queryString ? '?' + queryString : ''}`);
}

/**
 * Get revenue by region
 * @param {Object} filters - Optional filters (date_from, date_to)
 */
export async function getRevenueByRegion(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/revenue-by-region${queryString ? '?' + queryString : ''}`);
}

/**
 * Get service duration analysis
 * @param {Object} filters - Optional filters (date_from, date_to, session_type)
 */
export async function getServiceDurationAnalysis(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  if (filters.session_type) {
    params.append('session_type', filters.session_type);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/service-duration-analysis${queryString ? '?' + queryString : ''}`);
}

/**
 * Get most requested services
 * @param {Object} filters - Optional filters (date_from, date_to, limit)
 */
export async function getMostRequestedServices(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  if (filters.limit) {
    params.append('limit', filters.limit);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/most-requested-services${queryString ? '?' + queryString : ''}`);
}

/**
 * Get geographic service utilization
 * @param {Object} filters - Optional filters (date_from, date_to)
 */
export async function getGeographicUtilization(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/geographic-utilization${queryString ? '?' + queryString : ''}`);
}

/**
 * Get peak usage times
 * @param {Object} filters - Optional filters (date_from, date_to)
 */
export async function getPeakUsageTimes(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/peak-usage-times${queryString ? '?' + queryString : ''}`);
}

/**
 * Get payment completion rate
 * @param {Object} filters - Optional filters (date_from, date_to)
 */
export async function getPaymentCompletionRate(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/payment-completion-rate${queryString ? '?' + queryString : ''}`);
}

/**
 * Get monthly revenue comparison
 * @param {Object} filters - Optional filters (months_back)
 */
export async function getMonthlyRevenueComparison(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.months_back) {
    params.append('months_back', filters.months_back);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/monthly-revenue-comparison${queryString ? '?' + queryString : ''}`);
}

/**
 * Get tax collection summary
 * @param {Object} filters - Optional filters (date_from, date_to)
 */
export async function getTaxCollectionSummary(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/tax-collection-summary${queryString ? '?' + queryString : ''}`);
}

/**
 * Get transaction volume metrics
 * @param {Object} filters - Optional filters (date_from, date_to)
 */
export async function getTransactionVolumeMetrics(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/transaction-volume-metrics${queryString ? '?' + queryString : ''}`);
}

/**
 * Get failed payment analysis
 * @param {Object} filters - Optional filters (date_from, date_to)
 */
export async function getFailedPaymentAnalysis(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/failed-payment-analysis${queryString ? '?' + queryString : ''}`);
}

/**
 * Get refund statistics
 * @param {Object} filters - Optional filters (date_from, date_to)
 */
export async function getRefundStatistics(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/refund-statistics${queryString ? '?' + queryString : ''}`);
}

/**
 * Get average transaction value trends
 * @param {Object} filters - Optional filters (date_from, date_to)
 */
export async function getAvgTransactionValueTrends(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/avg-transaction-value-trends${queryString ? '?' + queryString : ''}`);
}

/**
 * Get patient payment history
 * @param {number} patientId - Patient ID
 * @param {Object} filters - Optional filters (date_from, date_to)
 */
export async function getPatientPaymentHistory(patientId, filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/patient-payment-history/${patientId}${queryString ? '?' + queryString : ''}`);
}

/**
 * Get revenue forecast
 * @param {Object} filters - Optional filters (forecast_months)
 */
export async function getRevenueForecast(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.forecast_months) {
    params.append('forecast_months', filters.forecast_months);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/revenue-forecast${queryString ? '?' + queryString : ''}`);
}

/**
 * Get payment processing time analysis
 * @param {Object} filters - Optional filters (date_from, date_to)
 */
export async function getPaymentProcessingTimeAnalysis(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/payment-processing-time-analysis${queryString ? '?' + queryString : ''}`);
}