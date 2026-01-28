/**
 * BNote Next Generation - Router
 *
 * Copyright (C) 2026 BNote Contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * Router - Simple URL parser
 * Browser handles navigation via full page loads
 */
const Router = {
    /**
     * Parse URL to extract module and entity information
     * @returns {Object} Parsed route information
     */
    parseUrl() {
        const urlParams = new URLSearchParams(window.location.search);
        const module = urlParams.get('module') || 'dashboard';
        const entity = urlParams.get('entity');
        const id = urlParams.get('id');
        const mode = urlParams.get('mode') || 'view';
        
        return {
            module,
            entity,
            id: id ? parseInt(id, 10) : null,
            mode
        };
    }
};

// Export for use in other scripts
window.Router = Router;
