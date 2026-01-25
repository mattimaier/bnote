/**
 * Authentication helpers for BNote Modern UI
 */
const Auth = {
    /**
     * Handle login flow
     * @param {string} username Username or email
     * @param {string} password Password
     * @returns {Promise<object>} User data on success
     */
    async login(username, password) {
        try {
            const result = await AuthApi.login(username, password);
            // Session is automatically set via PHP cookies
            return result;
        } catch (error) {
            console.error('Login failed:', error);
            throw error;
        }
    },
    
    /**
     * Handle logout
     */
    async logout() {
        try {
            await AuthApi.logout();
            // Redirect to login page
            window.location.href = 'login.html';
        } catch (error) {
            console.error('Logout failed:', error);
            // Still redirect even if API call fails
            window.location.href = 'login.html';
        }
    },
    
    /**
     * Check if user is logged in
     * @returns {Promise<object>} Session info
     */
    async checkSession() {
        try {
            return await AuthApi.checkSession();
        } catch (error) {
            console.error('Session check failed:', error);
            return { authenticated: false, user: null };
        }
    },
    
    /**
     * Redirect to dashboard if already authenticated
     * Use on login page
     */
    async redirectIfAuthenticated() {
        const session = await this.checkSession();
        if (session.authenticated) {
            window.location.href = 'dashboard.html';
        }
    },
    
    /**
     * Redirect to login if not authenticated
     * Use on protected pages
     */
    async redirectIfNotAuthenticated() {
        const session = await this.checkSession();
        if (!session.authenticated) {
            window.location.href = 'login.html';
        }
        return session;
    }
};
