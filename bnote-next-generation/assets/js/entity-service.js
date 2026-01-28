/**
 * BNote Next Generation - Entity Service
 *
 * Copyright (C) 2026 BNote Contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * Entity Service - URL generation and entity detail rendering
 * Entities are separate from modules
 */
const EntityService = {
    entities: {},
    
    /**
     * Load entity registry from entities.json
     */
    async loadEntityRegistry() {
        try {
            const response = await fetch('entities.json');
            const data = await response.json();
            this.entities = data.entities || {};
            console.log('EntityService: Loaded entities:', this.entities);
        } catch (error) {
            console.error('EntityService: Failed to load entities.json:', error);
        }
    },
    
    /**
     * Get URL for entity detail page
     * @param {string} entityType - Entity type (e.g., 'rehearsal', 'contact')
     * @param {number} id - Entity ID
     * @param {string} moduleContext - Current module context (e.g., 'dashboard')
     * @param {string} mode - View mode ('view', 'edit', 'create')
     * @returns {string} URL string
     */
    getEntityDetailUrl(entityType, id, moduleContext, mode = 'view') {
        return NavigationService.getEntityUrl(entityType, id, moduleContext, mode);
    },
    
    /**
     * Get entity owner handler ID
     * @param {string} entityType - Entity type
     * @returns {string|null} Handler ID or null if not found
     */
    getEntityOwner(entityType) {
        if (!this.entities[entityType]) {
            return null;
        }
        return this.entities[entityType].owner;
    },
    
    /**
     * Render entity detail view
     * @param {string} entityType - Entity type
     * @param {number} id - Entity ID
     * @param {string} mode - View mode ('view', 'edit', 'create')
     * @param {string} moduleContext - Current module context
     * @param {HTMLElement} container - Container to render into
     */
    async renderEntityDetail(entityType, id, mode, moduleContext, container) {
        // Load entity registry if not loaded
        if (Object.keys(this.entities).length === 0) {
            await this.loadEntityRegistry();
        }
        
        const entityConfig = this.entities[entityType];
        if (!entityConfig) {
            container.innerHTML = `<div class="text-error">Entity type "${entityType}" not found</div>`;
            return;
        }
        
        // Load entity handler
        const handlerClassName = entityConfig.handler;
        if (!window[handlerClassName]) {
            // Try to load handler script
            await this.loadEntityHandler(entityType);
        }
        
        const HandlerClass = window[handlerClassName];
        if (!HandlerClass) {
            container.innerHTML = `<div class="text-error">Entity handler "${handlerClassName}" not found</div>`;
            return;
        }
        
        // Create handler instance and render
        const handler = new HandlerClass();
        await handler.renderDetail(entityType, id, mode, container);
    },
    
    /**
     * Load entity handler script
     * @param {string} entityType - Entity type
     */
    async loadEntityHandler(entityType) {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = `assets/js/entity-handlers/${entityType}-handler.js`;
            script.onload = () => resolve();
            script.onerror = () => reject(new Error(`Failed to load handler: ${entityType}-handler.js`));
            document.head.appendChild(script);
        });
    }
};

// Export for use in other scripts
window.EntityService = EntityService;
