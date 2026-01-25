/**
 * BNote API Client
 * Simple fetch-based API client for BNote lightweight JSON API
 */
class Api {
    constructor() {
        // Calculate base path from current location
        // The API is at /api/index.php, but we're in /app/
        // So we need to go up one level from /app/ to reach the root
        const pathname = window.location.pathname;
        
        // Find the position of /app/ in the pathname
        const appIndex = pathname.indexOf('/app/');
        
        let basePath;
        if (appIndex !== -1) {
            // We're in /app/ directory, get everything before it
            basePath = pathname.substring(0, appIndex);
        } else {
            // Fallback: remove filename and any trailing /app
            const pathParts = pathname.split('/').filter(p => p);
            // Remove last part (filename)
            if (pathParts.length > 0) pathParts.pop();
            // Remove 'app' if it's the last part
            if (pathParts.length > 0 && pathParts[pathParts.length - 1] === 'app') {
                pathParts.pop();
            }
            basePath = pathParts.length > 0 ? '/' + pathParts.join('/') : '';
        }
        
        // Ensure basePath ends with /
        if (!basePath.endsWith('/')) {
            basePath += '/';
        }
        if (!basePath.startsWith('/')) {
            basePath = '/' + basePath;
        }
        
        this.baseUrl = basePath + 'api/index.php';
        
        // Debug: log the calculated URL
        console.log('API Base URL:', this.baseUrl, 'from pathname:', pathname, 'basePath:', basePath);
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
                console.error('API HTTP Error:', response.status, text);
                throw new Error(`API request failed: ${response.status} ${response.statusText}`);
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
    getNews: () => api.get('dashboard', 'news')
};
