/**
 * BNote Next Generation - Authentication Helpers
 *
 * Copyright (C) 2026 BNote Contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

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
     * @param {boolean} preserveParams - If true, preserve URL parameters when redirecting
     */
    async redirectIfAuthenticated(preserveParams = false) {
        const session = await this.checkSession();
        if (session.authenticated) {
            if (preserveParams && typeof Routing !== 'undefined') {
                // Check for event parameters in URL
                const eventFromUrl = Routing.getEventFromUrl();
                if (eventFromUrl) {
                    window.location.href = Routing.buildDashboardUrl(eventFromUrl.type, eventFromUrl.id);
                    return;
                }
            }
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
