/**
 * BNote API Client
 * Simple fetch-based API client for BNote lightweight JSON API
 */
class Api {
    constructor() {
        // API lives at /next/api/index.php. Compute base path up to and including /next.
        const pathname = window.location.pathname;
        const nextSlashIndex = pathname.indexOf('/next/');
        const nextIndex = pathname.indexOf('/next');

        let basePath;
        if (nextSlashIndex !== -1) {
            basePath = pathname.substring(0, nextSlashIndex + 6); // through /next/
        } else if (nextIndex !== -1) {
            basePath = pathname.substring(0, nextIndex + 5);      // through /next
        } else {
            const pathParts = pathname.split('/').filter(p => p && p !== '');
            const nextPos = pathParts.indexOf('next');
            if (nextPos !== -1) {
                basePath = '/' + pathParts.slice(0, nextPos + 1).join('/');
            } else {
                const withoutFile = pathParts.length > 0 && pathParts[pathParts.length - 1].includes('.')
                    ? pathParts.slice(0, -1) : pathParts;
                basePath = withoutFile.length > 0 ? '/' + withoutFile.join('/') : '/';
            }
        }

        this.baseUrl = basePath + (basePath.endsWith('/') ? '' : '/') + 'api/index.php';
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
        // Only set action if it's not null/undefined
        if (action != null) {
            url.searchParams.set('action', action);
        }

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
    checkSession: () => api.get('auth', 'session'),
    getModules: () => api.get('auth', 'getModules')
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

const RehearsalsApi = {
    get: (id) => api.get('rehearsals', null, { id })
};

const ConcertsApi = {
    get: (id) => api.get('concerts', null, { id })
};

const TranslationsApi = {
    get: (lang) => api.get('translations', 'get', { lang }),
    getModule: (module, lang) => api.get('translations', 'getModule', { module, lang })
};

const ContactsApi = {
    // Contact CRUD
    list: (groupId = null) => {
        const params = groupId ? { group: groupId } : {};
        return api.get('contacts', 'list', params);
    },
    get: (id) => api.get('contacts', 'get', { id }),
    create: (data) => api.post('contacts', 'create', data),
    update: (id, data) => api.post('contacts', 'update', { id, ...data }),
    delete: (id) => api.post('contacts', 'delete', { id }),
    
    // Groups
    getGroups: () => api.get('contacts', 'getGroups'),
    getGroupContacts: (groupId) => api.get('contacts', 'getGroupContacts', { group: groupId }),
    
    // Integration
    getMembers: (groupId = null) => {
        const params = groupId ? { group: groupId } : {};
        return api.get('contacts', 'getMembers', params);
    },
    getRehearsals: () => api.get('contacts', 'getRehearsals'),
    getPhases: () => api.get('contacts', 'getPhases'),
    getConcerts: () => api.get('contacts', 'getConcerts'),
    getVotes: () => api.get('contacts', 'getVotes'),
    integrate: (data) => api.post('contacts', 'integrate', data),
    
    // Groups management
    listGroups: () => api.get('contacts', 'listGroups'),
    getGroup: (id) => api.get('contacts', 'getGroup', { id }),
    createGroup: (data) => api.post('contacts', 'createGroup', data),
    updateGroup: (id, data) => api.post('contacts', 'updateGroup', { id, ...data }),
    deleteGroup: (id) => api.post('contacts', 'deleteGroup', { id }),
    getGroupMembers: (id) => api.get('contacts', 'getGroupMembers', { id }),
    
    // Printing
    getPrintData: (data) => api.post('contacts', 'getPrintData', data),
    
    // VCard
    importVCard: (formData) => {
        // For file uploads, we need to use FormData
        const url = new URL(api.baseUrl, window.location.origin);
        url.searchParams.set('module', 'contacts');
        url.searchParams.set('action', 'importVCard');
        
        return fetch(url, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        }).then(async (response) => {
            if (!response.ok) {
                const text = await response.text();
                let errorData;
                try {
                    errorData = JSON.parse(text);
                } catch (e) {
                    errorData = { error: text };
                }
                const error = new Error(errorData.error || `API request failed: ${response.status}`);
                error.status = response.status;
                error.code = errorData.code || response.status;
                throw error;
            }
            const result = await response.json();
            if (!result.success) {
                throw new Error(result.error || 'Request failed');
            }
            return result.data;
        });
    },
    
    // GDPR
    getGdprStatus: (ok = 2) => api.get('contacts', 'getGdprStatus', { ok }),
    generateGdprCodes: () => api.post('contacts', 'generateGdprCodes'),
    sendGdprMail: () => api.post('contacts', 'sendGdprMail'),
    deleteGdprNok: (contactIds) => api.post('contacts', 'deleteGdprNok', { contactIds })
};
