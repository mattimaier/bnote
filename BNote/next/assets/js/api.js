/**
 * BNote API Client
 * Simple fetch-based API client for BNote lightweight JSON API
 */
class Api {
    constructor() {
        // Calculate base path from current location
        // The API is at /api/index.php (root level), but we're in /next/
        // So we need to go up one level from /next/ to reach the root
        const pathname = window.location.pathname;

        // Method 1: Try to find /next/ or /next in the pathname and get everything before it
        let basePath;
        const nextSlashIndex = pathname.indexOf('/next/');
        const nextIndex = pathname.indexOf('/next');

        if (nextSlashIndex !== -1) {
            // Found /next/ with trailing slash
            basePath = pathname.substring(0, nextSlashIndex);
        } else if (nextIndex !== -1) {
            // Found /next without trailing slash (e.g., /next/login.html)
            basePath = pathname.substring(0, nextIndex);
        } else {
            // Fallback: parse path segments
            const pathParts = pathname.split('/').filter(p => p && p !== '');
            const nextPos = pathParts.indexOf('next');
            if (nextPos !== -1) {
                pathParts.splice(nextPos);
            } else {
                // Remove filename if no 'next' found
                if (pathParts.length > 0 && pathParts[pathParts.length - 1].includes('.')) {
                    pathParts.pop();
                }
            }
            basePath = pathParts.length > 0 ? '/' + pathParts.join('/') : '/';
        }

        // Ensure basePath ends with / (unless it's root)
        if (basePath !== '/' && !basePath.endsWith('/')) {
            basePath += '/';
        }

        // API is at root level: basePath + 'api/index.php'
        // Example: /bnote/BNote/ + api/index.php = /bnote/BNote/api/index.php
        this.baseUrl = basePath + 'api/index.php';

        // Debug: log the calculated URL with detailed info
        console.log('API Base URL:', this.baseUrl, 'from pathname:', pathname, 'basePath:', basePath, 'nextIndex:', nextIndex, 'nextSlashIndex:', nextSlashIndex);
    }

    /**
     * Make API request
     * @param {string} module Module name (e.g., 'rehearsals', 'dashboard')
     * @param {string} action Action name (e.g., 'list', 'get', 'create')
     * @param {object|null} data POST data (null for GET requests)
     * @param {object} params URL query parameters
     * @returns {Promise<*>} API response data
     */
    async request(module, action, data = null, params = {}) {
        // Use absolute URL to ensure correct path
        const url = new URL(this.baseUrl, window.location.origin);
        url.searchParams.set('module', module);
        url.searchParams.set('action', action);

        // Add params to URL
        Object.keys(params).forEach(key => {
            url.searchParams.set(key, params[key]);
        });

        const options = {
            method: data ? 'POST' : 'GET',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin', // Include cookies for session
        };

        if (data) {
            // Add action to POST body
            options.body = JSON.stringify({ ...data, action });
        }

        try {
            const response = await fetch(url, options);

            // Check if response is OK
            if (!response.ok) {
                const text = await response.text();
                let errorData;
                try {
                    errorData = JSON.parse(text);
                } catch (e) {
                    errorData = { error: text };
                }
                console.error('API HTTP Error:', response.status, errorData);
                const error = new Error(errorData.error || `API request failed: ${response.status} ${response.statusText}`);
                error.status = response.status;
                error.code = errorData.code || response.status;
                throw error;
            }

            // Try to parse as JSON
            const text = await response.text();
            let result;
            try {
                result = JSON.parse(text);
            } catch (parseError) {
                console.error('API JSON Parse Error:', parseError);
                console.error('Response text:', text);
                throw new Error('Invalid JSON response from server');
            }

            if (!result.success) {
                throw new Error(result.error || 'Request failed');
            }

            return result.data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }

    /**
     * GET request convenience method
     */
    async get(module, action, params = {}) {
        return this.request(module, action, null, params);
    }

    /**
     * POST request convenience method
     */
    async post(module, action, data = {}, params = {}) {
        return this.request(module, action, data, params);
    }
}

// Create global API instance
const api = new Api();

// Module-specific helpers
const AuthApi = {
    login: (username, password) => api.post('auth', 'login', { username, password }),
    logout: () => api.post('auth', 'logout'),
    checkSession: () => api.get('auth', 'session')
};

const DashboardApi = {
    getDashboard: () => api.get('dashboard', 'dashboard'),
    getInbox: (otype = null) => {
        const params = otype ? { otype } : {};
        return api.get('dashboard', 'inbox', params);
    },
    getNews: () => api.get('dashboard', 'news'),
    getEventsNeedingResponse: () => api.get('dashboard', 'eventsNeedingResponse'),
    respondToEvent: (otype, oid, attending, reason = '') => api.post('dashboard', 'respondToEvent', { otype, oid, attending, reason })
};

const ParticipationApi = {
    getStatus: (eventId, eventType) => api.get('participation', 'get', { event_id: eventId, event_type: eventType }),
    saveStatus: (eventId, eventType, status, reason = '') => api.post('participation', 'save', { event_id: eventId, event_type: eventType, status, reason })
};

const UsersApi = {
    list: () => api.get('users', 'list'),
    get: (id) => api.get('users', 'get', { id }),
    create: (data) => api.post('users', 'create', data),
    update: (id, data) => api.post('users', 'update', { id, ...data }),
    delete: (id) => api.post('users', 'delete', { id }),
    activate: (id) => api.post('users', 'activate', { id }),
    getPrivileges: (id) => api.get('users', 'getPrivileges', { id }),
    updatePrivileges: (id, privileges) => api.post('users', 'updatePrivileges', { id, privileges }),
    getContacts: () => api.get('users', 'getContacts'),
    getLongInactiveUsers: () => api.get('users', 'getLongInactiveUsers'),
    deleteUsersFull: (userIds) => api.post('users', 'deleteUsersFull', { userIds })
};
