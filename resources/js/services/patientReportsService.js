/**
 * Patient Reports Service
 * Handles all patient health reports API calls
 */
import { apiGet, apiPost } from '../utils/api';

/**
 * Get patient health trends report
 * @param {Object} filters - Optional filters (date_from, date_to, patient_id)
 */
export async function getPatientHealthTrends(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  if (filters.patient_id) {
    params.append('patient_id', filters.patient_id);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/patient-health-trends${queryString ? '?' + queryString : ''}`);
}

/**
 * Get progress notes analytics report
 * @param {Object} filters - Optional filters (date_from, date_to, patient_id)
 */
export async function getProgressNotesAnalytics(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  if (filters.patient_id) {
    params.append('patient_id', filters.patient_id);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/progress-notes-analytics${queryString ? '?' + queryString : ''}`);
}

/**
 * Get patient outcomes report
 * @param {Object} filters - Optional filters (date_from, date_to, patient_id)
 */
export async function getPatientOutcomes(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  if (filters.patient_id) {
    params.append('patient_id', filters.patient_id);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/patient-outcomes${queryString ? '?' + queryString : ''}`);
}

/**
 * Get medical conditions report
 * @param {Object} filters - Optional filters (date_from, date_to, patient_id)
 */
export async function getMedicalConditions(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  if (filters.patient_id) {
    params.append('patient_id', filters.patient_id);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/medical-conditions${queryString ? '?' + queryString : ''}`);
}

/**
 * Get all available patients for filtering
 */
export async function getAvailablePatients() {
  return apiGet('/users?role=patient');
}

/**
 * Export a specific patient health report
 * @param {string} reportType - Type of report (patient_health_trends, progress_notes_analytics, patient_outcomes, medical_conditions)
 * @param {Object} filters - Optional filters (date_from, date_to, patient_id)
 * @returns {Promise<Object>} - Returns {blob, filename}
 */
export async function exportPatientReport(reportType, filters = {}) {
  const params = new URLSearchParams();
  
  params.append('report_type', reportType);
  params.append('format', 'csv');
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  if (filters.patient_id) {
    params.append('patient_id', filters.patient_id);
  }
  
  const queryString = params.toString();
  const url = `/reports/export${queryString ? '?' + queryString : ''}`;
  
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
 * Export all patient health reports
 * @param {Object} filters - Optional filters (date_from, date_to, patient_id)
 * @returns {Promise<Array>} - Returns array of {blob, filename} objects
 */
export async function exportAllPatientReports(filters = {}) {
  const reportTypes = [
    'patient_health_trends',
    'progress_notes_analytics', 
    'patient_outcomes',
    'medical_conditions'
  ];
  
  const exportPromises = reportTypes.map((reportType, index) => {
    // Stagger exports to avoid overwhelming the server
    return new Promise((resolve) => {
      setTimeout(async () => {
        try {
          const result = await exportPatientReport(reportType, filters);
          resolve({ success: true, reportType, ...result });
        } catch (error) {
          resolve({ success: false, reportType, error: error.message });
        }
      }, index * 1000); // 1 second delay between each export
    });
  });
  
  return Promise.all(exportPromises);
}

/**
 * Refresh all patient reports data
 * @param {Object} filters - Optional filters to apply to all reports
 */
export async function refreshAllPatientReports(filters = {}) {
  return Promise.all([
    getPatientHealthTrends(filters),
    getProgressNotesAnalytics(filters),
    getPatientOutcomes(filters),
    getMedicalConditions(filters)
  ]);
}

/**
 * Get patient vitals history
 * @param {number} patientId - Patient ID
 * @param {Object} filters - Optional filters (date_from, date_to)
 */
export async function getPatientVitalsHistory(patientId, filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  const queryString = params.toString();
  return apiGet(`/patients/${patientId}/vitals${queryString ? '?' + queryString : ''}`);
}

/**
 * Get patient care plan progress
 * @param {number} patientId - Patient ID
 */
export async function getPatientCarePlanProgress(patientId) {
  return apiGet(`/patients/${patientId}/care-plan-progress`);
}

/**
 * Get patient satisfaction metrics
 * @param {Object} filters - Optional filters (date_from, date_to, patient_id)
 */
export async function getPatientSatisfactionMetrics(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  if (filters.patient_id) {
    params.append('patient_id', filters.patient_id);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/patient-satisfaction${queryString ? '?' + queryString : ''}`);
}

/**
 * Get nurse performance by patient outcomes
 * @param {Object} filters - Optional filters (date_from, date_to, nurse_id)
 */
export async function getNursePerformanceByOutcomes(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  if (filters.nurse_id) {
    params.append('nurse_id', filters.nurse_id);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/nurse-performance-outcomes${queryString ? '?' + queryString : ''}`);
}

/**
 * Get treatment adherence report
 * @param {Object} filters - Optional filters (date_from, date_to, patient_id)
 */
export async function getTreatmentAdherence(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.date_from) {
    params.append('date_from', filters.date_from);
  }
  
  if (filters.date_to) {
    params.append('date_to', filters.date_to);
  }
  
  if (filters.patient_id) {
    params.append('patient_id', filters.patient_id);
  }
  
  const queryString = params.toString();
  return apiGet(`/reports/treatment-adherence${queryString ? '?' + queryString : ''}`);
}