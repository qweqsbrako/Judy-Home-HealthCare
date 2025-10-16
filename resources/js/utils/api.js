/**
 * API Utility for making authenticated requests
 * Uses session-based authentication (HTTP-only cookies)
 * NO tokens stored in localStorage
 */

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || '';

let csrfToken = null;
let csrfPromise = null;

/**
 * Build full URL with base URL
 */
function buildUrl(endpoint) {
  if (!endpoint.startsWith('/')) {
    endpoint = '/' + endpoint;
  }
  return API_BASE_URL ? `${API_BASE_URL}${endpoint}` : endpoint;
}

/**
 * Get CSRF token (cached after first request)
 */
export async function getCsrfToken() {
  if (csrfToken) {
    return csrfToken;
  }

  if (csrfPromise) {
    return csrfPromise;
  }

  csrfPromise = fetch(buildUrl('/sanctum/csrf-cookie'), {
    method: 'GET',
    credentials: 'include',
    headers: {
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-Client-Type': 'web'
    }
  }).then(() => {
    const cookies = document.cookie.split(';');
    for (let cookie of cookies) {
      const [name, value] = cookie.trim().split('=');
      if (name === 'XSRF-TOKEN') {
        csrfToken = decodeURIComponent(value);
        break;
      }
    }
    csrfPromise = null;
    return csrfToken;
  }).catch(error => {
    console.error('Failed to get CSRF token:', error);
    csrfPromise = null;
    throw error;
  });

  return csrfPromise;
}

/**
 * Parse JSON response with error handling
 */
async function parseResponse(response) {
  if (!response) return null;
  
  if (!response.ok) {
    const error = await response.json().catch(() => ({
      message: `HTTP ${response.status}: ${response.statusText}`
    }));
    throw new Error(error.message || 'Request failed');
  }
  
  return response.json().catch(() => null);
}

/**
 * Make authenticated API request
 */
export async function apiRequest(url, options = {}) {
  const fullUrl = buildUrl(url);
  
  if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(options.method?.toUpperCase())) {
    try {
      await getCsrfToken();
    } catch (error) {
      console.error('Failed to get CSRF token:', error);
    }
  }

  // Check if body is FormData
  const isFormData = options.body instanceof FormData
  
  const defaultHeaders = {
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    'X-Client-Type': 'web',
    ...(csrfToken && { 'X-XSRF-TOKEN': csrfToken }),
  }
  
  // Only add Content-Type for non-FormData requests
  if (!isFormData) {
    defaultHeaders['Content-Type'] = 'application/json'
  }

  const defaultOptions = {
    headers: {
      ...defaultHeaders,
      ...options.headers
    },
    credentials: 'include'
  };

  try {
    const response = await fetch(fullUrl, { ...defaultOptions, ...options });

    if (response.status === 401 && !window.location.pathname.includes('/login')) {
      window.location.href = '/login';
      return null;
    }

    if (response.status === 419) {
      csrfToken = null;
      await getCsrfToken();
      return fetch(fullUrl, { ...defaultOptions, ...options });
    }

    return response;
  } catch (error) {
    console.error('API request failed:', error);
    throw error;
  }
}

/**
 * GET request helper
 */
export async function apiGet(url, options = {}) {
  const response = await apiRequest(url, { ...options, method: 'GET' });
  return parseResponse(response);
}

/**
 * POST request helper
 */
export async function apiPost(url, data = null, options = {}) {
  // Check if data is FormData
  const isFormData = data instanceof FormData
  
  const requestOptions = {
    ...options,
    method: 'POST',
  }
  
  // Only set body if we have data
  if (data) {
    if (isFormData) {
      // For FormData, pass it directly - browser will set correct Content-Type with boundary
      requestOptions.body = data
      // Remove Content-Type header so browser sets it automatically
      if (requestOptions.headers) {
        delete requestOptions.headers['Content-Type']
      }
    } else {
      // For regular data, stringify as JSON
      requestOptions.body = JSON.stringify(data)
    }
  }
  
  const response = await apiRequest(url, requestOptions)
  return parseResponse(response)
}

/**
 * PUT request helper
 */
export async function apiPut(url, data = null, options = {}) {
  // Check if data is FormData
  const isFormData = data instanceof FormData
  
  const requestOptions = {
    ...options,
    method: 'PUT',
  }
  
  // Only set body if we have data
  if (data) {
    if (isFormData) {
      // For FormData, pass it directly
      requestOptions.body = data
      // Remove Content-Type header so browser sets it automatically
      if (requestOptions.headers) {
        delete requestOptions.headers['Content-Type']
      }
    } else {
      // For regular data, stringify as JSON
      requestOptions.body = JSON.stringify(data)
    }
  }
  
  const response = await apiRequest(url, requestOptions)
  return parseResponse(response)
}
/**
 * DELETE request helper
 */
export async function apiDelete(url, options = {}) {
  const response = await apiRequest(url, { ...options, method: 'DELETE' });
  return parseResponse(response);
}

/**
 * Check if user is authenticated
 */
export async function checkAuth() {
  try {
    return await apiGet('/auth/me');
  } catch (error) {
    console.error('Auth check failed:', error);
    return null;
  }
}

/**
 * Logout helper
 */
export async function logout() {
  try {
    await apiPost('/auth/logout');
  } catch (error) {
    console.error('Logout failed:', error);
  } finally {
    csrfToken = null;
    window.location.href = '/login';
  }
}