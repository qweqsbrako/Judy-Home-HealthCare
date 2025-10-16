/**
 * Dashboard Service
 * Handles dashboard-related API calls
 */

import { apiGet } from '../utils/api';

/**
 * Get complete dashboard data for the current user
 * Includes stats, activities, alerts, schedules based on user role
 */
export async function getDashboardData() {
  return apiGet('/dashboard-data');
}