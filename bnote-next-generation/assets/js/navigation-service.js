/**
 * BNote Next Generation - Navigation Service
 *
 * Copyright (C) 2026 BNote Contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * Navigation Service - URL generation helpers
 * Generates URLs for <a href> links (full page loads)
 */
const NavigationService = {
    /**
     * Get URL for module page
     * @param {string} moduleRoute - Module route (e.g., 'dashboard', 'contacts')
     * @returns {string} URL string
     */
    getModuleUrl(moduleRoute) {
        return `app.html?module=${moduleRoute}`;
    },
    
    /**
     * Get URL for entity detail page
     * @param {string} entityType - Entity type (e.g., 'rehearsal', 'contact')
     * @param {number} id - Entity ID
     * @param {string} moduleContext - Current module context (e.g., 'dashboard')
     * @param {string} mode - View mode ('view', 'edit', 'create')
     * @returns {string} URL string
     */
    getEntityUrl(entityType, id, moduleContext, mode = 'view') {
        return `entity-detail.html?module=${moduleContext}&entity=${entityType}&id=${id}&mode=${mode}`;
    }
};

// Export for use in other scripts
window.NavigationService = NavigationService;
