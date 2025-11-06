/**
 * Profile Service
 * Handles all profile management API calls
 */
import { apiGet, apiPost, apiPut, apiDelete } from '../utils/api';

/**
 * Get current user profile
 * @returns {Promise<Object>} User profile data
 */
export async function getProfile() {
  return apiGet('/profile');
}

/**
 * Update general profile information
 * @param {Object} profileData - Profile data to update
 * @param {string} profileData.first_name - First name
 * @param {string} profileData.last_name - Last name
 * @param {string} profileData.email - Email address
 * @param {string} profileData.phone - Phone number
 * @param {string} profileData.date_of_birth - Date of birth (YYYY-MM-DD)
 * @param {string} profileData.gender - Gender (male/female/other)
 * @param {string} [profileData.emergency_contact_name] - Emergency contact name (patients only)
 * @param {string} [profileData.emergency_contact_phone] - Emergency contact phone (patients only)
 * @returns {Promise<Object>} Updated profile data
 */
export async function updateProfile(profileData) {
  return apiPut('/profile/update', profileData);
}

/**
 * Update user password
 * @param {Object} passwordData - Password change data
 * @param {string} passwordData.current_password - Current password
 * @param {string} passwordData.new_password - New password (min 8 characters)
 * @param {string} passwordData.new_password_confirmation - Confirm new password
 * @returns {Promise<Object>} Success response
 */
export async function updatePassword(passwordData) {
  return apiPut('/profile/update-password', passwordData);
}

/**
 * Update professional information (nurses/doctors only)
 * @param {Object} professionalData - Professional information
 * @param {string} [professionalData.license_number] - Professional license number
 * @param {string} [professionalData.specialization] - Area of specialization
 * @param {number} [professionalData.years_experience] - Years of experience
 * @returns {Promise<Object>} Updated profile data
 */
export async function updateProfessionalInfo(professionalData) {
  return apiPut('/profile/update-professional', professionalData);
}

/**
 * Update profile avatar/photo
 * @param {File} file - Image file (JPG, PNG, GIF - max 2MB)
 * @returns {Promise<Object>} Updated profile with new avatar URL
 */
export async function updateAvatar(file) {
  const formData = new FormData();
  formData.append('avatar', file);
  
  return apiPost('/profile/update-avatar', formData);
}

/**
 * Delete profile avatar/photo
 * @returns {Promise<Object>} Success response
 */
export async function deleteAvatar() {
  return apiDelete('/profile/avatar');
}

/**
 * Enable two-factor authentication
 * @param {Object} twoFactorData - Two-factor authentication data
 * @param {string} twoFactorData.method - Verification method ('email' or 'sms')
 * @returns {Promise<Object>} Success response with 2FA details
 */
export async function enableTwoFactor(twoFactorData) {
  return apiPost('/profile/enable-2fa', twoFactorData);
}

/**
 * Disable two-factor authentication
 * @returns {Promise<Object>} Success response
 */
export async function disableTwoFactor() {
  return apiPost('/profile/disable-2fa');
}

/**
 * Verify two-factor authentication code
 * @param {Object} verificationData - Verification data
 * @param {string} verificationData.code - Verification code received
 * @returns {Promise<Object>} Success response
 */
export async function verifyTwoFactorCode(verificationData) {
  return apiPost('/profile/verify-2fa', verificationData);
}

/**
 * Get profile activity log
 * @param {Object} filters - Optional filters
 * @param {number} [filters.page] - Page number
 * @param {number} [filters.per_page] - Results per page
 * @returns {Promise<Object>} Activity log data
 */
export async function getActivityLog(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.page) {
    params.append('page', filters.page);
  }
  if (filters.per_page) {
    params.append('per_page', filters.per_page);
  }
  
  const queryString = params.toString();
  const url = `/profile/activity${queryString ? '?' + queryString : ''}`;
  
  return apiGet(url);
}

/**
 * Request email verification
 * @returns {Promise<Object>} Success response
 */
export async function requestEmailVerification() {
  return apiPost('/profile/request-email-verification');
}

/**
 * Update notification preferences
 * @param {Object} preferences - Notification preferences
 * @param {boolean} [preferences.email_notifications] - Email notifications enabled
 * @param {boolean} [preferences.sms_notifications] - SMS notifications enabled
 * @param {boolean} [preferences.push_notifications] - Push notifications enabled
 * @returns {Promise<Object>} Updated preferences
 */
export async function updateNotificationPreferences(preferences) {
  return apiPut('/profile/notification-preferences', preferences);
}

/**
 * Get notification preferences
 * @returns {Promise<Object>} Current notification preferences
 */
export async function getNotificationPreferences() {
  return apiGet('/profile/notification-preferences');
}

/**
 * Deactivate account
 * @param {Object} deactivationData - Deactivation data
 * @param {string} deactivationData.password - User password for confirmation
 * @param {string} [deactivationData.reason] - Reason for deactivation
 * @returns {Promise<Object>} Success response
 */
export async function deactivateAccount(deactivationData) {
  return apiPost('/profile/deactivate', deactivationData);
}

/**
 * Export profile data
 * @returns {Promise<Object>} Returns {blob, filename}
 */
export async function exportProfileData() {
  const response = await fetch('/profile/export', {
    method: 'GET',
    credentials: 'include',
    headers: {
      'Accept': 'application/json',
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
    filename: response.headers.get('Content-Disposition')?.match(/filename=(.+)/)?.[1] || 'profile_data.json'
  };
}