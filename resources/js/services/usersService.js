/**
 * Users Service
 * Handles all user management API calls
 */
import { apiGet, apiPost, apiPut, apiDelete } from '../utils/api';

/**
 * Get all users
 * @param {Object} filters - Optional filters (role, status, search, page, per_page)
 */
export async function getUsers(filters = {}) {
  const params = new URLSearchParams();
  
  // Add pagination parameters
  if (filters.page) {
    params.append('page', filters.page);
  }
  if (filters.per_page) {
    params.append('per_page', filters.per_page);
  }
  
  // Add filter parameters
  if (filters.role && filters.role !== 'all') {
    params.append('role', filters.role);
  }
  if (filters.status && filters.status !== 'all') {
    params.append('status', filters.status);
  }
  if (filters.search) {
    params.append('search', filters.search);
  }
  
  const queryString = params.toString();
  const url = `/users${queryString ? '?' + queryString : ''}`;
  
  console.log('getUsers - URL:', url); // Debug log
  console.log('getUsers - Filters:', filters); // Debug log
  
  return apiGet(url);
}

/**
 * Get a single user by ID
 * @param {number} userId - User ID
 */
export async function getUser(userId) {
  return apiGet(`/users/${userId}`);
}

/**
 * Create a new user
 * @param {Object} userData - User data
 */
export async function createUser(userData) {
  return apiPost('/users', userData);
}

/**
 * Update an existing user
 * @param {number} userId - User ID
 * @param {Object} userData - Updated user data
 */
export async function updateUser(userId, userData) {
  return apiPut(`/users/${userId}`, userData);
}

/**
 * Delete a user
 * @param {number} userId - User ID
 */
export async function deleteUser(userId) {
  return apiDelete(`/users/${userId}`);
}

/**
 * Verify a user
 * @param {number} userId - User ID
 */
export async function verifyUser(userId) {
  return apiPost(`/users/${userId}/verify`);
}

/**
 * Suspend a user
 * @param {number} userId - User ID
 */
export async function suspendUser(userId) {
  return apiPost(`/users/${userId}/suspend`);
}

/**
 * Activate a user
 * @param {number} userId - User ID
 */
export async function activateUser(userId) {
  return apiPost(`/users/${userId}/activate`);
}

/**
 * Change user password
 * @param {number} userId - User ID
 * @param {Object} passwordData - { new_password: string }
 */
export async function changeUserPassword(userId, passwordData) {
  return apiPost(`/users/${userId}/change-password`, passwordData);
}

/**
 * Send password reset email to user
 * @param {number} userId - User ID
 */
export async function sendPasswordResetEmail(userId) {
  return apiPost(`/users/${userId}/send-password-reset`);
}


/**
 * Create user with photo
 * @param {FormData} formData - User data including photo file
 */
export async function createUserWithPhoto(formData) {
  console.log('createUserWithPhoto called with FormData') // DEBUG
  
  // Check if it's actually FormData
  if (!(formData instanceof FormData)) {
    console.error('ERROR: Not FormData!', formData)
    throw new Error('Expected FormData but got something else')
  }
  
  // Don't pass headers option - let apiPost handle it
  return apiPost('/users', formData)
}

/**
 * Update user with photo
 * @param {number} userId - User ID
 * @param {FormData} formData - Updated user data including photo file
 */
export async function updateUserWithPhoto(userId, formData) {
  console.log('updateUserWithPhoto called with FormData') // DEBUG
  
  // Check if it's actually FormData
  if (!(formData instanceof FormData)) {
    console.error('ERROR: Not FormData!', formData)
    throw new Error('Expected FormData but got something else')
  }
  
  // Note: We're using POST with _method=PUT (already added in FormData)
  // This is because Laravel doesn't handle PUT with multipart/form-data well
  return apiPost(`/users/${userId}`, formData)
}

/**
 * Delete user photo
 * @param {number} userId - User ID
 */
export async function deleteUserPhoto(userId) {
  return apiDelete(`/users/${userId}/photo`);
}

/**
 * Export users to CSV
 * @param {Object} filters - Optional filters (role, status, search)
 * @returns {Promise<Object>} - Returns {blob, filename}
 */
export async function exportUsers(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.role && filters.role !== 'all') {
    params.append('role', filters.role);
  }
  if (filters.status && filters.status !== 'all') {
    params.append('status', filters.status);
  }
  if (filters.search) {
    params.append('search', filters.search);
  }
  
  const queryString = params.toString();
  const url = `/users/export${queryString ? '?' + queryString : ''}`;
  
  // For file downloads, we need to handle the response differently
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
    filename: response.headers.get('Content-Disposition')?.match(/filename=(.+)/)?.[1] || 'users_export.csv'
  };
}