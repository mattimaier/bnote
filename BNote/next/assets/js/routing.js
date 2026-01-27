/**
 * BNote Next Generation - Routing Utility
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
 * Routing utility for handling URL parameters and navigation state
 */
const Routing = {
    STORAGE_KEY: 'bnote_pending_navigation',

    /**
     * Parse event parameters from URL
     * Supports: ?rehearsal={id} or ?concert={id}
     * @returns {object|null} { type: 'R'|'C', id: number } or null
     */
    getEventFromUrl() {
        const urlParams = new URLSearchParams(window.location.search);
        const rehearsalId = urlParams.get('rehearsal');
        const concertId = urlParams.get('concert');

        if (rehearsalId) {
            const id = parseInt(rehearsalId, 10);
            if (!isNaN(id) && id > 0) {
                return { type: 'R', id };
            }
        }

        if (concertId) {
            const id = parseInt(concertId, 10);
            if (!isNaN(id) && id > 0) {
                return { type: 'C', id };
            }
        }

        return null;
    },

    /**
     * Store pending navigation in sessionStorage
     * Used to preserve event navigation through login redirect
     * @param {string} eventType - 'R' for rehearsal, 'C' for concert
     * @param {number} eventId - Event ID
     */
    storePendingNavigation(eventType, eventId) {
        try {
            const navigation = {
                eventType,
                eventId: parseInt(eventId, 10),
                timestamp: Date.now()
            };
            sessionStorage.setItem(this.STORAGE_KEY, JSON.stringify(navigation));
        } catch (error) {
            console.warn('Failed to store pending navigation:', error);
        }
    },

    /**
     * Get and clear pending navigation from sessionStorage
     * @returns {object|null} { eventType: string, eventId: number } or null
     */
    getPendingNavigation() {
        try {
            const stored = sessionStorage.getItem(this.STORAGE_KEY);
            if (!stored) {
                return null;
            }

            const navigation = JSON.parse(stored);
            
            // Clear after reading
            sessionStorage.removeItem(this.STORAGE_KEY);
            
            // Validate structure
            if (navigation.eventType && navigation.eventId) {
                return {
                    eventType: navigation.eventType,
                    eventId: parseInt(navigation.eventId, 10)
                };
            }
            
            return null;
        } catch (error) {
            console.warn('Failed to get pending navigation:', error);
            // Clear invalid data
            try {
                sessionStorage.removeItem(this.STORAGE_KEY);
            } catch (e) {
                // Ignore cleanup errors
            }
            return null;
        }
    },

    /**
     * Clear pending navigation from sessionStorage
     */
    clearPendingNavigation() {
        try {
            sessionStorage.removeItem(this.STORAGE_KEY);
        } catch (error) {
            console.warn('Failed to clear pending navigation:', error);
        }
    },

    /**
     * Build dashboard URL with event parameters
     * @param {string} eventType - 'R' for rehearsal, 'C' for concert
     * @param {number} eventId - Event ID
     * @returns {string} URL with query parameters
     */
    buildDashboardUrl(eventType, eventId) {
        const param = eventType === 'R' ? 'rehearsal' : 'concert';
        return `dashboard.html?${param}=${eventId}`;
    },

    /**
     * Clean URL by removing event parameters
     * Uses history.replaceState to update URL without reload
     */
    cleanUrl() {
        try {
            const url = new URL(window.location.href);
            url.searchParams.delete('rehearsal');
            url.searchParams.delete('concert');
            
            // Update URL without reload
            const cleanUrl = url.pathname + (url.search ? url.search : '');
            window.history.replaceState({}, '', cleanUrl);
        } catch (error) {
            console.warn('Failed to clean URL:', error);
        }
    }
};
