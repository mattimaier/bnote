/**
 * BNote Next Generation - Module Base Class
 *
 * Copyright (C) 2026 BNote Contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * Module Base Class
 * Base class for all modules - pure UI containers
 */
class ModuleBase {
    constructor() {
        this.session = null;
        this.container = null;
        this.route = '';
    }
    
    /**
     * Initialize module
     * @param {Object} session - User session
     * @param {HTMLElement} container - Container element
     */
    async init(session, container) {
        this.session = session;
        this.container = container;
        
        // Render module template
        if (typeof this.getTemplate === 'function') {
            container.innerHTML = this.getTemplate();
        }
        
        // Call module-specific initialization
        if (typeof this.onInit === 'function') {
            await this.onInit(session, container);
        }
    }
    
    /**
     * Get entity link URL
     * @param {string} entityType - Entity type
     * @param {number} id - Entity ID
     * @param {string} mode - View mode ('view', 'edit', 'create')
     * @returns {string} URL string
     */
    getEntityLink(entityType, id, mode = 'view') {
        if (typeof EntityService !== 'undefined') {
            return EntityService.getEntityDetailUrl(entityType, id, this.route, mode);
        }
        return NavigationService.getEntityUrl(entityType, id, this.route, mode);
    }
    
    /**
     * Cleanup module
     */
    cleanup() {
        if (typeof this.onCleanup === 'function') {
            this.onCleanup();
        }
    }
}

// Export for use in other scripts
window.ModuleBase = ModuleBase;
